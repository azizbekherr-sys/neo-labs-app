<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('document')) {
            $data['document_path'] = media_store($request->file('document'), 'certificates');
        }
        $data['is_published'] = $request->boolean('is_published');
        Certificate::create($data);
        $this->clearCache();

        return back()->with('success', 'Sertifikat saqlandi.');
    }

    public function update(Request $request, Certificate $certificate)
    {
        $data = $this->validated($request);
        $oldDocument = $certificate->document_path;

        if ($request->hasFile('document')) {
            $data['document_path'] = media_store($request->file('document'), 'certificates');
        }
        $data['is_published'] = $request->boolean('is_published');
        $certificate->update($data);

        if (isset($data['document_path']) && $oldDocument !== $data['document_path']) {
            $this->deleteDocument($oldDocument);
        }
        $this->clearCache();

        return back()->with('success', 'Sertifikat yangilandi.');
    }

    public function destroy(Certificate $certificate)
    {
        $document = $certificate->document_path;
        $certificate->delete();
        $this->deleteDocument($document);
        $this->clearCache();

        return back()->with('success', 'Sertifikat o‘chirildi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name_uz' => ['required', 'string', 'max:255'],
            'name_ru' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:255'],
            'issuer_uz' => ['nullable', 'string', 'max:255'],
            'issuer_ru' => ['nullable', 'string', 'max:255'],
            'issuer_en' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'scope_uz' => ['nullable', 'string'],
            'scope_ru' => ['nullable', 'string'],
            'scope_en' => ['nullable', 'string'],
            'verification_url' => ['nullable', 'url', 'max:2048'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    private function deleteDocument(?string $path): void
    {
        media_delete($path, ['certificates/']);
    }

    private function clearCache(): void
    {
        Cache::forget('seo.certifications.ru');
        Cache::forget('seo.certifications.uz');
        Cache::forget('seo.certifications.en');
    }
}
