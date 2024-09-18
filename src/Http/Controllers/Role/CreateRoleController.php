<?php

namespace Modules\Authetication\src\Http\Controllers\Role;

use Modules\Authetication\src\Http\Controllers\Controller;
use Modules\Authetication\src\Models\Permission;
use Modules\Authetication\src\Models\Role;
use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Http\Requests\Role\CreateRoleRequest;
use Modules\Authetication\src\Repositories\User\UserRepositoryInterface;
use Modules\Authetication\src\Repositories\Role\RoleRepositoryInterface;
use Modules\Authetication\src\Repositories\Permission\PermissionRepositoryInterface;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CreateRoleController extends Controller
{   
    /**
     * @var RoleRepositoryInterface
     */
    protected $userRepository;
    protected $roleRepository;
    protected $permissionRepository;

    /**
     * CreateRoleController constructor.
     * 
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, RoleRepositoryInterface $roleRepository, PermissionRepositoryInterface $permissionRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Display the create view.
     */
    public function create(): View
    {
        $permissions = $this->permissionRepository->all();
        return view('Authetication::role.create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Handle an incoming POST request.
     */
    public function store(CreateRoleRequest $request): RedirectResponse
    {
        //RoleTableSeeder.php
        $obj = new Role();
        $obj->slug = $request->slug;
        $obj->name = $request->name;
        $obj->description = $request->description ?? 'Chưa có mô tả';
        $obj->code = $request->code ?? 'dark';
        $obj->save();
        if(isset($request->permission) && !empty($request->permission)){
            foreach($request->permission as $permission) {            
                $new_permission = Permission::where('slug', $permission)->first();
                $obj->permissions()->attach($new_permission);
            }
        }
        return redirect()->route('admin_role')->with('success', 'Thêm role thành công');
    }
}