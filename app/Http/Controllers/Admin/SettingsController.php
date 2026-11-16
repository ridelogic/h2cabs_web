<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageStore;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Website settings: contact details, WhatsApp number, app and social links,
 * and the logo mark. Saved values override .env; blank values fall back to it.
 */
class SettingsController extends Controller
{
    private const LOGO_KEY = 'brand.logo_mark';

    public function edit(): View
    {
        $saved = Setting::values();

        return view('admin.settings', [
            'groups' => collect(SiteSettings::definitions())
                ->map(fn (array $definition, string $key) => [
                    ...$definition,
                    'key' => $key,
                    'field' => str_replace('.', '__', $key),
                    'saved' => $saved[$key] ?? null,
                    'default' => SiteSettings::envDefault($key),
                ])
                ->groupBy('group'),
            'customLogo' => $saved[self::LOGO_KEY] ?? null,
            'storageLinked' => is_dir(public_path('storage')),
        ]);
    }

    public function update(Request $request, ImageStore $images): RedirectResponse
    {
        $definitions = SiteSettings::definitions();

        // Form field names use "__" for dots so Laravel does not nest them.
        $rules = collect($definitions)->mapWithKeys(fn (array $d, string $key) => [str_replace('.', '__', $key) => $d['rules']])->all();
        $rules['logo'] = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000'];
        $rules['remove_logo'] = ['nullable', 'boolean'];

        $validated = $request->validate($rules, [
            'logo.mimes' => 'The logo must be a PNG, JPG or WebP image (SVG uploads are not accepted).',
            '*.regex' => 'Enter a phone number using digits, spaces, + or -.',
        ], collect($definitions)->mapWithKeys(fn (array $d, string $key) => [str_replace('.', '__', $key) => $d['label']])->all());

        foreach (array_keys($definitions) as $key) {
            Setting::put($key, $validated[str_replace('.', '__', $key)] ?? null);
        }

        $oldLogo = Setting::values()[self::LOGO_KEY] ?? null;

        if ($request->hasFile('logo')) {
            $path = $images->store($request->file('logo'), 'branding');
            Setting::put(self::LOGO_KEY, 'storage/'.$path);
        } elseif ($request->boolean('remove_logo')) {
            Setting::put(self::LOGO_KEY, null);
        }

        $newLogo = Setting::values()[self::LOGO_KEY] ?? null;
        if ($oldLogo && $oldLogo !== $newLogo && str_starts_with($oldLogo, 'storage/branding/')) {
            Storage::disk(ImageStore::DISK)->delete(substr($oldLogo, strlen('storage/')));
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved. They are live on the website now.');
    }
}
