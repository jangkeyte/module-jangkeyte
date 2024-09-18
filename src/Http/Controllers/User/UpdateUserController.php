<?php

namespace Modules\Authetication\src\Http\Controllers\User;

use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Http\Requests\User\UpdateUserRequest;
use Modules\Authetication\src\Repositories\User\UserRepositoryInterface;
use Modules\Authetication\src\Repositories\Role\RoleRepositoryInterface;
use Modules\Authetication\src\Repositories\Permission\PermissionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

class UpdateUserController extends Controller
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;
    protected $roleRepository;
    protected $permissionRepository;

    /**
     * UserController constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, RoleRepositoryInterface $roleRepository, PermissionRepositoryInterface $permissionRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Display the login view.
     */
    public function create($id): View
    {
        $user = $this->userRepository->find($id);
        $roles = $this->roleRepository->all()->toArray();
        return view('Authetication::user.update', [
            'user' => $user,
            'roles' => collect(Arr::pluck($roles, 'name', 'id')),
        ]);
    }

    /**
     * Handle an incoming POST request.
     */
    public function store(UpdateUserRequest $request): RedirectResponse
    {
        $user = $this->userRepository->find($request->id);
        if(isset($request->role)){
            $role = $this->roleRepository->find($request->role);
            $user->roles()->detach();
            $user->roles()->attach($role);
        }
        if(isset($request->permission)){
            $user->permissions()->detach();
            foreach($request->permission as $permission) {
                if(!$user->can($permission)) {
                    $user->givePermissionsTo($permission);
                }
            }
        }
        if($this->userRepository->updateUser($request))
            $message = 'Cập nhật tài khoản thành công!!!';
        else
            $message = 'Cập nhật tài khoản thất bại';
        return redirect()->route('admin_user')->with('success', $message);
    }

}
