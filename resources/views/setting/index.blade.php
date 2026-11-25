@extends('JangKeyte::layout.app')

@section('heading', __('Settings'))

@section('button')
    @can('add-setting')
        <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#addSettingModal">
            <i class="bi bi-plus-lg"></i> {{ __('Add Custom Setting') }}
        </button>
    @endcan
@endsection

@section('main_content')
    @php
        $uploadPrefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
        $groupLabels = [
            'general' => ['label' => __('General'), 'icon' => 'bi bi-sliders'],
            'branding' => ['label' => __('Branding'), 'icon' => 'bi bi-palette'],
            'contact' => ['label' => __('Contact'), 'icon' => 'bi bi-telephone'],
        ];
        $groupKeys = $groupedSettings->keys()->values();
        $oldSettings = old('settings', []);
    @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTab" role="tablist">
                    @foreach($groupKeys as $group)
                        @php $meta = $groupLabels[$group] ?? ['label' => \Illuminate\Support\Str::headline($group), 'icon' => 'bi bi-gear']; @endphp
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link @if($loop->first) active @endif"
                                id="tab-{{ $group }}-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-{{ $group }}"
                                type="button"
                                role="tab"
                                aria-controls="tab-{{ $group }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <i class="{{ $meta['icon'] }} me-1"></i> {{ $meta['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="settingsTabContent">
                    @forelse($groupedSettings as $group => $items)
                        <div
                            class="tab-pane fade @if($loop->first) show active @endif"
                            id="tab-{{ $group }}"
                            role="tabpanel"
                            aria-labelledby="tab-{{ $group }}-btn"
                            tabindex="0"
                        >
                            @foreach($items as $setting)
                                @php
                                    $fieldId = 'setting_' . $setting->id;
                                    $currentValue = is_array($oldSettings) && array_key_exists($setting->key, $oldSettings)
                                        ? $oldSettings[$setting->key]
                                        : $setting->value;
                                @endphp
                                <div class="row mb-3 align-items-start border-bottom pb-3">
                                    <label class="col-sm-3 col-form-label" for="{{ $fieldId }}">
                                        <span class="fw-semibold">{{ $setting->display_name }}</span>
                                        <div class="small text-muted"><code>setting('{{ $setting->key }}')</code></div>
                                    </label>

                                    <div class="col-sm-9">
                                        @if($setting->type === 'textarea')
                                            <textarea id="{{ $fieldId }}" name="settings[{{ $setting->key }}]" rows="3" class="form-control">{{ $currentValue }}</textarea>
                                        @elseif($setting->type === 'boolean')
                                            <div class="form-check form-switch mt-2">
                                                <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="{{ $fieldId }}" name="settings[{{ $setting->key }}]" value="1" @checked((string) $currentValue === '1')>
                                                <label class="form-check-label" for="{{ $fieldId }}">{{ __('Enabled') }}</label>
                                            </div>
                                        @elseif($setting->type === 'image')
                                            @php
                                                $imgUrl = !empty($setting->value)
                                                    ? (str_starts_with($setting->value, 'http') || str_starts_with($setting->value, '/')
                                                        ? $setting->value
                                                        : asset($uploadPrefix . '/' . ltrim($setting->value, '/')))
                                                    : null;
                                            @endphp
                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                <img id="{{ $fieldId }}_preview" src="{{ $imgUrl ?? asset('assets/images/logo.png') }}" alt="{{ $setting->display_name }}" class="border rounded p-1 bg-light {{ $imgUrl ? '' : 'opacity-50' }}" style="max-height: 64px; max-width: 160px; object-fit: contain;">
                                                <div class="flex-grow-1">
                                                    <input type="file" id="{{ $fieldId }}" name="settings_files[{{ $setting->key }}]" accept="image/*" class="form-control js-setting-image-input" data-preview-target="{{ $fieldId }}_preview">
                                                    @if(!empty($setting->value))
                                                        <div class="form-text"><code>{{ $setting->value }}</code></div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <input type="text" id="{{ $fieldId }}" name="settings[{{ $setting->key }}]" value="{{ $currentValue }}" class="form-control">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ __('No settings configured yet.') }}</p>
                    @endforelse
                </div>
            </div>

            @can('edit-setting')
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('Save Settings') }}
                    </button>
                </div>
            @endcan
        </div>
    </form>

    @can('add-setting')
        <div class="modal fade" id="addSettingModal" tabindex="-1" aria-labelledby="addSettingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.settings.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSettingModalLabel">{{ __('Add Custom Setting') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_setting_key" class="form-label">{{ __('Key') }} *</label>
                            <input type="text" id="new_setting_key" name="key" value="{{ old('key') }}" class="form-control @error('key') is-invalid @enderror" placeholder="e.g. seo.meta_keywords" required>
                            @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="new_setting_display_name" class="form-label">{{ __('Display Name') }} *</label>
                            <input type="text" id="new_setting_display_name" name="display_name" value="{{ old('display_name') }}" class="form-control @error('display_name') is-invalid @enderror" placeholder="e.g. Meta Keywords" required>
                            @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="new_setting_group" class="form-label">{{ __('Group') }} *</label>
                                <select id="new_setting_group" name="group" class="form-select @error('group') is-invalid @enderror" required>
                                    <option value="general" @selected(old('group') === 'general')>{{ __('General') }}</option>
                                    <option value="branding" @selected(old('group') === 'branding')>{{ __('Branding') }}</option>
                                    <option value="contact" @selected(old('group') === 'contact')>{{ __('Contact') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="new_setting_type" class="form-label">{{ __('Type') }} *</label>
                                <select id="new_setting_type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="text" @selected(old('type') === 'text')>Text</option>
                                    <option value="textarea" @selected(old('type') === 'textarea')>Textarea</option>
                                    <option value="image" @selected(old('type') === 'image')>Image</option>
                                    <option value="boolean" @selected(old('type') === 'boolean')>Boolean (Switch)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-setting-image-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const targetId = this.getAttribute('data-preview-target');
                const preview = targetId ? document.getElementById(targetId) : null;
                if (!preview || !this.files || !this.files[0]) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('opacity-50');
                };
                reader.readAsDataURL(this.files[0]);
            });
        });
    });
</script>
@endpush
