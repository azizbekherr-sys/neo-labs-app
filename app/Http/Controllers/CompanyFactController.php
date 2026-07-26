<?php

namespace App\Http\Controllers;

use App\Models\CompanyFact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyFactController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $fact = CompanyFact::where('key', $data['key'])->first();
        $oldDocument = $fact?->document_path;

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('company-facts', 'public');
        }
        $data['is_published'] = $request->boolean('is_published');
        $fact = CompanyFact::updateOrCreate(['key' => $data['key']], $data);

        if (isset($data['document_path']) && $oldDocument !== $fact->document_path) {
            $this->deleteDocument($oldDocument);
        }

        return back()->with('success', 'Kompaniya fakti saqlandi.');
    }

    public function update(Request $request, CompanyFact $companyFact)
    {
        $data = $this->validated($request, $companyFact);
        $oldDocument = $companyFact->document_path;

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('company-facts', 'public');
        }
        $data['is_published'] = $request->boolean('is_published');
        $companyFact->update($data);

        if (isset($data['document_path']) && $oldDocument !== $data['document_path']) {
            $this->deleteDocument($oldDocument);
        }

        return back()->with('success', 'Kompaniya fakti yangilandi.');
    }

    public function destroy(CompanyFact $companyFact)
    {
        $document = $companyFact->document_path;
        $companyFact->delete();
        $this->deleteDocument($document);

        return back()->with('success', 'Kompaniya fakti o‘chirildi.');
    }

    private function validated(Request $request, ?CompanyFact $companyFact = null): array
    {
        return $request->validate([
            'key' => ['required', 'alpha_dash', 'max:100', Rule::unique('company_facts', 'key')->ignore($companyFact?->id)],
            'label_uz' => ['required', 'string', 'max:255'],
            'label_ru' => ['required', 'string', 'max:255'],
            'label_en' => ['required', 'string', 'max:255'],
            'value_uz' => ['required', 'string'],
            'value_ru' => ['required', 'string'],
            'value_en' => ['required', 'string'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    private function deleteDocument(?string $path): void
    {
        if ($path && str_starts_with(ltrim($path, '/'), 'company-facts/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
