<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Services\MailboxService;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;
use Throwable;

/**
 * Forward newly arrived mail in the company mailbox to Telegram.
 * Runs every minute from the scheduler; remembers the last forwarded UID.
 */
class MailboxTelegramNotify extends Command
{
    protected $signature = 'mailbox:telegram {--init : Only remember the newest message, send nothing}';

    protected $description = 'Send new info@ mailbox messages to Telegram';

    private const MAX_PER_RUN = 10;

    public function handle(MailboxService $mailbox, TelegramNotifier $telegram): int
    {
        if (!$mailbox->configured()) {
            return self::SUCCESS;
        }

        try {
            $state = $mailbox->inboxState();
        } catch (Throwable $e) {
            report($e);

            return self::FAILURE;
        }

        $newest = $state['uids'] ? max($state['uids']) : 0;
        $lastUid = SiteSetting::getValue('mailbox_tg_last_uid');
        $validity = (int) SiteSetting::getValue('mailbox_tg_uidvalidity', '0');

        // First run, explicit init, or the mailbox was rebuilt: start from "now" without flooding the chat.
        if ($this->option('init') || $lastUid === null || $validity !== $state['validity']) {
            $this->remember($newest, $state['validity']);
            $this->info("Initialised at UID {$newest}.");

            return self::SUCCESS;
        }

        $pending = array_values(array_filter($state['uids'], fn (int $uid) => $uid > (int) $lastUid));
        sort($pending);

        // Not set up or switched off: keep the pointer moving so nothing old is replayed later.
        if (!$telegram->configured() || !$telegram->enabled('mail')) {
            if ($pending) {
                $this->remember(max($pending), $state['validity']);
            }

            return self::SUCCESS;
        }

        foreach (array_slice($pending, 0, self::MAX_PER_RUN) as $uid) {
            $message = $mailbox->get('inbox', $uid, false);
            $sender = $message['from'][0]['email'] ?? '';

            if ($message && $sender !== $mailbox->address() && !$telegram->send($this->format($message, $mailbox))) {
                $this->warn("Telegram did not accept UID {$uid}; will retry next run.");

                return self::FAILURE;
            }

            $this->remember($uid, $state['validity']);
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $message */
    private function format(array $message, MailboxService $mailbox): string
    {
        $e = [TelegramNotifier::class, 'e'];
        $sender = $message['from'][0] ?? ['email' => '', 'name' => ''];
        $who = $sender['name'] !== '' ? $sender['name'] . ' <' . $sender['email'] . '>' : $sender['email'];
        $snippet = $mailbox->snippet($message, 700);
        $files = array_map(fn ($file) => $file['name'], $message['attachments']);
        $link = rtrim((string) config('seo.base_url', config('app.url')), '/') . '/dashboard/mail/inbox/' . $message['uid'];

        $lines = [
            '📧 <b>' . $e($mailbox->address()) . ' pochtasiga yangi xat</b>',
            '👤 Kimdan: ' . $e($who),
            '📝 Mavzu: ' . $e($message['subject']),
        ];
        if ($message['date']) {
            $lines[] = '🕒 ' . $message['date']->format('d.m.Y H:i');
        }
        if ($snippet !== '') {
            $lines[] = '';
            $lines[] = $e($snippet);
        }
        if ($files) {
            $lines[] = '';
            $lines[] = '📎 Ilovalar: ' . $e(implode(', ', array_slice($files, 0, 10)));
        }
        $lines[] = '';
        $lines[] = '🔗 <a href="' . $e($link) . '">Admin panelda ochish va javob yozish</a>';

        return implode("\n", $lines);
    }

    private function remember(int $uid, int $validity): void
    {
        SiteSetting::putValue('mailbox_tg_last_uid', (string) $uid);
        SiteSetting::putValue('mailbox_tg_uidvalidity', (string) $validity);
    }
}
