<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function store(Request $request)
    {
        // Support new repeater format `partners[][logo,url]` and legacy `logos[]`
        $useRepeater = is_array($request->input('partners'));

        if ($useRepeater) {
            $validated = $request->validate([
                'partners' => ['required', 'array'],
                'partners.*.logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:6144'],
                'partners.*.url' => ['nullable', 'string', 'max:2048', 'url'],
            ]);
        } else {
            $validated = $request->validate([
                'logos' => ['required', 'array'],
                'logos.*' => ['image', 'mimes:jpg,jpeg,png,webp,svg', 'max:6144'],
            ]);
        }

        if (!is_dir(public_path('partners'))) {
            mkdir(public_path('partners'), 0755, true);
        }

        if ($useRepeater) {
            foreach ($request->input('partners', []) as $idx => $row) {
                $file = $request->file("partners.$idx.logo");
                $url = $row['url'] ?? null;
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension();
                    $filename = uniqid('pr_') . '_' . time() . '.' . $ext;
                    $file->move(public_path('partners'), $filename);
                    Partner::create([
                        'path' => 'partners/' . $filename,
                        'url' => $url,
                    ]);
                }
            }
        } else {
            foreach ($request->file('logos', []) as $file) {
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension();
                    $filename = uniqid('pr_') . '_' . time() . '.' . $ext;
                    $file->move(public_path('partners'), $filename);
                    Partner::create(['path' => 'partners/' . $filename]);
                }
            }
        }

        return back()->with('success', 'Hamkorlar logolari yangilandi.');
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'url' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:6144'],
        ]);

        $oldPath = $partner->path;
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if (!is_dir(public_path('partners'))) {
                mkdir(public_path('partners'), 0755, true);
            }

            $file = $request->file('logo');
            $filename = uniqid('pr_') . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('partners'), $filename);
            $data['path'] = 'partners/' . $filename;
        }

        $data['url'] = $data['url'] ?? null;
        $partner->update($data);

        if (isset($data['path']) && $oldPath !== $data['path']) {
            $this->deleteLogoFiles($oldPath);
        }

        return back()->with('success', 'Hamkor ma’lumoti yangilandi.');
    }

    public function destroy(Partner $partner)
    {
        $this->deleteLogoFiles($partner->path);
        $partner->delete();

        return back()->with('success', 'Logo o‘chirildi.');
    }

    private function deleteLogoFiles(?string $relativePath): void
    {
        $relativePath = ltrim((string) $relativePath, '/');
        if ($relativePath === '' || !str_starts_with($relativePath, 'partners/')) {
            return;
        }

        $path = public_path($relativePath);
        if (is_file($path)) {
            @unlink($path);
        }

        $optimized = public_path('partners/optimized/' . pathinfo($relativePath, PATHINFO_FILENAME) . '.webp');
        if (is_file($optimized)) {
            @unlink($optimized);
        }
    }
}


