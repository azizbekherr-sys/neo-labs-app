<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $isManufacturingRequest = $request->input('form_context') === 'manufacturing';

        $validated = $request->validate([
            'form_context' => 'nullable|in:manufacturing,general',
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => ($isManufacturingRequest ? 'nullable' : 'required_without:contact') . '|string|max:255',
            'contact' => [
                $isManufacturingRequest ? 'required' : 'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                    $isPhone = preg_match('/^\+?[0-9\s()\-]{7,25}$/u', (string) $value) === 1;
                    if (!$isEmail && !$isPhone) {
                        $fail(__('manufacturing.form.contact_invalid'));
                    }
                },
            ],
            'product_type' => ($isManufacturingRequest ? 'required' : 'nullable') . '|string|max:255',
            'message' => ($isManufacturingRequest ? 'required|string|min:10' : 'required|string') . '|max:2000',
            'website' => 'nullable|string|max:0',
        ]);

        $contactMessage = ContactMessage::create([
            'context' => $isManufacturingRequest ? 'manufacturing' : 'general',
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'contact' => $validated['contact'] ?? null,
            'product_type' => $validated['product_type'] ?? null,
            'message' => $validated['message'],
            'locale' => in_array(app()->getLocale(), ['uz', 'ru', 'en'], true) ? app()->getLocale() : null,
            'status' => 'new',
            'delivery_status' => 'pending',
            'source_url' => mb_substr((string) url()->previous(), 0, 2048),
            'ip_address' => $request->ip(),
        ]);

        $token = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $chatId = config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            return back()->with('contact_ok', __('site.contacts.success'));
        }

        $contact = $validated['contact'] ?? $validated['phone'] ?? '';
        $text = "🆕 Yangi aloqa xabari:\n"
            . "👤 Ism: {$validated['name']}\n"
            . (!empty($validated['company']) ? "🏢 Kompaniya: {$validated['company']}\n" : '')
            . "📞 Aloqa: {$contact}\n"
            . (!empty($validated['product_type']) ? "📦 Mahsulot turi: {$validated['product_type']}\n" : '')
            . "💬 Xabar: " . mb_substr($validated['message'], 0, 1600)
            . "\n🌐 Sayt: " . $request->getHost();

        try {
            $response = Http::timeout(12)
                ->retry(2, 300)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);
            if (!$response->ok()) {
                $status = $response->status();
                $body = null;
                try { $body = $response->json(); } catch (\Throwable $e) {}

                // Fallback: many private/supergroup chats require -100 prefix
                // If "chat not found", try reformatting chat_id as -100XXXXXXXXXX
                if ($status === 400 && is_array($body) && isset($body['description']) && str_contains((string)$body['description'], 'chat not found')) {
                    $numeric = preg_replace('/[^0-9\\-]/', '', (string)$chatId);
                    if (is_string($numeric) && strlen($numeric) > 1 && $numeric[0] === '-') {
                        $abs = ltrim($numeric, '-');
                        $altChatId = '-100' . $abs;
                        $retry = Http::timeout(12)
                            ->asForm()
                            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                                'chat_id' => $altChatId,
                                'text' => $text,
                                'disable_web_page_preview' => true,
                            ]);
                        if ($retry->ok()) {
                            $contactMessage->update([
                                'delivery_status' => 'sent',
                                'telegram_sent_at' => now(),
                            ]);
                            return back()->with('contact_ok', __('site.contacts.success'));
                        }
                        try {
                            Log::error('Telegram retry with -100 failed', ['status' => $retry->status(), 'body' => $retry->body()]);
                        } catch (\Throwable $e) {}
                    }
                }
                try {
                    Log::error('Telegram send failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                } catch (\Throwable $e) {
                    // ignore logging error
                }
                $contactMessage->update(['delivery_status' => 'failed']);
                return back()->with('contact_ok', __('site.contacts.success'));
            }
        } catch (\Throwable $e) {
            try {
                Log::error('Telegram send exception', ['error' => $e->getMessage()]);
            } catch (\Throwable $e2) {
                // ignore logging error
            }
            $contactMessage->update(['delivery_status' => 'failed']);
            return back()->with('contact_ok', __('site.contacts.success'));
        }

        $contactMessage->update([
            'delivery_status' => 'sent',
            'telegram_sent_at' => now(),
        ]);
        return back()->with('contact_ok', __('site.contacts.success'));
    }
}


