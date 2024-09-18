<?php

namespace Modules\Authetication\src\Http\Controllers\Role;

use Modules\Authetication\src\Models\Permission;
use Modules\Authetication\src\Models\Role;
use Modules\Authetication\src\Http\Controllers\Controller;
use Modules\Authetication\src\Http\Requests\Role\UpdateRoleRequest;
use Modules\Authetication\src\Repositories\User\UserRepositoryInterface;
use Modules\Authetication\src\Repositories\Role\RoleRepositoryInterface;
use Modules\Authetication\src\Repositories\Permission\PermissionRepositoryInterface;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UpdateRoleController extends Controller
{   
    /**
     * @var RoleRepositoryInterface
     */
    protected $roleRepository;
    protected $permissionRepository;

    /**
     * UpdateRoleController constructor.
     * 
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(RoleRepositoryInterface $roleRepository, PermissionRepositoryInterface $permissionRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    public function create($id): View
    {
        $role_single = Role::where('id', $id)->first();
        $permissions = $this->permissionRepository->all(); 
        return view('Authetication::role.edit', [
            'role_single' => $role_single,
            'permissions' => $permissions,
        ]);
    }

    public function store(UpdateRoleRequest $request, $id)
    {
        $obj = Role::where('id', $id)->first();

        $obj->name = $request->name;
        $obj->slug = $request->slug;
        $obj->description = $request->description;
        $obj->code = $request->code;
        $obj->update();

        $obj->permissions()->detach();
        if(isset($request->permission) && !empty($request->permission)){
            foreach($request->permission as $permission) {            
                $new_permission = Permission::where('slug', $permission)->first();
                $obj->permissions()->attach($new_permission);
            }
        }
        return redirect()->route('admin_role')->with('success', __('Data is updated successfully.'));
    }
    
}