<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;

/**
 * Admin page for Telegram notifications: bot token (stored encrypted), chat IDs,
 * which events to forward, chat discovery and a test message.
 */
class AdminTelegramController extends Controller
{
    public function __construct(private TelegramNotifier $telegram)
    {
    }

    public function index()
    {
        $token = $this->telegram->token();

        return view('admin.telegram.index', [
            'hasToken' => $token !== null,
            'tokenHint' => $token ? '…' . substr($token, -4) : null,
            'tokenFromEnv' => $token !== null && SiteSetting::getSecret('telegram_bot_token') === null,
            'chatIds' => implode(', ', $this->telegram->chatIds()),
            'notifyForms' => $this->telegram->enabled('forms'),
            'notifyMail' => $this->telegram->enabled('mail'),
            'bot' => $token ? $this->telegram->check() : null,
            'chats' => session('telegram_chats'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'bot_token' => ['nullable', 'string', 'max:120', 'regex:/^\d{5,}:[A-Za-z0-9_-]{20,}$/'],
            'chat_id' => ['nullable', 'string', 'max:300', 'regex:/^[-0-9@A-Za-z_,;\s]*$/'],
            'notify_forms' => ['nullable', 'boolean'],
            'notify_mail' => ['nullable', 'boolean'],
            'clear_token' => ['nullable', 'boolean'],
        ], [
            'bot_token.regex' => 'Token formati noto‘g‘ri. U 123456789:AA... ko‘rinishida bo‘lishi kerak.',
            'chat_id.regex' => 'Chat ID faqat raqam, minus belgisi yoki @kanal nomidan iborat bo‘lishi kerak.',
        ]);

        $newToken = trim((string) ($data['bot_token'] ?? ''));
        if ($newToken !== '') {
            $check = $this->telegram->check($newToken);
            if (!$check['ok']) {
                return back()->withErrors(['bot_token' => 'Telegram bu tokenni qabul qilmadi: ' . $check['error']]);
            }
            SiteSetting::putSecret('telegram_bot_token', $newToken);
        } elseif ($request->boolean('clear_token')) {
            SiteSetting::putSecret('telegram_bot_token', null);
        }

        SiteSetting::putValue('telegram_chat_id', trim((string) ($data['chat_id'] ?? '')) ?: null);
        SiteSetting::putValue('telegram_notify_forms', $request->boolean('notify_forms') ? '1' : '0');
        SiteSetting::putValue('telegram_notify_mail', $request->boolean('notify_mail') ? '1' : '0');

        return redirect()->route('dashboard.telegram.index')->with('success', 'Telegram sozlamalari saqlandi.');
    }

    public function discover()
    {
        $result = $this->telegram->discoverChats();
        if (!$result['ok']) {
            return back()->withErrors(['telegram' => 'Chatlarni olishning iloji bo‘lmadi: ' . $result['error']]);
        }

        if (!$result['chats']) {
            return back()->withErrors(['telegram' => 'Hali hech kim botga yozmagan. Telegram’da botni oching, /start bosing (yoki botni guruhga qo‘shib, guruhda biror xabar yozing), keyin qaytadan urinib ko‘ring.']);
        }

        return back()->with('telegram_chats', $result['chats']);
    }

    public function test()
    {
        if (!$this->telegram->configured()) {
            return back()->withErrors(['telegram' => 'Avval bot tokeni va kamida bitta chat ID ni saqlang.']);
        }

        $sent = $this->telegram->send("✅ <b>NEO-LABS</b>: Telegram bildirishnomalari ishlayapti.\nSaytdagi formalar va info@neo-labs.uz pochtasiga kelgan xatlar shu yerga yuboriladi.");

        return $sent
            ? back()->with('success', 'Test xabar yuborildi. Telegram’ni tekshiring.')
            : back()->withErrors(['telegram' => 'Test xabar yuborilmadi. Chat ID to‘g‘riligini va bot shu chatga qo‘shilganini tekshiring.']);
    }
}
