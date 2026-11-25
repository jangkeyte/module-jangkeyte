<?php

namespace JangKeyte\Http\Controllers\Role;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use JangKeyte\Http\Requests\Role\SaveRoleRequest;
use JangKeyte\Models\Permission;
use JangKeyte\Repositories\Permission\PermissionRepositoryInterface;
use JangKeyte\Repositories\Role\RoleRepositoryInterface;
use JangKeyte\Services\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository,
        protected PermissionRepositoryInterface $permissionRepository,
        protected PermissionRegistrar $permissionRegistrar,
    ) {}

    public function index(): View
    {
        $roles = $this->roleRepository->allRoles();

        return view('JangKeyte::role.index', compact('roles'));
    }

    public function create(): View
    {
        $role = $this->roleRepository->find(null);
        $groupedPermissions = $this->permissionRepository->allOrdered()->groupBy('group');
        $selectedPermissions = [];

        return view('JangKeyte::role.form', compact('role', 'groupedPermissions', 'selectedPermissions'));
    }

    public function store(SaveRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $role = $this->roleRepository->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? '',
                'code' => $validated['code'] ?: 'secondary',
            ]);

            if (! empty($validated['permission'])) {
                $ids = Permission::whereIn('slug', $validated['permission'])->pluck('id')->all();
                $role->permissions()->sync($ids);
            }
        });

        $this->permissionRegistrar->forgetCachedPermissions();

        return redirect()->route('admin_role')->with('success', __('Tạo vai trò mới thành công.'));
    }

    public function edit(int $id): View
    {
        $role = $this->roleRepository->findOrFail($id);
        $groupedPermissions = $this->permissionRepository->allOrdered()->groupBy('group');
        $selectedPermissions = $role->permissions()->pluck('slug')->all();

        return view('JangKeyte::role.form', compact('role', 'groupedPermissions', 'selectedPermissions'));
    }

    public function update(SaveRoleRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($id, $validated): void {
            $role = $this->roleRepository->update($id, [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? '',
                'code' => $validated['code'] ?: 'secondary',
            ]);

            $ids = ! empty($validated['permission'])
                ? Permission::whereIn('slug', $validated['permission'])->pluck('id')->all()
                : [];
            $role->permissions()->sync($ids);
        });

        $this->permissionRegistrar->forgetCachedPermissions();

        return redirect()->route('admin_role')->with('success', __('Cập nhật vai trò thành công.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = $this->roleRepository->findOrFail($id);
        if (in_array($role->slug, ['administrator', 'admin'], true)) {
            return redirect()->route('admin_role')->with('error', __('Không thể xóa vai trò hệ thống gốc.'));
        }

        $this->roleRepository->delete($id);
        $this->permissionRegistrar->forgetCachedPermissions();

        return redirect()->route('admin_role')->with('success', __('Xóa vai trò thành công.'));
    }
}
