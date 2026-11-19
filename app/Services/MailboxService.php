<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around PHP's IMAP extension for the company mailbox
 * (e.g. info@neo-labs.uz). Used by the admin "Pochta" section to list,
 * read and file messages; replies are sent through the Laravel mailer.
 */
class MailboxService
{
    /** Folder keys used in admin URLs. */
    public const FOLDERS = ['inbox', 'sent'];

    /** @var resource|\IMAP\Connection|null */
    private $stream = null;

    private ?string $openMailbox = null;

    /** @var array<string, string> */
    private array $specialCache = [];

    public function configured(): bool
    {
        $config = config('services.mailbox');

        return !empty($config['host']) && !empty($config['username']) && !empty($config['password']);
    }

    public function address(): string
    {
        return strtolower((string) config('services.mailbox.username'));
    }

    public function fromName(): string
    {
        return (string) config('services.mailbox.from_name', 'NEO-LABS');
    }

    public function unreadCount(): int
    {
        $stream = $this->open('INBOX');
        $status = @imap_status($stream, $this->ref() . 'INBOX', SA_UNSEEN);

        return $status ? (int) $status->unseen : 0;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function list(string $folder, int $page = 1, int $perPage = 25, ?string $search = null): array
    {
        $stream = $this->open($this->mailboxName($folder));

        $criteria = 'ALL';
        $search = $search !== null ? trim(preg_replace('/[\r\n"\\\\]+/', ' ', $search)) : '';
        if ($search !== '') {
            $criteria = 'TEXT "' . $search . '"';
        }

        $uids = @imap_sort($stream, SORTARRIVAL, true, SE_UID, $criteria, 'UTF-8') ?: [];
        $total = count($uids);
        $slice = array_slice($uids, max(0, ($page - 1) * $perPage), $perPage);

        $items = [];
        if ($slice) {
            $byUid = [];
            foreach (@imap_fetch_overview($stream, implode(',', $slice), FT_UID) ?: [] as $overview) {
                $byUid[(int) $overview->uid] = $overview;
            }

            foreach ($slice as $uid) {
                $overview = $byUid[(int) $uid] ?? null;
                if (!$overview) {
                    continue;
                }

                $items[] = [
                    'uid' => (int) $uid,
                    'from' => $this->decodeHeader($overview->from ?? ''),
                    'to' => $this->decodeHeader($overview->to ?? ''),
                    'subject' => $this->decodeHeader($overview->subject ?? '') ?: '(mavzusiz)',
                    'date' => $this->parseDate($overview->date ?? null),
                    'seen' => !empty($overview->seen),
                    'answered' => !empty($overview->answered),
                    'size' => (int) ($overview->size ?? 0),
                ];
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $folder, int $uid, bool $markSeen = true): ?array
    {
        $stream = $this->open($this->mailboxName($folder));
        $msgno = @imap_msgno($stream, $uid);
        if (!$msgno) {
            return null;
        }

        $header = @imap_headerinfo($stream, $msgno);
        $structure = @imap_fetchstructure($stream, $uid, FT_UID);
        if (!$header || !$structure) {
            return null;
        }

        $rawHeader = (string) @imap_fetchheader($stream, $uid, FT_UID);
        $parts = ['text' => '', 'html' => '', 'attachments' => []];
        $this->walk($stream, $uid, $structure, '', $parts);

        if ($markSeen) {
            @imap_setflag_full($stream, (string) $uid, '\\Seen', ST_UID);
        }

        return [
            'uid' => $uid,
            'folder' => $folder,
            'subject' => $this->decodeHeader($header->subject ?? '') ?: '(mavzusiz)',
            'from' => $this->addresses($header->from ?? []),
            'to' => $this->addresses($header->to ?? []),
            'cc' => $this->addresses($header->cc ?? []),
            'reply_to' => $this->addresses($header->reply_to ?? []),
            'date' => $this->parseDate($header->date ?? null),
            'message_id' => trim((string) ($header->message_id ?? '')),
            'references' => $this->headerValue($rawHeader, 'References'),
            'answered' => trim((string) ($header->Answered ?? '')) === 'A',
            'text' => $parts['text'],
            'html' => $parts['html'],
            'attachments' => $parts['attachments'],
        ];
    }

    /**
     * @return array{name: string, mime: string, data: string}|null
     */
    public function attachment(string $folder, int $uid, string $partNo): ?array
    {
        $stream = $this->open($this->mailboxName($folder));
        $structure = @imap_fetchstructure($stream, $uid, FT_UID);
        $part = $structure ? $this->findPart($structure, $partNo) : null;
        if (!$part) {
            return null;
        }

        $params = $this->params($part);
        $raw = (string) @imap_fetchbody($stream, $uid, $partNo, FT_UID | FT_PEEK);

        return [
            'name' => $this->decodeHeader($params['filename'] ?? $params['name'] ?? '') ?: 'fayl',
            'mime' => $this->mimeType($part),
            'data' => $this->decodeTransfer($raw, (int) ($part->encoding ?? 0)),
        ];
    }

    public function markAnswered(int $uid): void
    {
        $stream = $this->open('INBOX');
        @imap_setflag_full($stream, (string) $uid, '\\Answered', ST_UID);
    }

    /** Move a message to Trash (or delete it when it is already in Trash). */
    public function delete(string $folder, int $uid): void
    {
        $mailbox = $this->mailboxName($folder);
        $trash = $this->specialMailbox('Trash');
        $stream = $this->open($mailbox);

        if ($trash !== $mailbox) {
            @imap_mail_move($stream, (string) $uid, $trash, CP_UID);
        } else {
            @imap_delete($stream, (string) $uid, FT_UID);
        }

        @imap_expunge($stream);
    }

    /** Store a copy of a sent message so it shows up in "Yuborilgan" and in webmail. */
    public function appendToSent(string $rawMessage): void
    {
        try {
            $sent = $this->specialMailbox('Sent');
            $stream = $this->open('INBOX');
            @imap_append($stream, $this->ref() . $sent, str_replace(["\r\n", "\n"], ["\n", "\r\n"], $rawMessage), '\\Seen');
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function decodeHeader(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $out = '';
        foreach (imap_mime_header_decode($value) ?: [] as $element) {
            $out .= $this->toUtf8($element->text, $element->charset === 'default' ? null : $element->charset);
        }

        return trim($out);
    }

    public function __destruct()
    {
        if ($this->stream) {
            @imap_close($this->stream);
        }
    }

    private function mailboxName(string $folder): string
    {
        return $folder === 'sent' ? $this->specialMailbox('Sent') : 'INBOX';
    }

    /** Find (or create) a special folder such as Sent/Trash; cPanel uses the "INBOX." prefix. */
    private function specialMailbox(string $name): string
    {
        if (isset($this->specialCache[$name])) {
            return $this->specialCache[$name];
        }

        $stream = $this->open('INBOX');
        $ref = $this->ref();
        foreach (@imap_list($stream, $ref, '*') ?: [] as $full) {
            $mailbox = substr($full, strlen($ref));
            if (preg_match('/(^|[.\/])' . preg_quote($name, '/') . '$/i', $mailbox)) {
                return $this->specialCache[$name] = $mailbox;
            }
        }

        $mailbox = 'INBOX.' . $name;
        @imap_createmailbox($stream, imap_utf7_encode($ref . $mailbox));
        @imap_subscribe($stream, $ref . $mailbox);
        imap_errors();

        return $this->specialCache[$name] = $mailbox;
    }

    private function ref(): string
    {
        $config = config('services.mailbox');

        return '{' . $config['host'] . ':' . (int) $config['port'] . ($config['flags'] ?: '/imap/ssl') . '}';
    }

    /** @return resource|\IMAP\Connection */
    private function open(string $mailbox)
    {
        if (!$this->configured()) {
            throw new RuntimeException('Pochta sozlanmagan: .env faylida MAILBOX_* qiymatlari yo‘q.');
        }

        if ($this->stream && $this->openMailbox === $mailbox) {
            return $this->stream;
        }

        if ($this->stream) {
            if (@imap_reopen($this->stream, $this->ref() . $mailbox)) {
                $this->openMailbox = $mailbox;

                return $this->stream;
            }
            @imap_close($this->stream);
            $this->stream = null;
        }

        $config = config('services.mailbox');
        $stream = @imap_open($this->ref() . $mailbox, $config['username'], $config['password'], 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        if (!$stream) {
            $error = imap_last_error() ?: 'noma’lum xato';
            imap_errors();
            imap_alerts();
            throw new RuntimeException('Pochta serveriga ulanib bo‘lmadi: ' . $error);
        }

        $this->stream = $stream;
        $this->openMailbox = $mailbox;

        return $stream;
    }

    /** @param array<string, mixed> $out */
    private function walk($stream, int $uid, object $part, string $partNo, array &$out): void
    {
        if (!empty($part->parts)) {
            foreach ($part->parts as $index => $sub) {
                $this->walk($stream, $uid, $sub, $partNo === '' ? (string) ($index + 1) : $partNo . '.' . ($index + 1), $out);
            }

            return;
        }

        $number = $partNo === '' ? '1' : $partNo;
        $params = $this->params($part);
        $mime = $this->mimeType($part);
        $filename = $params['filename'] ?? $params['name'] ?? null;
        $disposition = strtolower((string) ($part->ifdisposition ? $part->disposition : ''));
        $isBody = in_array($mime, ['text/plain', 'text/html'], true) && $filename === null && $disposition !== 'attachment';

        if (!$isBody) {
            $out['attachments'][] = [
                'part' => $number,
                'name' => $filename !== null ? ($this->decodeHeader($filename) ?: 'fayl') : 'fayl.' . (strtolower((string) ($part->subtype ?? 'bin'))),
                'mime' => $mime,
                'size' => (int) ($part->bytes ?? 0),
            ];

            return;
        }

        $key = $mime === 'text/html' ? 'html' : 'text';
        $raw = (string) @imap_fetchbody($stream, $uid, $number, FT_UID | FT_PEEK);
        $body = $this->toUtf8($this->decodeTransfer($raw, (int) ($part->encoding ?? 0)), $params['charset'] ?? null);
        $out[$key] .= ($out[$key] !== '' ? "\n" : '') . $body;
    }

    private function findPart(object $structure, string $partNo): ?object
    {
        if (empty($structure->parts)) {
            return $partNo === '1' ? $structure : null;
        }

        $node = $structure;
        foreach (explode('.', $partNo) as $index) {
            $i = (int) $index - 1;
            if ($i < 0 || empty($node->parts[$i])) {
                return null;
            }
            $node = $node->parts[$i];
        }

        return $node;
    }

    /** @return array<string, string> */
    private function params(object $part): array
    {
        $out = [];
        foreach (['parameters' => 'ifparameters', 'dparameters' => 'ifdparameters'] as $property => $flag) {
            if (!empty($part->{$flag}) && !empty($part->{$property})) {
                foreach ($part->{$property} as $param) {
                    $out[strtolower((string) $param->attribute)] = (string) $param->value;
                }
            }
        }

        // RFC 2231 encoded values, e.g. filename*=utf-8''%D0%9F%D1%80%D0%B0%D0%B9%D1%81.pdf
        foreach (['filename', 'name'] as $key) {
            $encoded = $out[$key . '*'] ?? null;
            if ($encoded === null) {
                $chunks = [];
                foreach ($out as $attr => $value) {
                    if (preg_match('/^' . $key . '\*(\d+)\*?$/', $attr, $m)) {
                        $chunks[(int) $m[1]] = $value;
                    }
                }
                if ($chunks) {
                    ksort($chunks);
                    $encoded = implode('', $chunks);
                }
            }
            if ($encoded !== null) {
                $charset = null;
                if (preg_match("/^([^']*)'[^']*'(.*)$/s", $encoded, $m)) {
                    $charset = $m[1] ?: null;
                    $encoded = $m[2];
                }
                $out[$key] = $this->toUtf8(rawurldecode($encoded), $charset);
            }
        }

        return $out;
    }

    private function mimeType(object $part): string
    {
        $types = ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'model', 'other'];
        $type = $types[(int) ($part->type ?? 3)] ?? 'application';
        $subtype = !empty($part->ifsubtype) ? (string) $part->subtype : 'octet-stream';

        return strtolower($type . '/' . $subtype);
    }

    private function decodeTransfer(string $raw, int $encoding): string
    {
        if ($encoding === ENCBASE64) {
            return (string) base64_decode($raw);
        }

        if ($encoding === ENCQUOTEDPRINTABLE) {
            return quoted_printable_decode($raw);
        }

        return $raw;
    }

    private function toUtf8(string $value, ?string $charset): string
    {
        $charset = $charset ? strtoupper(trim($charset)) : null;

        if ($charset && !in_array($charset, ['UTF-8', 'UTF8', 'US-ASCII', 'ASCII', 'DEFAULT'], true)) {
            try {
                $converted = mb_convert_encoding($value, 'UTF-8', $charset);
                if (is_string($converted)) {
                    return $converted;
                }
            } catch (Throwable $e) {
                // Unknown charset for mbstring; fall back to iconv below.
            }

            $converted = @iconv($charset, 'UTF-8//IGNORE', $value);
            if (is_string($converted)) {
                return $converted;
            }
        }

        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /** @return array<int, array{email: string, name: string}> */
    private function addresses($list): array
    {
        $out = [];
        foreach ((array) $list as $address) {
            if (empty($address->mailbox) || empty($address->host) || $address->host === '.SYNTAX-ERROR.') {
                continue;
            }

            $out[] = [
                'email' => strtolower($address->mailbox . '@' . $address->host),
                'name' => isset($address->personal) ? $this->decodeHeader($address->personal) : '',
            ];
        }

        return $out;
    }

    private function headerValue(string $rawHeader, string $name): string
    {
        $unfolded = (string) preg_replace("/\r?\n[ \t]+/", ' ', $rawHeader);

        return preg_match('/^' . preg_quote($name, '/') . ':\s*(.*)$/mi', $unfolded, $m) ? trim($m[1]) : '';
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse(preg_replace('/\s*\([^)]*\)\s*$/', '', $value))->setTimezone('Asia/Tashkent');
        } catch (Throwable $e) {
            return null;
        }
    }
}
