<?php

namespace JangKeyte\Http\Controllers\Setting;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use JangKeyte\Models\Setting;
use JangKeyte\Services\FileUploadService;
use JangKeyte\Services\SettingService;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly FileUploadService $fileUploadService
    ) {
    }

    public function index(): View
    {
        $groupedSettings = $this->settingService->allGrouped();

        return view('JangKeyte::setting.index', compact('groupedSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        $rawInputs = $request->input('settings', []);
        $inputs = is_array($rawInputs) ? $rawInputs : [];

        $settingsFiles = $request->file('settings_files', []);
        $settingFiles = $request->file('setting_files', []);
        $inlineFiles = $request->file('settings', []);

        $files = array_merge(
            is_array($settingsFiles) ? $settingsFiles : [],
            is_array($settingFiles) ? $settingFiles : [],
            is_array($inlineFiles) ? $inlineFiles : [],
        );

        $settings = Setting::query()->get();

        foreach ($settings as $setting) {
            if ($setting->type === 'image') {
                $file = $files[$setting->key] ?? null;
                if ($file instanceof UploadedFile) {
                    Validator::make(
                        ['file' => $file],
                        ['file' => ['file', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:5120']]
                    )->validate();
                }
            }
        }

        DB::transaction(function () use ($settings, $inputs, $files): void {
            foreach ($settings as $setting) {
                if ($setting->type === 'image') {
                    $file = $files[$setting->key] ?? null;
                    if ($file instanceof UploadedFile) {
                        $filename = $this->fileUploadService->uploadPublic($file, 'settings', null);
                        $setting->value = 'settings/' . $filename;
                        $setting->save();
                    }

                    continue;
                }

                if ($setting->type === 'boolean') {
                    if (array_key_exists($setting->key, $inputs)) {
                        $setting->value = filter_var($inputs[$setting->key], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                        $setting->save();
                    }

                    continue;
                }

                if (array_key_exists($setting->key, $inputs)) {
                    $setting->value = $inputs[$setting->key] === null ? null : (string) $inputs[$setting->key];
                    $setting->save();
                }
            }
        });

        $this->settingService->forgetCache();

        return redirect()->route('admin.settings.index')
            ->with('success', __('Settings updated successfully.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:191', 'regex:/^[a-z][a-z0-9_.]*$/', Rule::unique('settings', 'key')],
            'display_name' => ['required', 'string', 'max:191'],
            'group' => ['required', 'string', Rule::in(['general', 'branding', 'contact'])],
            'type' => ['required', 'string', Rule::in(['text', 'textarea', 'image', 'boolean'])],
            'value' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $value = $validated['value'] ?? ($validated['type'] === 'boolean' ? '0' : null);

        Setting::create([
            'key' => $validated['key'],
            'display_name' => $validated['display_name'],
            'group' => $validated['group'],
            'type' => $validated['type'],
            'value' => $value,
            'order' => (int) ($validated['order'] ?? 0),
        ]);

        $this->settingService->forgetCache();

        return redirect()->route('admin.settings.index')
            ->with('success', __('Custom setting created successfully.'));
    }
}
