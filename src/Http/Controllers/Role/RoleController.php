<?php

namespace Modules\Authetication\src\Http\Controllers\Role;

use Modules\Authetication\src\Http\Controllers\Controller;
use Modules\Authetication\src\Models\Permission;
use Modules\Authetication\src\Models\Role;
use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Repositories\User\UserRepositoryInterface;
use Modules\Authetication\src\Repositories\Role\RoleRepositoryInterface;
use Modules\Authetication\src\Repositories\Permission\PermissionRepositoryInterface;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{   
    protected $roleRepository;
    protected $permissionRepository;

    public function __construct(RoleRepositoryInterface $roleRepository, PermissionRepositoryInterface $permissionRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    public function index1()
    {
        $roles = $this->roleRepository->all();
        $permissions = $this->permissionRepository->all();     
        $objects = array(
            'user'              => 'User',
            'role'              => 'Role',
            'post'              => 'Post', 
            'company'           => 'Company', 
            'company-location'  => 'Company Location',
            'company-ndustry'   => 'Company Industry',
            'company-size'      => 'Company Size',
            'candidate'         => 'Candidate',
            'job'               => 'Job',  
            'job-category'      => 'Job Category',
            'job-location'      => 'Job Location',
            'job-type'          => 'Job Type',
            'job-experience'    => 'Job Experience',
            'job-gender'        => 'Job Gender',
            'job-salary-range'  => 'Job Salary Range',
            'setting'           => 'Setting',
            'package'           => 'Package',
            'banner'            => 'Banner',
            'subsciber'         => 'Subsciber',
            'advertisement'     => 'Advertisement',
            'testimonial'       => 'Testimonial',
            'faq'               => 'Faq',
            'why-choose'        => 'Why Choose',
            'home-page'         => 'Home Page',
        );

        return view('Authetication::role.dashboard', [
            'roles' => $roles,
            'permissions' => $permissions,
            'objects' => $objects,
        ]);
    }
    
    public function index()
    {
        $roles = $this->roleRepository->all();
        return view('Authetication::role.dashboard', compact('roles'));
    }

    public function create()
    {
        $roles = $this->roleRepository->all();
        $permissions = $this->permissionRepository->all();        
        $objects = array(
            'user'              => 'User',
            'role'              => 'Role',
            'post'              => 'Post', 
            'company'           => 'Company', 
            'company-location'  => 'Company Location',
            'company-ndustry'   => 'Company Industry',
            'company-size'      => 'Company Size',
            'candidate'         => 'Candidate',
            'job'               => 'Job',  
            'job-category'      => 'Job Category',
            'job-location'      => 'Job Location',
            'job-type'          => 'Job Type',
            'job-experience'    => 'Job Experience',
            'job-gender'        => 'Job Gender',
            'job-salary-range'  => 'Job Salary Range',
            'setting'           => 'Setting',
            'package'           => 'Package',
            'banner'            => 'Banner',
            'subsciber'         => 'Subsciber',
            'advertisement'     => 'Advertisement',
            'testimonial'       => 'Testimonial',
            'faq'               => 'Faq',
            'why-choose'        => 'Why Choose',
            'home-page'         => 'Home Page',
        );

        return view('Authetication::role.create', [
            'roles' => $roles,
            'permissions' => $permissions,
            'objects' => $objects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([            
            'name' => 'required',
            'slug' => 'required|alpha_dash|unique:posts',
            'description' => 'required',
            'code' => 'required',
        ]);

        $obj = new Role();
        
        $obj->name = $request->name;
        $obj->slug = $request->slug;
        $obj->description = $request->description;
        $obj->code = $request->code;
        $obj->save();

        foreach($request->permission as $permission) {            
            $new_permission = Permission::where('slug', $permission)->first();
            $obj->permissions()->attach($new_permission);
        }

        return redirect()->route('admin_role')->with('success', __('Data is saved successfully.'));
    }
    
    public function delete($id)
    {
        $role_single = Role::where('id', $id)->first();
        Role::where('id', $id)->delete();
        return redirect()->route('admin_role')->with('success', __('Data is deleted successfully.'));
    }
}