<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends notifications to Telegram. The bot token and chat IDs are entered by the
 * admin in the panel (stored encrypted); TELEGRAM_* env values remain a fallback.
 */
class TelegramNotifier
{
    public const CHANNELS = ['forms', 'mail'];

    public function token(): ?string
    {
        return SiteSetting::getSecret('telegram_bot_token') ?: (config('services.telegram.bot_token') ?: null);
    }

    /** @return array<int, string> */
    public function chatIds(): array
    {
        $raw = SiteSetting::getValue('telegram_chat_id') ?: (string) config('services.telegram.chat_id');

        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw) ?: []))));
    }

    public function configured(): bool
    {
        return $this->token() !== null && $this->chatIds() !== [];
    }

    /** Whether a notification channel (forms|mail) is switched on in the panel. */
    public function enabled(string $channel): bool
    {
        return SiteSetting::getValue('telegram_notify_' . $channel, '1') !== '0';
    }

    /** Send an HTML-formatted message to every configured chat. */
    public function send(string $html, ?string $onlyChat = null): bool
    {
        $token = $this->token();
        $chats = $onlyChat !== null ? [$onlyChat] : $this->chatIds();
        if (!$token || !$chats) {
            return false;
        }

        // Telegram allows 4096 characters; never cut HTML mid-tag — fall back to plain text instead.
        $parseMode = 'HTML';
        $text = $html;
        if (mb_strlen($html) > 3900) {
            $parseMode = null;
            $text = Str::limit(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 3900, '…');
        }

        $allSent = true;
        foreach ($chats as $chat) {
            $allSent = $this->sendTo($token, $chat, $text, $parseMode) && $allSent;
        }

        return $allSent;
    }

    /**
     * Validate a token with getMe.
     *
     * @return array{ok: bool, username?: string, error?: string}
     */
    public function check(?string $token = null): array
    {
        $token = $token ?: $this->token();
        if (!$token) {
            return ['ok' => false, 'error' => 'Bot tokeni kiritilmagan.'];
        }

        try {
            $response = Http::timeout(10)->get($this->url($token, 'getMe'));
            if ($response->ok() && $response->json('ok')) {
                return ['ok' => true, 'username' => (string) $response->json('result.username')];
            }

            return ['ok' => false, 'error' => (string) ($response->json('description') ?: 'HTTP ' . $response->status())];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $this->scrub($e->getMessage(), $token)];
        }
    }

    /**
     * Chats that recently wrote to the bot (or added it to a group), to pick a chat ID.
     *
     * @return array{ok: bool, chats: array<int, array{id: string, type: string, title: string}>, error?: string}
     */
    public function discoverChats(): array
    {
        $token = $this->token();
        if (!$token) {
            return ['ok' => false, 'chats' => [], 'error' => 'Avval bot tokenini saqlang.'];
        }

        try {
            $response = Http::timeout(10)->get($this->url($token, 'getUpdates'), ['limit' => 100]);
            if (!$response->ok() || !$response->json('ok')) {
                return ['ok' => false, 'chats' => [], 'error' => (string) ($response->json('description') ?: 'HTTP ' . $response->status())];
            }

            $chats = [];
            foreach ((array) $response->json('result') as $update) {
                foreach (['message', 'edited_message', 'channel_post', 'my_chat_member', 'chat_member'] as $field) {
                    $chat = $update[$field]['chat'] ?? null;
                    if (!$chat || !isset($chat['id'])) {
                        continue;
                    }
                    $title = $chat['title'] ?? trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));
                    if (!empty($chat['username'])) {
                        $title = trim($title . ' (@' . $chat['username'] . ')');
                    }
                    $chats[(string) $chat['id']] = [
                        'id' => (string) $chat['id'],
                        'type' => (string) ($chat['type'] ?? ''),
                        'title' => $title !== '' ? $title : (string) $chat['id'],
                    ];
                }
            }

            return ['ok' => true, 'chats' => array_values($chats)];
        } catch (Throwable $e) {
            return ['ok' => false, 'chats' => [], 'error' => $this->scrub($e->getMessage(), $token)];
        }
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function sendTo(string $token, string $chat, string $text, ?string $parseMode): bool
    {
        $payload = array_filter(['chat_id' => $chat, 'text' => $text, 'parse_mode' => $parseMode, 'disable_web_page_preview' => true], fn ($v) => $v !== null);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(12)->asForm()->post($this->url($token, 'sendMessage'), $payload);
                if ($response->ok()) {
                    return true;
                }

                $description = (string) ($response->json('description') ?? '');
                // Groups converted to supergroups need the -100 prefix.
                if ($response->status() === 400 && str_contains($description, 'chat not found') && preg_match('/^-(\d+)$/', $chat, $m) && !str_starts_with($chat, '-100')) {
                    $retry = Http::timeout(12)->asForm()->post($this->url($token, 'sendMessage'), ['chat_id' => '-100' . $m[1]] + $payload);
                    if ($retry->ok()) {
                        return true;
                    }
                }

                Log::warning('Telegram send failed', ['chat' => $chat, 'status' => $response->status(), 'description' => $description]);

                return false;
            } catch (ConnectionException $e) {
                if ($attempt === 2) {
                    Log::warning('Telegram connection failed', ['chat' => $chat, 'error' => $this->scrub($e->getMessage(), $token)]);
                }
                usleep(400000);
            } catch (Throwable $e) {
                Log::warning('Telegram send exception', ['chat' => $chat, 'error' => $this->scrub($e->getMessage(), $token)]);

                return false;
            }
        }

        return false;
    }

    private function url(string $token, string $method): string
    {
        return "https://api.telegram.org/bot{$token}/{$method}";
    }

    private function scrub(string $message, string $token): string
    {
        return str_replace($token, '***', $message);
    }
}
