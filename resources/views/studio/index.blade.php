@extends('JangKeyte::layout.app')

@section('heading', __('Module Studio'))

@section('button')
    @if($canManageBuilder)
        <a href="{{ route('admin.modules.create_crud') }}" class="btn btn-primary btn-sm ms-2">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Create Module / CRUD') }}
        </a>
    @endif
@endsection

@section('main_content')
    @if(! $builderEnabled)
        <div class="alert alert-info py-2 small d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock fs-5"></i>
            <span>{{ __('Builder write actions are disabled in this environment.') }}</span>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>{{ __('Module') }}</th>
                            <th style="width: 130px;">{{ __('Status') }}</th>
                            <th>{{ __('Generated CRUDs') }}</th>
                            @if($canManageBuilder)
                                <th class="text-end" style="width: 280px;">{{ __('Action') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($modules as $module)
                            @php
                                $schemas = $module['schemas'] ?? [];
                                $tableNames = array_values(array_filter(array_map(
                                    static fn (array $s): string => (string) ($s['table'] ?? ''),
                                    $schemas
                                )));
                                $isValidModuleName = is_string($module['name'] ?? null)
                                    && preg_match(\JangKeyte\Services\Generator\CrudDefinition::STUDLY_REGEX, $module['name']) === 1;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $module['name'] }}</div>
                                    <div class="small text-muted"><code>{{ $module['provider'] }}</code></div>
                                </td>
                                <td>
                                    @if(! empty($module['enabled']))
                                        <span class="badge text-bg-success">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ __('Disabled') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($schemas as $schema)
                                        @php
                                            $routeIndex = $schema['route_index'] ?? null;
                                            $isRoutable = ! empty($schema['route_available'])
                                                && is_string($routeIndex)
                                                && $routeIndex !== ''
                                                && \Illuminate\Support\Facades\Route::has($routeIndex);
                                            $schemaIcon = $schema['icon'] ?? 'bi bi-collection';
                                            $fieldCount = is_array($schema['fields'] ?? null) ? count($schema['fields']) : 0;
                                        @endphp
                                        @if($isRoutable)
                                            <a href="{{ route($routeIndex) }}" class="badge rounded-pill text-bg-primary text-decoration-none me-1 mb-1 px-2 py-1">
                                                <i class="{{ $schemaIcon }} me-1"></i>{{ $schema['model'] ?? '' }}
                                                <span class="opacity-75">({{ $fieldCount }})</span>
                                            </a>
                                        @else
                                            <span class="badge rounded-pill text-bg-light border text-muted me-1 mb-1 px-2 py-1" title="{{ __('Enable module to open CRUD') }}">
                                                <i class="{{ $schemaIcon }} me-1"></i>{{ $schema['model'] ?? '' }}
                                                <span class="opacity-75">({{ $fieldCount }})</span>
                                            </span>
                                        @endif
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </td>
                                @if($canManageBuilder)
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                            @if($isValidModuleName)
                                                <a href="{{ route('admin.modules.create_crud', ['module' => $module['name']]) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-plus-circle me-1"></i>{{ __('Add CRUD') }}
                                                </a>

                                                <form method="POST" action="{{ route('admin.modules.toggle', $module['name']) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    @if(! empty($module['enabled']))
                                                        <button type="submit" class="btn btn-outline-warning btn-sm">
                                                            <i class="bi bi-pause-circle me-1"></i>{{ __('Disable') }}
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                                            <i class="bi bi-play-circle me-1"></i>{{ __('Enable') }}
                                                        </button>
                                                    @endif
                                                </form>

                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm js-delete-module-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModuleModal"
                                                    data-module-name="{{ $module['name'] }}"
                                                    data-delete-url="{{ route('admin.modules.destroy', $module['name']) }}"
                                                    data-tables="{{ implode(', ', $tableNames) }}"
                                                >
                                                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                                                </button>
                                            @else
                                                <span class="badge text-bg-warning" title="{{ __('Invalid module directory name') }}">
                                                    {{ __('Invalid Name') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManageBuilder ? 5 : 4 }}" class="text-center text-muted py-5">
                                    <i class="bi bi-cpu fs-1 d-block mb-2 opacity-50"></i>
                                    <div class="mb-2">{{ __('No modules created yet.') }}</div>
                                    @if($canManageBuilder)
                                        <a href="{{ route('admin.modules.create_crud') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i>{{ __('Create Module / CRUD') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($canManageBuilder)
        <div class="modal fade" id="deleteModuleModal" tabindex="-1" aria-labelledby="deleteModuleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="#" id="deleteModuleForm" class="modal-content">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModuleModalLabel">
                            <i class="bi bi-exclamation-triangle text-danger me-1"></i>{{ __('Delete Module') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            {{ __('Are you sure you want to delete module') }}
                            <strong id="deleteModuleName"></strong>?
                        </p>

                        <div id="deleteModuleTablesWrap" class="alert alert-light border py-2 px-3 small mb-3 d-none">
                            <span class="fw-semibold">{{ __('Affected database tables:') }}</span>
                            <code id="deleteModuleTables" class="ms-1"></code>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="dropTablesCheckbox" name="drop_tables" value="1">
                            <label class="form-check-label fw-medium text-danger" for="dropTablesCheckbox">
                                {{ __('Drop database tables & RBAC permissions') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@if($canManageBuilder)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('deleteModuleModal');
            const deleteForm = document.getElementById('deleteModuleForm');
            const nameEl = document.getElementById('deleteModuleName');
            const tablesWrap = document.getElementById('deleteModuleTablesWrap');
            const tablesEl = document.getElementById('deleteModuleTables');
            const dropCheckbox = document.getElementById('dropTablesCheckbox');

            function populateModal(trigger) {
                if (!trigger || !deleteForm) {
                    return;
                }
                const modName = trigger.dataset.moduleName || '';
                const deleteUrl = trigger.dataset.deleteUrl || '#';
                const tables = trigger.dataset.tables || '';

                deleteForm.setAttribute('action', deleteUrl);
                if (nameEl) {
                    nameEl.textContent = modName;
                }
                if (tablesEl && tablesWrap) {
                    tablesEl.textContent = tables;
                    tablesWrap.classList.toggle('d-none', tables.trim() === '');
                }
                if (dropCheckbox) {
                    dropCheckbox.checked = false;
                }
            }

            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function (event) {
                    populateModal(event.relatedTarget);
                });
            }

            document.querySelectorAll('.js-delete-module-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    populateModal(btn);
                });
            });
        });
    </script>
    @endpush
@endif
