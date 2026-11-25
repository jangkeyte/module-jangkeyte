@extends('JangKeyte::layout.app')

@section('heading', __('Media Manager'))

@section('button')
    @can('add-media')
        <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#createFolderModal">
            <i class="bi bi-folder-plus me-1"></i> {{ __('New Folder') }}
        </button>
    @endcan
@endsection

@section('main_content')
    @php
        $breadcrumbs = $listing['breadcrumbs'] ?? [['name' => 'Root', 'path' => '']];
        $directories = $listing['directories'] ?? [];
        $files = $listing['files'] ?? [];
        $acceptString = collect($allowedExtensions)
            ->map(static fn ($ext): string => '.' . ltrim((string) $ext, '.'))
            ->implode(',');
        $parentPath = '';
        if ($currentFolder !== '') {
            $dirParent = dirname($currentFolder);
            $parentPath = ($dirParent === '.' || $dirParent === '/') ? '' : $dirParent;
        }
    @endphp

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        @foreach($breadcrumbs as $crumb)
                            <li class="breadcrumb-item @if($loop->last) active @endif" @if($loop->last) aria-current="page" @endif>
                                @if($loop->last)
                                    @if($crumb['path'] === '')
                                        <i class="bi bi-house-door me-1"></i>{{ __($crumb['name']) }}
                                    @else
                                        {{ $crumb['name'] }}
                                    @endif
                                @else
                                    <a href="{{ route('admin.media.index', $crumb['path'] !== '' ? ['folder' => $crumb['path']] : []) }}">
                                        @if($crumb['path'] === '')
                                            <i class="bi bi-house-door me-1"></i>{{ __($crumb['name']) }}
                                        @else
                                            {{ $crumb['name'] }}
                                        @endif
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <div class="d-flex align-items-center gap-2">
                    @if($currentFolder !== '')
                        <a href="{{ route('admin.media.index', $parentPath !== '' ? ['folder' => $parentPath] : []) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-up me-1"></i> {{ __('Up One Level') }}
                        </a>
                    @endif
                    <div class="input-group input-group-sm" style="width: 240px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="mediaSearchInput" class="form-control" placeholder="{{ __('Filter...') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('add-media')
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="POST" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    <input type="hidden" name="target_path" value="{{ $currentFolder }}">
                    <div class="flex-grow-1" style="min-width: 240px;">
                        <input type="file" name="files[]" id="mediaUploadFiles" class="form-control form-control-sm" multiple required @if($acceptString !== '') accept="{{ $acceptString }}" @endif>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-cloud-upload me-1"></i> {{ __('Upload Files') }}
                    </button>
                    <span class="text-muted small">
                        {{ implode(', ', $allowedExtensions) }} &middot; max {{ number_format($maxSizeKb / 1024, 1) }} MB
                    </span>
                </form>
            </div>
        </div>
    @endcan

    <div class="row g-3">
        <div class="col-lg-8 col-xl-9">
            @if(count($directories) > 0)
                <h6 class="text-uppercase text-muted small fw-semibold mb-2">{{ __('Folders') }}</h6>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3 mb-4">
                    @foreach($directories as $dir)
                        <div class="col js-media-item" data-media-name="{{ strtolower($dir['name']) }}">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3 position-relative">
                                    @can('delete-media')
                                        <form method="POST" action="{{ route('admin.media.delete') }}" class="position-absolute top-0 end-0 m-1" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="path" value="{{ $dir['relative_path'] }}">
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-1" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    <a href="{{ route('admin.media.index', ['folder' => $dir['relative_path']]) }}" class="text-decoration-none text-reset stretched-link-area w-100">
                                        <i class="bi bi-folder-fill text-warning fs-1 d-block mb-1"></i>
                                        <div class="fw-medium small text-truncate">{{ $dir['name'] }}</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($files) > 0)
                <h6 class="text-uppercase text-muted small fw-semibold mb-2">{{ __('Files') }}</h6>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                    @foreach($files as $file)
                        @php
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $iconClass = match ($ext) {
                                'pdf' => 'bi-file-earmark-pdf text-danger',
                                'doc', 'docx' => 'bi-file-earmark-word text-primary',
                                'xls', 'xlsx' => 'bi-file-earmark-excel text-success',
                                'zip' => 'bi-file-earmark-zip text-warning',
                                default => 'bi-file-earmark text-secondary',
                            };
                            $modifiedFormatted = !empty($file['modified']) ? date('Y-m-d H:i', (int) $file['modified']) : '';
                        @endphp
                        <div class="col js-media-item" data-media-name="{{ strtolower($file['name']) }}">
                            <div
                                class="card h-100 shadow-sm media-file-card js-media-file"
                                role="button"
                                tabindex="0"
                                data-name="{{ $file['name'] }}"
                                data-path="{{ $file['relative_path'] }}"
                                data-url="{{ $file['url'] }}"
                                data-size="{{ $file['size'] }}"
                                data-mime="{{ $file['mime'] ?? '' }}"
                                data-ext="{{ $ext }}"
                                data-is-image="{{ !empty($file['is_image']) ? '1' : '0' }}"
                                data-modified="{{ $modifiedFormatted }}"
                            >
                                <div class="bg-light d-flex align-items-center justify-content-center overflow-hidden rounded-top" style="height: 140px;">
                                    @if(!empty($file['is_image']))
                                        <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" class="h-100 w-100 object-fit-cover" loading="lazy">
                                    @else
                                        <i class="bi {{ $iconClass }} fs-1"></i>
                                    @endif
                                </div>
                                <div class="card-body p-2">
                                    <div class="text-truncate fw-medium small" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                                    <div class="text-muted small">{{ number_format($file['size'] / 1024, 1) }} KB</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($directories) === 0 && count($files) === 0)
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-images fs-1 d-block mb-2 opacity-50"></i>
                        <p class="mb-0">{{ __('No files or folders in this directory.') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4 col-xl-3">
            <div class="card position-sticky" style="top: 1rem;">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-1"></i> {{ __('Inspector') }}</h6>
                </div>
                <div class="card-body">
                    <div id="mediaInspectorEmpty" class="text-center text-muted py-4">
                        <i class="bi bi-cursor fs-2 d-block mb-2 opacity-50"></i>
                        <div class="small">{{ __('Select a file to preview and inspect details.') }}</div>
                    </div>

                    <div id="mediaInspectorBody" class="d-none">
                        <div class="border rounded bg-light d-flex align-items-center justify-content-center mb-3 overflow-hidden" style="height: 180px;">
                            <img id="mediaInspectorImg" src="" alt="" class="h-100 w-100 object-fit-contain d-none">
                            <i id="mediaInspectorIcon" class="bi bi-file-earmark fs-1 text-secondary d-none"></i>
                        </div>

                        <div class="mb-2">
                            <div id="mediaInspectorName" class="fw-semibold text-break"></div>
                            <div class="small text-muted"><code id="mediaInspectorPath"></code></div>
                        </div>

                        <div class="small text-muted mb-3">
                            <div><strong>{{ __('Size') }}:</strong> <span id="mediaInspectorSize"></span></div>
                            <div id="mediaInspectorMeta"></div>
                        </div>

                        <div class="mb-3">
                            <label for="mediaInspectorUrlInput" class="form-label small fw-semibold mb-1">URL</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="mediaInspectorUrlInput" class="form-control" readonly>
                                <button type="button" id="mediaCopyUrlBtn" class="btn btn-outline-primary">
                                    <i class="bi bi-clipboard"></i> {{ __('Copy URL') }}
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a id="mediaInspectorOpenLink" href="#" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('Open') }}
                            </a>

                            @can('delete-media')
                                <form method="POST" action="{{ route('admin.media.delete') }}" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="path" id="mediaInspectorDeletePath" value="">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash me-1"></i> {{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('add-media')
        <div class="modal fade" id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.media.folder') }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="parent_path" value="{{ $currentFolder }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createFolderModalLabel">{{ __('New Folder') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="mediaFolderName" class="form-label">{{ __('Folder Name') }} *</label>
                            <input type="text" name="folder_name" id="mediaFolderName" class="form-control" required maxlength="100" placeholder="e.g. campaign-2026">
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
        const searchInput = document.getElementById('mediaSearchInput');
        const fileCards = document.querySelectorAll('.js-media-file');
        const emptyPanel = document.getElementById('mediaInspectorEmpty');
        const bodyPanel = document.getElementById('mediaInspectorBody');
        const imgEl = document.getElementById('mediaInspectorImg');
        const iconEl = document.getElementById('mediaInspectorIcon');
        const nameEl = document.getElementById('mediaInspectorName');
        const pathEl = document.getElementById('mediaInspectorPath');
        const sizeEl = document.getElementById('mediaInspectorSize');
        const metaEl = document.getElementById('mediaInspectorMeta');
        const urlInput = document.getElementById('mediaInspectorUrlInput');
        const copyBtn = document.getElementById('mediaCopyUrlBtn');
        const openLink = document.getElementById('mediaInspectorOpenLink');
        const deletePathInput = document.getElementById('mediaInspectorDeletePath');

        function selectCard(card) {
            fileCards.forEach(function (item) {
                item.classList.remove('border-primary', 'shadow');
            });
            card.classList.add('border-primary', 'shadow');

            const isImage = card.dataset.isImage === '1';
            const url = card.dataset.url || '';
            const name = card.dataset.name || '';
            const relPath = card.dataset.path || '';
            const sizeBytes = parseInt(card.dataset.size || '0', 10);
            const mime = card.dataset.mime || '';
            const modified = card.dataset.modified || '';

            if (emptyPanel) emptyPanel.classList.add('d-none');
            if (bodyPanel) bodyPanel.classList.remove('d-none');

            if (isImage && imgEl && iconEl) {
                imgEl.src = url;
                imgEl.alt = name;
                imgEl.classList.remove('d-none');
                iconEl.classList.add('d-none');
            } else if (imgEl && iconEl) {
                imgEl.src = '';
                imgEl.classList.add('d-none');
                iconEl.classList.remove('d-none');
            }

            if (nameEl) nameEl.textContent = name;
            if (pathEl) pathEl.textContent = relPath;
            if (sizeEl) sizeEl.textContent = (sizeBytes / 1024).toFixed(1) + ' KB';
            if (metaEl) metaEl.textContent = [mime, modified].filter(Boolean).join(' · ');
            if (urlInput) urlInput.value = url;
            if (openLink) openLink.href = url;
            if (deletePathInput) deletePathInput.value = relPath;
        }

        fileCards.forEach(function (card) {
            card.addEventListener('click', function () {
                selectCard(card);
            });
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectCard(card);
                }
            });
        });

        if (copyBtn && urlInput) {
            copyBtn.addEventListener('click', function () {
                const value = urlInput.value;
                if (!value) return;

                const notifyCopied = function () {
                    if (typeof iziToast !== 'undefined') {
                        iziToast.success({
                            position: 'topRight',
                            message: @json(__('URL copied to clipboard.')),
                        });
                    }
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(notifyCopied).catch(function () {
                        urlInput.select();
                        document.execCommand('copy');
                        notifyCopied();
                    });
                } else {
                    urlInput.select();
                    document.execCommand('copy');
                    notifyCopied();
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                document.querySelectorAll('.js-media-item').forEach(function (item) {
                    const itemName = item.dataset.mediaName || '';
                    item.classList.toggle('d-none', term !== '' && !itemName.includes(term));
                });
            });
        }
    });
</script>
@endpush
