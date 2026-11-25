@extends('JangKeyte::layout.app')

@section('heading', __('Create Module / CRUD'))

@section('button')
    <a href="{{ route('admin.modules.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
    </a>
@endsection

@section('main_content')
    @php
        $defaultFields = [
            [
                'name' => 'title',
                'type' => 'string',
                'nullable' => '0',
                'sortable' => '1',
                'related_model' => '',
                'related_display' => 'name',
            ],
        ];
        $oldFields = old('fields');
        $initialFields = (is_array($oldFields) && count($oldFields) > 0)
            ? array_values($oldFields)
            : $defaultFields;
        $currentIcon = old('icon', 'bi bi-collection');
    @endphp

    <form method="POST" action="{{ route('admin.modules.store_crud') }}" id="studioCreateCrudForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-box-seam me-1"></i>{{ __('Module & Model Configuration') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="module_name" class="form-label">{{ __('Module Name') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="module_name"
                            name="module_name"
                            list="moduleNameList"
                            value="{{ old('module_name', $defaultModule ?? '') }}"
                            class="form-control @error('module_name') is-invalid @enderror"
                            placeholder="e.g. TripBooking"
                            required
                        >
                        <datalist id="moduleNameList">
                            @foreach($existingModules as $modName)
                                <option value="{{ $modName }}"></option>
                            @endforeach
                        </datalist>
                        @error('module_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">StudlyCase (e.g. <code>TripBooking</code>, <code>Blog</code>)</div>
                    </div>

                    <div class="col-md-4">
                        <label for="model_name" class="form-label">{{ __('Model Name') }} <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            id="model_name"
                            name="model_name"
                            value="{{ old('model_name') }}"
                            class="form-control @error('model_name') is-invalid @enderror"
                            placeholder="e.g. Trip"
                            required
                        >
                        @error('model_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">StudlyCase singular (e.g. <code>Trip</code>, <code>Article</code>)</div>
                    </div>

                    <div class="col-md-4">
                        <label for="studioIconInput" class="form-label">{{ __('Sidebar Icon') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i id="studioIconPreview" class="{{ $currentIcon }}"></i>
                            </span>
                            <input
                                type="text"
                                id="studioIconInput"
                                name="icon"
                                value="{{ $currentIcon }}"
                                class="form-control @error('icon') is-invalid @enderror"
                                placeholder="bi bi-collection"
                            >
                            <button
                                type="button"
                                class="btn btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="{{ __('Choose Icon') }}"
                            >
                                <i class="bi bi-grid"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" style="width: 260px;">
                                <div class="row row-cols-6 g-1">
                                    @foreach($presetIcons as $presetIcon)
                                        <div class="col">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm w-100 p-1 js-icon-preset"
                                                data-icon-value="{{ $presetIcon }}"
                                                title="{{ $presetIcon }}"
                                            >
                                                <i class="{{ $presetIcon }}"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @error('icon')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="soft_deletes" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                role="switch"
                                id="soft_deletes"
                                name="soft_deletes"
                                value="1"
                                @checked(filter_var(old('soft_deletes', '0'), FILTER_VALIDATE_BOOLEAN))
                            >
                            <label class="form-check-label" for="soft_deletes">{{ __('Enable Soft Deletes (Trash & Restore)') }}</label>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="exportable" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                role="switch"
                                id="exportable"
                                name="exportable"
                                value="1"
                                @checked(filter_var(old('exportable', '1'), FILTER_VALIDATE_BOOLEAN))
                            >
                            <label class="form-check-label" for="exportable">{{ __('Enable Excel/CSV Export') }}</label>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="run_migrations" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                role="switch"
                                id="run_migrations"
                                name="run_migrations"
                                value="1"
                                @checked(filter_var(old('run_migrations', '1'), FILTER_VALIDATE_BOOLEAN))
                            >
                            <label class="form-check-label" for="run_migrations">{{ __('Run Migrations & Seed Permissions') }}</label>
                        </div>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="force" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                role="switch"
                                id="force"
                                name="force"
                                value="1"
                                @checked(filter_var(old('force', '0'), FILTER_VALIDATE_BOOLEAN))
                            >
                            <label class="form-check-label" for="force">{{ __('Force Overwrite Existing Files') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="bi bi-table me-1"></i>{{ __('Columns Builder') }}</h5>
                <button type="button" id="addFieldRowBtn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add Column') }}
                </button>
            </div>
            <div class="card-body">
                @error('fields')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror

                <div id="fieldsContainer" class="d-flex flex-column gap-2"></div>

                <div class="form-text mt-2">
                    Reserved columns (<code>id</code>, <code>created_at</code>, <code>updated_at</code>, <code>deleted_at</code>) are added automatically.
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.modules.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-magic me-1"></i>{{ __('Generate CRUD') }}
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialFields = @json($initialFields);
        const availableModels = @json($availableModels);
        const fieldTypes = @json($fieldTypes);
        const validationErrors = @json($errors->getMessages());

        const labels = {
            columnName: @json(__('Column Name')),
            fieldType: @json(__('Field Type')),
            nullable: @json(__('Nullable')),
            sortable: @json(__('Sortable')),
            targetModel: @json(__('Target Model')),
            displayColumn: @json(__('Display Column')),
            selectModel: @json(__('Select Model')),
            moveUp: @json(__('Move Up')),
            moveDown: @json(__('Move Down')),
            remove: @json(__('Remove')),
            minOneRow: @json(__('At least one column is required.'))
        };

        const iconInput = document.getElementById('studioIconInput');
        const iconPreview = document.getElementById('studioIconPreview');
        if (iconInput && iconPreview) {
            const updateIconPreview = function (rawVal) {
                const trimmed = (rawVal || '').trim();
                iconPreview.className = /^[a-zA-Z0-9 _-]+$/.test(trimmed) && trimmed !== ''
                    ? trimmed
                    : 'bi bi-collection';
            };

            iconInput.addEventListener('input', function () {
                updateIconPreview(this.value);
            });

            document.querySelectorAll('.js-icon-preset').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const val = btn.dataset.iconValue || 'bi bi-collection';
                    iconInput.value = val;
                    updateIconPreview(val);
                });
            });
        }

        function isTruthy(val) {
            return val === true || val === 1 || val === '1' || val === 'true' || val === 'on';
        }

        function toSnakeCase(str) {
            return String(str || '')
                .trim()
                .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
                .toLowerCase()
                .replace(/[^a-z0-9_]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function getFieldError(idx, key) {
            const fullKey = 'fields.' + idx + '.' + key;
            if (validationErrors && Array.isArray(validationErrors[fullKey]) && validationErrors[fullKey].length > 0) {
                return String(validationErrors[fullKey][0]);
            }
            return '';
        }

        function findModelColumns(modelClass) {
            for (let i = 0; i < availableModels.length; i++) {
                if (availableModels[i].class === modelClass && Array.isArray(availableModels[i].columns)) {
                    return availableModels[i].columns;
                }
            }
            return [];
        }

        function populateDisplayDatalist(datalistEl, modelClass) {
            while (datalistEl.firstChild) {
                datalistEl.removeChild(datalistEl.firstChild);
            }
            const cols = findModelColumns(modelClass);
            cols.forEach(function (col) {
                const opt = document.createElement('option');
                opt.setAttribute('value', col);
                datalistEl.appendChild(opt);
            });
        }

        const container = document.getElementById('fieldsContainer');
        const addBtn = document.getElementById('addFieldRowBtn');
        const formEl = document.getElementById('studioCreateCrudForm');

        function reindexRows() {
            const rows = container.querySelectorAll('.js-field-row');
            const total = rows.length;

            rows.forEach(function (row, idx) {
                const nameInput = row.querySelector('.js-field-name');
                const typeSelect = row.querySelector('.js-field-type');
                const nullableHidden = row.querySelector('.js-field-nullable-hidden');
                const nullableCheck = row.querySelector('.js-field-nullable');
                const nullableLabel = row.querySelector('.js-field-nullable-label');
                const sortableHidden = row.querySelector('.js-field-sortable-hidden');
                const sortableCheck = row.querySelector('.js-field-sortable');
                const sortableLabel = row.querySelector('.js-field-sortable-label');
                const relModelSelect = row.querySelector('.js-field-related-model');
                const relDisplayInput = row.querySelector('.js-field-related-display');
                const relDatalist = row.querySelector('.js-field-display-datalist');
                const upBtn = row.querySelector('.js-move-up');
                const downBtn = row.querySelector('.js-move-down');
                const removeBtn = row.querySelector('.js-remove-row');

                if (nameInput) nameInput.setAttribute('name', 'fields[' + idx + '][name]');
                if (typeSelect) typeSelect.setAttribute('name', 'fields[' + idx + '][type]');
                if (nullableHidden) nullableHidden.setAttribute('name', 'fields[' + idx + '][nullable]');
                if (nullableCheck) {
                    nullableCheck.setAttribute('name', 'fields[' + idx + '][nullable]');
                    nullableCheck.setAttribute('id', 'field_nullable_' + idx);
                }
                if (nullableLabel) nullableLabel.setAttribute('for', 'field_nullable_' + idx);

                if (sortableHidden) sortableHidden.setAttribute('name', 'fields[' + idx + '][sortable]');
                if (sortableCheck) {
                    sortableCheck.setAttribute('name', 'fields[' + idx + '][sortable]');
                    sortableCheck.setAttribute('id', 'field_sortable_' + idx);
                }
                if (sortableLabel) sortableLabel.setAttribute('for', 'field_sortable_' + idx);

                if (relModelSelect) relModelSelect.setAttribute('name', 'fields[' + idx + '][related_model]');
                if (relDisplayInput && relDatalist) {
                    const listId = 'field_display_list_' + idx;
                    relDisplayInput.setAttribute('name', 'fields[' + idx + '][related_display]');
                    relDisplayInput.setAttribute('list', listId);
                    relDatalist.setAttribute('id', listId);
                }

                if (upBtn) upBtn.disabled = idx === 0;
                if (downBtn) downBtn.disabled = idx === total - 1;
                if (removeBtn) removeBtn.disabled = total <= 1;
            });
        }

        function createFieldRow(fieldData, idx) {
            const row = document.createElement('div');
            row.className = 'border rounded p-3 bg-body-tertiary js-field-row';

            const mainGrid = document.createElement('div');
            mainGrid.className = 'row g-2 align-items-start';

            // Column Name
            const nameCol = document.createElement('div');
            nameCol.className = 'col-md-3';
            const nameLabel = document.createElement('label');
            nameLabel.className = 'form-label small fw-semibold mb-1';
            nameLabel.textContent = labels.columnName;
            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.className = 'form-control form-control-sm js-field-name';
            nameInput.required = true;
            nameInput.placeholder = 'e.g. title';
            nameInput.value = fieldData && fieldData.name ? String(fieldData.name) : '';

            const nameError = getFieldError(idx, 'name');
            if (nameError !== '') {
                nameInput.classList.add('is-invalid');
            }
            nameInput.addEventListener('blur', function () {
                if (this.value.trim() !== '') {
                    this.value = toSnakeCase(this.value);
                }
            });
            nameCol.appendChild(nameLabel);
            nameCol.appendChild(nameInput);
            if (nameError !== '') {
                const errDiv = document.createElement('div');
                errDiv.className = 'invalid-feedback d-block';
                errDiv.textContent = nameError;
                nameCol.appendChild(errDiv);
            }

            // Field Type
            const typeCol = document.createElement('div');
            typeCol.className = 'col-md-3';
            const typeLabel = document.createElement('label');
            typeLabel.className = 'form-label small fw-semibold mb-1';
            typeLabel.textContent = labels.fieldType;
            const typeSelect = document.createElement('select');
            typeSelect.className = 'form-select form-select-sm js-field-type';
            const selectedType = fieldData && fieldData.type ? String(fieldData.type) : 'string';
            fieldTypes.forEach(function (t) {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                if (t === selectedType) {
                    opt.selected = true;
                }
                typeSelect.appendChild(opt);
            });
            const typeError = getFieldError(idx, 'type');
            if (typeError !== '') {
                typeSelect.classList.add('is-invalid');
            }
            typeCol.appendChild(typeLabel);
            typeCol.appendChild(typeSelect);
            if (typeError !== '') {
                const errDiv = document.createElement('div');
                errDiv.className = 'invalid-feedback d-block';
                errDiv.textContent = typeError;
                typeCol.appendChild(errDiv);
            }

            // Nullable & Sortable Checkboxes
            const flagsCol = document.createElement('div');
            flagsCol.className = 'col-md-3 d-flex align-items-center gap-3 pt-md-4';

            const nullableWrap = document.createElement('div');
            nullableWrap.className = 'form-check mb-0';
            const nullableHidden = document.createElement('input');
            nullableHidden.type = 'hidden';
            nullableHidden.value = '0';
            nullableHidden.className = 'js-field-nullable-hidden';
            const nullableCheck = document.createElement('input');
            nullableCheck.type = 'checkbox';
            nullableCheck.value = '1';
            nullableCheck.className = 'form-check-input js-field-nullable';
            nullableCheck.checked = fieldData ? isTruthy(fieldData.nullable) : false;
            const nullableLabel = document.createElement('label');
            nullableLabel.className = 'form-check-label small js-field-nullable-label';
            nullableLabel.textContent = labels.nullable;
            nullableWrap.appendChild(nullableHidden);
            nullableWrap.appendChild(nullableCheck);
            nullableWrap.appendChild(nullableLabel);

            const sortableWrap = document.createElement('div');
            sortableWrap.className = 'form-check mb-0';
            const sortableHidden = document.createElement('input');
            sortableHidden.type = 'hidden';
            sortableHidden.value = '0';
            sortableHidden.className = 'js-field-sortable-hidden';
            const sortableCheck = document.createElement('input');
            sortableCheck.type = 'checkbox';
            sortableCheck.value = '1';
            sortableCheck.className = 'form-check-input js-field-sortable';
            sortableCheck.checked = fieldData && Object.prototype.hasOwnProperty.call(fieldData, 'sortable')
                ? isTruthy(fieldData.sortable)
                : true;
            const sortableLabel = document.createElement('label');
            sortableLabel.className = 'form-check-label small js-field-sortable-label';
            sortableLabel.textContent = labels.sortable;
            sortableWrap.appendChild(sortableHidden);
            sortableWrap.appendChild(sortableCheck);
            sortableWrap.appendChild(sortableLabel);

            flagsCol.appendChild(nullableWrap);
            flagsCol.appendChild(sortableWrap);

            // Row Actions
            const actionsCol = document.createElement('div');
            actionsCol.className = 'col-md-3 d-flex justify-content-md-end align-items-center gap-1 pt-md-4';

            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'btn btn-outline-secondary btn-sm js-move-up';
            upBtn.title = labels.moveUp;
            const upIcon = document.createElement('i');
            upIcon.className = 'bi bi-arrow-up';
            upBtn.appendChild(upIcon);

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'btn btn-outline-secondary btn-sm js-move-down';
            downBtn.title = labels.moveDown;
            const downIcon = document.createElement('i');
            downIcon.className = 'bi bi-arrow-down';
            downBtn.appendChild(downIcon);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger btn-sm js-remove-row';
            removeBtn.title = labels.remove;
            const removeIcon = document.createElement('i');
            removeIcon.className = 'bi bi-x-lg';
            removeBtn.appendChild(removeIcon);

            actionsCol.appendChild(upBtn);
            actionsCol.appendChild(downBtn);
            actionsCol.appendChild(removeBtn);

            mainGrid.appendChild(nameCol);
            mainGrid.appendChild(typeCol);
            mainGrid.appendChild(flagsCol);
            mainGrid.appendChild(actionsCol);
            row.appendChild(mainGrid);

            // Conditional BelongsTo Panel
            const relPanel = document.createElement('div');
            relPanel.className = 'row g-2 mt-2 pt-2 border-top js-belongs-to-panel';

            const relModelCol = document.createElement('div');
            relModelCol.className = 'col-md-6';
            const relModelLabel = document.createElement('label');
            relModelLabel.className = 'form-label small fw-semibold mb-1';
            relModelLabel.textContent = labels.targetModel;
            const relModelSelect = document.createElement('select');
            relModelSelect.className = 'form-select form-select-sm js-field-related-model';

            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = labels.selectModel;
            relModelSelect.appendChild(emptyOpt);

            const selectedModel = fieldData && fieldData.related_model ? String(fieldData.related_model) : '';
            availableModels.forEach(function (m) {
                const opt = document.createElement('option');
                opt.value = m.class;
                opt.textContent = m.label + ' — ' + m.class;
                if (m.class === selectedModel) {
                    opt.selected = true;
                }
                relModelSelect.appendChild(opt);
            });
            const relModelError = getFieldError(idx, 'related_model');
            if (relModelError !== '') {
                relModelSelect.classList.add('is-invalid');
            }
            relModelCol.appendChild(relModelLabel);
            relModelCol.appendChild(relModelSelect);
            if (relModelError !== '') {
                const errDiv = document.createElement('div');
                errDiv.className = 'invalid-feedback d-block';
                errDiv.textContent = relModelError;
                relModelCol.appendChild(errDiv);
            }

            const relDisplayCol = document.createElement('div');
            relDisplayCol.className = 'col-md-6';
            const relDisplayLabel = document.createElement('label');
            relDisplayLabel.className = 'form-label small fw-semibold mb-1';
            relDisplayLabel.textContent = labels.displayColumn;
            const relDisplayInput = document.createElement('input');
            relDisplayInput.type = 'text';
            relDisplayInput.className = 'form-control form-control-sm js-field-related-display';
            relDisplayInput.placeholder = 'name';
            relDisplayInput.value = fieldData && fieldData.related_display ? String(fieldData.related_display) : 'name';
            const relDatalist = document.createElement('datalist');
            relDatalist.className = 'js-field-display-datalist';
            populateDisplayDatalist(relDatalist, selectedModel);

            const relDisplayError = getFieldError(idx, 'related_display');
            if (relDisplayError !== '') {
                relDisplayInput.classList.add('is-invalid');
            }
            relDisplayCol.appendChild(relDisplayLabel);
            relDisplayCol.appendChild(relDisplayInput);
            relDisplayCol.appendChild(relDatalist);
            if (relDisplayError !== '') {
                const errDiv = document.createElement('div');
                errDiv.className = 'invalid-feedback d-block';
                errDiv.textContent = relDisplayError;
                relDisplayCol.appendChild(errDiv);
            }

            relPanel.appendChild(relModelCol);
            relPanel.appendChild(relDisplayCol);
            row.appendChild(relPanel);

            function syncBelongsToVisibility() {
                const isBelongsTo = typeSelect.value === 'belongsTo';
                relPanel.classList.toggle('d-none', !isBelongsTo);
                relModelSelect.disabled = !isBelongsTo;
                relDisplayInput.disabled = !isBelongsTo;
                relModelSelect.required = isBelongsTo;
            }

            syncBelongsToVisibility();

            typeSelect.addEventListener('change', function () {
                syncBelongsToVisibility();
                if (['text', 'richtext', 'image', 'file'].indexOf(this.value) !== -1) {
                    sortableCheck.checked = false;
                }
            });

            relModelSelect.addEventListener('change', function () {
                populateDisplayDatalist(relDatalist, this.value);
                const cols = findModelColumns(this.value);
                if (cols.length > 0 && cols.indexOf(relDisplayInput.value) === -1) {
                    if (cols.indexOf('name') !== -1) {
                        relDisplayInput.value = 'name';
                    } else if (cols.indexOf('title') !== -1) {
                        relDisplayInput.value = 'title';
                    } else {
                        relDisplayInput.value = cols[0];
                    }
                }
            });

            upBtn.addEventListener('click', function () {
                const prev = row.previousElementSibling;
                if (prev) {
                    container.insertBefore(row, prev);
                    reindexRows();
                }
            });

            downBtn.addEventListener('click', function () {
                const next = row.nextElementSibling;
                if (next) {
                    container.insertBefore(next, row);
                    reindexRows();
                }
            });

            removeBtn.addEventListener('click', function () {
                if (container.querySelectorAll('.js-field-row').length <= 1) {
                    if (typeof iziToast !== 'undefined') {
                        iziToast.warning({ position: 'topRight', message: labels.minOneRow });
                    }
                    return;
                }
                row.remove();
                reindexRows();
            });

            return row;
        }

        initialFields.forEach(function (fieldData, idx) {
            container.appendChild(createFieldRow(fieldData, idx));
        });
        reindexRows();

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                const nextIdx = container.querySelectorAll('.js-field-row').length;
                container.appendChild(createFieldRow({
                    name: '',
                    type: 'string',
                    nullable: '0',
                    sortable: '1',
                    related_model: '',
                    related_display: 'name'
                }, nextIdx));
                reindexRows();
            });
        }

        if (formEl) {
            formEl.addEventListener('submit', function () {
                container.querySelectorAll('.js-field-name').forEach(function (input) {
                    if (input.value.trim() !== '') {
                        input.value = toSnakeCase(input.value);
                    }
                });
                reindexRows();
            });
        }
    });
</script>
@endpush
