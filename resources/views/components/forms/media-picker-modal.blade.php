@props([
    'id' => 'jangkeyteMediaPickerModal',
    'title' => null,
    'initialFolder' => '',
    'accept' => 'all',
])

<div
    class="modal fade js-media-picker-modal"
    id="{{ $id }}"
    tabindex="-1"
    aria-labelledby="{{ $id }}Label"
    aria-hidden="true"
    data-index-url="{{ route('admin.media.index') }}"
    data-upload-url="{{ route('admin.media.upload') }}"
    data-initial-folder="{{ $initialFolder }}"
    data-default-accept="{{ $accept }}"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">
                    <i class="bi bi-images me-1"></i> {{ $title ?? __('Select Media') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 js-picker-breadcrumbs"></ol>
                    </nav>

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" class="form-control form-control-sm js-picker-search" placeholder="{{ __('Filter...') }}" style="width: 200px;">
                        <button type="button" class="btn btn-outline-secondary btn-sm js-picker-refresh" title="{{ __('Refresh') }}">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                @can('add-media')
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 bg-light rounded border">
                        <input type="file" class="form-control form-control-sm js-picker-upload-input flex-grow-1" multiple style="min-width: 220px;">
                        <button type="button" class="btn btn-sm btn-outline-primary js-picker-upload-btn">
                            <i class="bi bi-cloud-upload me-1"></i> {{ __('Upload Here') }}
                        </button>
                    </div>
                @endcan

                <div class="alert alert-danger py-2 small d-none js-picker-error" role="alert"></div>

                <div class="text-center py-4 d-none js-picker-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 js-picker-grid"></div>
            </div>

            <div class="modal-footer justify-content-between">
                <span class="small text-muted">
                    {{ __('Root') }}: <code class="js-picker-current-folder">/</code>
                </span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const pickerStates = new WeakMap();

    function getState(modalEl) {
        if (!pickerStates.has(modalEl)) {
            pickerStates.set(modalEl, {
                currentFolder: modalEl.dataset.initialFolder || '',
                accept: modalEl.dataset.defaultAccept || 'all',
                inputId: null,
                previewId: null,
                onSelect: null,
                lastPayload: null,
            });
        }
        return pickerStates.get(modalEl);
    }

    function showError(modalEl, message) {
        const errEl = modalEl.querySelector('.js-picker-error');
        if (!errEl) return;
        if (message) {
            errEl.textContent = message;
            errEl.classList.remove('d-none');
        } else {
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }
    }

    function setLoading(modalEl, loading) {
        const loadingEl = modalEl.querySelector('.js-picker-loading');
        if (loadingEl) {
            loadingEl.classList.toggle('d-none', !loading);
        }
    }

    function applySelection(modalEl, item) {
        const state = getState(modalEl);

        if (state.inputId) {
            const inputEl = document.getElementById(state.inputId);
            if (inputEl) {
                inputEl.value = item.relative_path || '';
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                inputEl.dispatchEvent(new CustomEvent('jangkeyte:media-selected', {
                    bubbles: true,
                    detail: item,
                }));
            }
        }

        if (state.previewId) {
            const previewEl = document.getElementById(state.previewId);
            if (previewEl) {
                if (previewEl.tagName === 'IMG' && item.url) {
                    previewEl.src = item.url;
                    previewEl.alt = item.name || '';
                }
                previewEl.classList.remove('d-none', 'opacity-50');
            }
        }

        if (typeof state.onSelect === 'function') {
            state.onSelect(item);
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
    }

    function renderBreadcrumbs(modalEl, breadcrumbs) {
        const listEl = modalEl.querySelector('.js-picker-breadcrumbs');
        if (!listEl) return;

        while (listEl.firstChild) {
            listEl.removeChild(listEl.firstChild);
        }

        const items = Array.isArray(breadcrumbs) && breadcrumbs.length > 0
            ? breadcrumbs
            : [{ name: 'Root', path: '' }];

        items.forEach(function (crumb, index) {
            const li = document.createElement('li');
            const isLast = index === items.length - 1;
            li.className = 'breadcrumb-item' + (isLast ? ' active' : '');

            if (isLast) {
                li.setAttribute('aria-current', 'page');
                li.textContent = crumb.path === '' ? @json(__('Root')) : crumb.name;
            } else {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-link btn-sm p-0 text-decoration-none align-baseline';
                btn.textContent = crumb.path === '' ? @json(__('Root')) : crumb.name;
                btn.addEventListener('click', function () {
                    loadFolder(modalEl, crumb.path || '');
                });
                li.appendChild(btn);
            }

            listEl.appendChild(li);
        });
    }

    function renderGrid(modalEl, payload) {
        const gridEl = modalEl.querySelector('.js-picker-grid');
        const folderLabelEl = modalEl.querySelector('.js-picker-current-folder');
        const searchEl = modalEl.querySelector('.js-picker-search');
        if (!gridEl) return;

        const state = getState(modalEl);
        state.currentFolder = payload.current_path || '';
        state.lastPayload = payload;

        if (folderLabelEl) {
            folderLabelEl.textContent = state.currentFolder ? '/' + state.currentFolder : '/';
        }

        renderBreadcrumbs(modalEl, payload.breadcrumbs);

        while (gridEl.firstChild) {
            gridEl.removeChild(gridEl.firstChild);
        }

        const term = (searchEl ? searchEl.value : '').trim().toLowerCase();
        const directories = Array.isArray(payload.directories) ? payload.directories : [];
        const files = Array.isArray(payload.files) ? payload.files : [];
        let renderedCount = 0;

        directories.forEach(function (dir) {
            const name = String(dir.name || '');
            if (term && !name.toLowerCase().includes(term)) return;

            const col = document.createElement('div');
            col.className = 'col';

            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'card h-100 w-100 text-center p-3 shadow-sm border bg-body btn btn-light';
            card.addEventListener('click', function () {
                loadFolder(modalEl, dir.relative_path || '');
            });

            const icon = document.createElement('i');
            icon.className = 'bi bi-folder-fill text-warning fs-1 d-block mb-1';

            const label = document.createElement('div');
            label.className = 'small fw-medium text-truncate w-100';
            label.textContent = name;
            label.title = name;

            card.appendChild(icon);
            card.appendChild(label);
            col.appendChild(card);
            gridEl.appendChild(col);
            renderedCount++;
        });

        files.forEach(function (file) {
            if (state.accept === 'image' && !file.is_image) return;

            const name = String(file.name || '');
            if (term && !name.toLowerCase().includes(term)) return;

            const col = document.createElement('div');
            col.className = 'col';

            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'card h-100 w-100 text-start p-0 shadow-sm overflow-hidden btn btn-light border';
            card.addEventListener('click', function () {
                applySelection(modalEl, file);
            });

            const thumbBox = document.createElement('div');
            thumbBox.className = 'bg-light d-flex align-items-center justify-content-center w-100 overflow-hidden';
            thumbBox.style.height = '110px';

            if (file.is_image && file.url) {
                const img = document.createElement('img');
                img.src = file.url;
                img.alt = name;
                img.className = 'h-100 w-100 object-fit-cover';
                img.loading = 'lazy';
                thumbBox.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark fs-1 text-secondary';
                thumbBox.appendChild(icon);
            }

            const cardBody = document.createElement('div');
            cardBody.className = 'p-2 w-100';

            const title = document.createElement('div');
            title.className = 'small fw-medium text-truncate';
            title.textContent = name;
            title.title = name;

            const meta = document.createElement('div');
            meta.className = 'text-muted';
            meta.style.fontSize = '0.75rem';
            meta.textContent = ((parseInt(file.size || 0, 10) / 1024).toFixed(1)) + ' KB';

            cardBody.appendChild(title);
            cardBody.appendChild(meta);
            card.appendChild(thumbBox);
            card.appendChild(cardBody);
            col.appendChild(card);
            gridEl.appendChild(col);
            renderedCount++;
        });

        if (renderedCount === 0) {
            const emptyCol = document.createElement('div');
            emptyCol.className = 'col-12 text-center text-muted py-4';
            emptyCol.textContent = @json(__('No files or folders in this directory.'));
            gridEl.appendChild(emptyCol);
        }
    }

    function loadFolder(modalEl, folder) {
        const indexUrl = modalEl.dataset.indexUrl;
        if (!indexUrl) return;

        showError(modalEl, null);
        setLoading(modalEl, true);

        const sep = indexUrl.includes('?') ? '&' : '?';
        const url = indexUrl + sep + 'json=1&folder=' + encodeURIComponent(folder || '');

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        throw new Error((data && data.message) || 'Failed to load media.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                renderGrid(modalEl, data);
            })
            .catch(function (err) {
                showError(modalEl, err.message || 'Failed to load media.');
            })
            .finally(function () {
                setLoading(modalEl, false);
            });
    }

    function bindModalEvents(modalEl) {
        if (modalEl.dataset.pickerBound === '1') return;
        modalEl.dataset.pickerBound = '1';

        const refreshBtn = modalEl.querySelector('.js-picker-refresh');
        const searchEl = modalEl.querySelector('.js-picker-search');
        const uploadBtn = modalEl.querySelector('.js-picker-upload-btn');
        const uploadInput = modalEl.querySelector('.js-picker-upload-input');

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                loadFolder(modalEl, getState(modalEl).currentFolder);
            });
        }

        if (searchEl) {
            searchEl.addEventListener('input', function () {
                const payload = getState(modalEl).lastPayload;
                if (payload) {
                    renderGrid(modalEl, payload);
                }
            });
        }

        if (uploadBtn && uploadInput) {
            uploadBtn.addEventListener('click', function () {
                const files = uploadInput.files;
                if (!files || files.length === 0) return;

                const uploadUrl = modalEl.dataset.uploadUrl;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const state = getState(modalEl);

                const formData = new FormData();
                formData.append('target_path', state.currentFolder || '');
                Array.from(files).forEach(function (file) {
                    formData.append('files[]', file);
                });

                showError(modalEl, null);
                uploadBtn.disabled = true;

                fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            if (!res.ok) {
                                throw new Error((data && data.message) || 'Upload failed.');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        uploadInput.value = '';
                        if (typeof iziToast !== 'undefined' && data && data.message) {
                            iziToast.success({ position: 'topRight', message: data.message });
                        }
                        loadFolder(modalEl, state.currentFolder);
                    })
                    .catch(function (err) {
                        showError(modalEl, err.message || 'Upload failed.');
                    })
                    .finally(function () {
                        uploadBtn.disabled = false;
                    });
            });
        }
    }

    window.JangKeyteMediaPicker = {
        open: function (options) {
            const opts = options || {};
            const modalId = opts.modalId || 'jangkeyteMediaPickerModal';
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;

            bindModalEvents(modalEl);

            const state = getState(modalEl);
            state.inputId = opts.inputId || null;
            state.previewId = opts.previewId || null;
            state.accept = opts.accept || modalEl.dataset.defaultAccept || 'all';
            state.onSelect = typeof opts.onSelect === 'function' ? opts.onSelect : null;

            const startFolder = opts.folder !== undefined && opts.folder !== null
                ? opts.folder
                : (modalEl.dataset.initialFolder || '');

            const searchEl = modalEl.querySelector('.js-picker-search');
            if (searchEl) searchEl.value = '';

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            loadFolder(modalEl, startFolder);
        },
    };

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-media-picker], [data-media-target]');
        if (!trigger) return;

        event.preventDefault();
        window.JangKeyteMediaPicker.open({
            modalId: trigger.dataset.mediaModal || 'jangkeyteMediaPickerModal',
            inputId: trigger.dataset.mediaInput || trigger.dataset.mediaTarget || null,
            previewId: trigger.dataset.mediaPreview || null,
            folder: trigger.dataset.mediaFolder !== undefined ? trigger.dataset.mediaFolder : null,
            accept: trigger.dataset.mediaAccept || null,
        });
    });
})();
</script>
@endpush
@endonce
