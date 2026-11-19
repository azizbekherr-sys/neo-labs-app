<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function send(Request $request, TelegramNotifier $telegram)
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

        if (!$telegram->configured() || !$telegram->enabled('forms')) {
            $contactMessage->update(['delivery_status' => 'pending']);

            return back()->with('contact_ok', __('site.contacts.success'));
        }

        $e = [TelegramNotifier::class, 'e'];
        $lines = [
            $isManufacturingRequest ? '🏭 <b>Saytdan kontrakt ishlab chiqarish so‘rovi</b>' : '🆕 <b>Saytdan yangi murojaat</b>',
            '👤 Ism: ' . $e($validated['name']),
        ];
        if (!empty($validated['company'])) {
            $lines[] = '🏢 Kompaniya: ' . $e($validated['company']);
        }
        if (!empty($validated['phone'])) {
            $lines[] = '📞 Telefon: ' . $e($validated['phone']);
        }
        if (!empty($validated['contact']) && $validated['contact'] !== ($validated['phone'] ?? null)) {
            $lines[] = '✉️ Aloqa: ' . $e($validated['contact']);
        }
        if (!empty($validated['product_type'])) {
            $lines[] = '📦 Mahsulot turi: ' . $e($validated['product_type']);
        }
        $lines[] = '';
        $lines[] = '💬 ' . $e(mb_substr($validated['message'], 0, 1600));
        $lines[] = '';
        if ($contactMessage->source_url) {
            $lines[] = '🌐 Sahifa: ' . $e($contactMessage->source_url);
        }
        $lines[] = '🔗 <a href="' . $e(rtrim((string) config('seo.base_url', config('app.url')), '/') . '/dashboard/messages') . '">Admin paneldagi murojaatlar</a>';

        $sent = $telegram->send(implode("\n", $lines));
        $contactMessage->update($sent
            ? ['delivery_status' => 'sent', 'telegram_sent_at' => now()]
            : ['delivery_status' => 'failed']);

        return back()->with('contact_ok', __('site.contacts.success'));
    }
}


