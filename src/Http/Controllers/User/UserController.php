<?php

namespace JangKeyte\Http\Controllers\User;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use JangKeyte\Filters\UserFilter;
use JangKeyte\Http\Requests\User\SaveUserRequest;
use JangKeyte\Repositories\Permission\PermissionRepositoryInterface;
use JangKeyte\Repositories\Role\RoleRepositoryInterface;
use JangKeyte\Repositories\User\UserRepositoryInterface;
use JangKeyte\Services\FileUploadService;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected RoleRepositoryInterface $roleRepository,
        protected PermissionRepositoryInterface $permissionRepository,
        protected FileUploadService $fileUploadService,
    ) {}

    public function index(Request $request): View
    {
        $users = $request->filled('keyword')
            ? $this->userRepository->filter(new UserFilter($request), 20)
            : $this->userRepository->paginateWithRoles(20);

        return view('JangKeyte::user.index', compact('users'));
    }

    public function show(int $id): View
    {
        $user = $this->userRepository->findOrFail($id);
        $user->load('roles.permissions', 'permissions');
        $groupedPermissions = $this->permissionRepository->allOrdered()->groupBy('group');

        return view('JangKeyte::user.detail', compact('user', 'groupedPermissions'));
    }

    public function create(): View
    {
        $user = $this->userRepository->find(null);
        $roles = $this->roleRepository->allRoles()->pluck('name', 'id');

        return view('JangKeyte::user.form', compact('user', 'roles'));
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $photo = 'default.png';

        if ($request->hasFile('image')) {
            $photo = $this->fileUploadService->uploadPublic($request->file('image'), 'users');
        }

        DB::transaction(function () use ($validated, $photo): void {
            $user = $this->userRepository->create([
                'name' => $validated['name'],
                'username' => $validated['username'] ?? $validated['email'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'photo' => $photo,
            ]);

            if (! empty($validated['role'])) {
                $user->roles()->sync([(int) $validated['role']]);
            }

            if (! empty($validated['permission'])) {
                $user->refreshPermissions($validated['permission']);
            }
        });

        return redirect()->route('admin_user')->with('success', __('Thêm người dùng mới thành công.'));
    }

    public function edit(int $id): View
    {
        $user = $this->userRepository->findOrFail($id);
        $user->load('roles');
        $roles = $this->roleRepository->allRoles()->pluck('name', 'id');

        return view('JangKeyte::user.form', compact('user', 'roles'));
    }

    public function update(SaveUserRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();
        $existing = $this->userRepository->findOrFail($id);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['username'])) {
            $data['username'] = $validated['username'];
        }

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('image')) {
            $data['photo'] = $this->fileUploadService->uploadPublic(
                $request->file('image'),
                'users',
                $existing->photo
            );
        }

        DB::transaction(function () use ($id, $data, $validated): void {
            $user = $this->userRepository->update($id, $data);

            if (! empty($validated['role'])) {
                $user->roles()->sync([(int) $validated['role']]);
            }

            if (isset($validated['permission'])) {
                $user->refreshPermissions($validated['permission']);
            }
        });

        return redirect()->route('admin_user')->with('success', __('Cập nhật tài khoản thành công.'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ((int) $request->user()?->id === $id) {
            return redirect()->route('admin_user')->with('error', __('Không thể tự xóa tài khoản đang đăng nhập.'));
        }

        $this->userRepository->delete($id);

        return redirect()->route('admin_user')->with('success', __('Xóa tài khoản thành công.'));
    }
}
