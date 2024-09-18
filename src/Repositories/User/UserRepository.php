<?php

namespace Modules\Authetication\src\Repositories\User;

use Modules\Authetication\src\Models\User;
use Modules\Authetication\src\Filters\UserFilter;
use Modules\Authetication\src\Repositories\BaseRepository;
use Modules\Authetication\src\Http\Requests\User\CreateUserRequest;
use Modules\Authetication\src\Http\Requests\User\UpdateUserRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

use Modules\Authetication\src\Exports\UserExport;
use Modules\Authetication\src\Imports\UserImport;
use Maatwebsite\Excel\Facades\Excel;


class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * @var User
     */
    protected $model;

    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get's all users.
     *
     * @return mixed
     */
    public function all()
    {
        return $this->model->paginate(99);
    }
  
    public function createUser(CreateUserRequest $request)
    {        
        $imageUrl = storeImage($request);
        $user = $request->all();
        $user['photo'] = $imageUrl;
        $user['uid'] = Str::random(36);
        $user['username'] = $user['email'];
        $user['token'] = Str::random(2048);
        $user['password'] = Hash::make($user['password']);
        return $this->model->create($user);
    }

    /**
     * Create an microsoft user
     * @param array $data
     * @return mixed
     */
    public function createMsUser($microsoftUser)
    {
        $user = new User();
        $user->uid = $microsoftUser->id;
        $user->username = $microsoftUser->email;
        $user->name = $microsoftUser->name;
        $user->photo = 'default.jpg';
        $user->email = $microsoftUser->email;
        $user->password = Hash::make('KTX@12345');
        $user->token = $microsoftUser->token;
        $user->save();
        return $user;
    }

    /**
     * Update an user
     * @param request $request
     * @return mixed
     */
    public function updateUser(UpdateUserRequest $request)
    {
        $request->name != '' ? $data['name'] = $request->name : '';
        $request->email != '' ? $data['email'] = $request->email : '';
        $request->image != '' ? $data['photo'] = storeImage($request) : '';
        (isset($request->password) && $request->password != null) ? $data['password'] = Hash::make($request->password) : '';

        return $this->model->find($request->id)->update($data);
    }
    
    /**
     * Get's list of user by condition
     *
     * @param array
     * @return collection
     */
    public function search(Request $request)
    {
        return $this->model->filter(new UserFilter($request))->paginate(99);
    }

    /**
     * Get's list of user by condition
     *
     * @param array
     * @return collection
     */
    public function searchByCondition($key, $value)
    {
        return $this->model->where($key, $value)->first();
    }

    /**
     * Export all user
     *
     * @return bool
     */
    public function export()
    {
        
    }
    
    /**
     * Export list of user by condition
     *
     * @param condition
     * @return bool
     */
    public function exportByCondition(Request $request)
    {
        
    }

    /**
     * Import users from file upload
     *
     * @param request
     * @return bool
     */
    public function import(Request $request)
    {
        
    }
    
}