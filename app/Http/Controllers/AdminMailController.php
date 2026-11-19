<?php

namespace App\Http\Controllers;

use App\Services\MailboxService;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

/**
 * Admin "Pochta": read the company mailbox (info@neo-labs.uz) and reply from the panel.
 */
class AdminMailController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(private MailboxService $mailbox)
    {
    }

    public function index(Request $request)
    {
        $folder = $this->folder($request->query('folder'));
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('q', ''));
        $error = null;
        $result = ['items' => [], 'total' => 0];

        try {
            $result = $this->mailbox->list($folder, $page, self::PER_PAGE, $search !== '' ? $search : null);
            if ($folder === 'inbox' && $search === '' && $page === 1) {
                Cache::forget('admin.nav.unread_mail');
            }
        } catch (Throwable $e) {
            report($e);
            $error = $e->getMessage();
        }

        $messages = new LengthAwarePaginator($result['items'], $result['total'], self::PER_PAGE, $page, [
            'path' => route('dashboard.mail.index'),
            'query' => array_filter(['folder' => $folder === 'inbox' ? null : $folder, 'q' => $search ?: null]),
        ]);

        return view('admin.mail.index', [
            'folder' => $folder,
            'messages' => $messages,
            'search' => $search,
            'error' => $error,
            'address' => $this->mailbox->address(),
        ]);
    }

    public function show(string $folder, int $uid)
    {
        $folder = $this->folder($folder);

        try {
            $message = $this->mailbox->get($folder, $uid);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('dashboard.mail.index')->withErrors(['mail' => $e->getMessage()]);
        }

        abort_if(!$message, 404);
        Cache::forget('admin.nav.unread_mail');

        return view('admin.mail.show', [
            'folder' => $folder,
            'mail' => $message,
            'replyTo' => $this->replyRecipient($message),
            'address' => $this->mailbox->address(),
        ]);
    }

    public function attachment(string $folder, int $uid, string $part)
    {
        $attachment = $this->mailbox->attachment($this->folder($folder), $uid, $part);
        abort_if(!$attachment, 404);

        $name = str_replace(['/', '\\', "\r", "\n", "\0"], '_', $attachment['name']);
        $fallback = Str::ascii($name) ?: 'attachment';
        $fallback = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fallback) ?: 'attachment';

        // Always force a download so HTML/SVG attachments never render inside the admin origin.
        return response($attachment['data'], 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reply(Request $request, int $uid)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'quote' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        try {
            $original = $this->mailbox->get('inbox', $uid, false);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['mail' => $e->getMessage()]);
        }

        abort_if(!$original, 404);
        $recipient = $this->replyRecipient($original);
        if (!$recipient) {
            return back()->withInput()->withErrors(['mail' => 'Bu xat uchun javob manzili topilmadi.']);
        }

        $subject = preg_match('/^\s*(re|ответ|javob)\s*:/iu', $original['subject']) ? $original['subject'] : 'Re: ' . $original['subject'];
        $body = rtrim($data['body']) . "\n\n" . $this->signature();
        if ($request->boolean('quote', true)) {
            $body .= "\n\n" . $this->quote($original);
        }

        $references = trim($original['references'] . ' ' . $original['message_id']);

        try {
            $this->deliver($recipient['email'], $recipient['name'], $subject, $body, $request->file('attachments', []), $original['message_id'] ?: null, $references ?: null);
            $this->mailbox->markAnswered($uid);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['mail' => 'Xat yuborilmadi: ' . $e->getMessage()]);
        }

        return redirect()->route('dashboard.mail.show', ['folder' => 'inbox', 'uid' => $uid])
            ->with('success', "Javob {$recipient['email']} manziliga yuborildi.");
    }

    public function compose(Request $request)
    {
        return view('admin.mail.compose', [
            'address' => $this->mailbox->address(),
            'to' => (string) $request->query('to', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'email:filter', 'max:255'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        try {
            $this->deliver($data['to'], '', $data['subject'], rtrim($data['body']) . "\n\n" . $this->signature(), $request->file('attachments', []));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['mail' => 'Xat yuborilmadi: ' . $e->getMessage()]);
        }

        return redirect()->route('dashboard.mail.index', ['folder' => 'sent'])
            ->with('success', "Xat {$data['to']} manziliga yuborildi.");
    }

    public function destroy(string $folder, int $uid)
    {
        $folder = $this->folder($folder);

        try {
            $this->mailbox->delete($folder, $uid);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['mail' => $e->getMessage()]);
        }

        Cache::forget('admin.nav.unread_mail');

        return redirect()->route('dashboard.mail.index', array_filter(['folder' => $folder === 'inbox' ? null : $folder]))
            ->with('success', 'Xat savatga ko‘chirildi.');
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    private function deliver(string $to, string $toName, string $subject, string $body, array $attachments = [], ?string $inReplyTo = null, ?string $references = null): void
    {
        $from = $this->mailbox->address();
        $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
        $html = view('admin.mail.email-body', ['body' => $body])->render();
        $swift = null;

        Mail::send([], [], function (Message $message) use ($from, $to, $toName, $subject, $body, $html, $attachments, $inReplyTo, $references, &$swift) {
            $message->from($from, $this->mailbox->fromName())
                ->to($to, $toName !== '' ? $toName : null)
                ->subject($subject);
            $message->setBody($html, 'text/html');
            $message->addPart($body, 'text/plain');

            foreach ($attachments as $file) {
                $message->attach($file->getRealPath(), [
                    'as' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                ]);
            }

            $headers = $message->getSwiftMessage()->getHeaders();
            if ($inReplyTo) {
                $headers->addTextHeader('In-Reply-To', $inReplyTo);
            }
            if ($references) {
                $headers->addTextHeader('References', $references);
            }

            $swift = $message->getSwiftMessage();
        });

        if ($swift) {
            $this->mailbox->appendToSent($swift->toString());
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{email: string, name: string}|null
     */
    private function replyRecipient(array $message): ?array
    {
        $own = $this->mailbox->address();
        foreach (array_merge($message['reply_to'], $message['from']) as $address) {
            if ($address['email'] !== $own && filter_var($address['email'], FILTER_VALIDATE_EMAIL)) {
                return $address;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $message */
    private function quote(array $message): string
    {
        $text = $message['text'] !== '' ? $message['text'] : $this->htmlToText($message['html']);
        $text = Str::limit(trim($text), 6000);
        $sender = $message['from'][0] ?? ['email' => '', 'name' => ''];
        $who = trim(($sender['name'] ? $sender['name'] . ' ' : '') . '<' . $sender['email'] . '>');
        $when = $message['date'] ? $message['date']->format('d.m.Y H:i') : '';

        return "{$when}, {$who} yozdi:\n" . preg_replace('/^/m', '> ', $text);
    }

    private function signature(): string
    {
        $phone = (string) config('seo.phone');
        if (preg_match('/^\+998(\d{2})(\d{3})(\d{2})(\d{2})$/', $phone, $m)) {
            $phone = "+998 {$m[1]} {$m[2]} {$m[3]} {$m[4]}";
        }

        return implode("\n", array_filter([
            '--',
            config('seo.site_name', 'NEO-LABS'),
            $phone,
            $this->mailbox->address(),
            rtrim((string) config('seo.base_url', 'https://neo-labs.uz'), '/'),
        ]));
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(br|/p|/div|/tr|/li|/h[1-6])\b[^>]*>#i', "\n", (string) $html);

        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function folder($folder): string
    {
        return in_array($folder, MailboxService::FOLDERS, true) ? $folder : 'inbox';
    }
}
