<?php

use Illuminate\Support\Facades\Route;
use Modules\Authetication\src\Http\Controllers\User\UserController;
use Modules\Authetication\src\Http\Controllers\User\LoginController;
use Modules\Authetication\src\Http\Controllers\User\CreateUserController;
use Modules\Authetication\src\Http\Controllers\User\UpdateUserController;
use Modules\Authetication\src\Http\Controllers\User\SearchUserController;
use Modules\Authetication\src\Http\Controllers\User\ImportUserController;

use Modules\Authetication\src\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\Authetication\src\Http\Controllers\Auth\ConfirmablePasswordController;
use Modules\Authetication\src\Http\Controllers\Auth\PasswordController;
use Modules\Authetication\src\Http\Controllers\Auth\RegisteredUserController;
use Modules\Authetication\src\Http\Controllers\Permission\PermissionController;

use Modules\Authetication\src\Http\Controllers\Role\RoleController;
use Modules\Authetication\src\Http\Controllers\Role\CreateRoleController;
use Modules\Authetication\src\Http\Controllers\Role\UpdateRoleController;

use Modules\Authetication\src\Http\Controllers\Admin\AdminHomeController;
use Modules\Authetication\src\Http\Controllers\Admin\AdminLoginController;
use Modules\Authetication\src\Http\Controllers\Admin\AdminProfileController;
use Modules\Authetication\src\Http\Controllers\Admin\AdminHomePageController;

use Illuminate\Support\Facades\Auth;

Route::get('/auth/redirect/{provider}', 'SocialController@redirect');
Route::get('/callback/{provider}', 'SocialController@callback');

Route::group(['prefix' => 'login', 'middleware' => 'locale'], function() {
    Route::get('/', [LoginController::class, 'create'])->name('login');
    Route::post('/', [LoginController::class, 'store'])->name('login');
});


/* Admin Middleware */
Route::middleware(['auth'])->group(function() {

    // Đăng xuất
    Route::get('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::group(['prefix' => 'admin', 'middleware' => 'locale'], function() {

        Route::prefix('user')->group(function () {
            
            // Danh sách người dùng
            Route::get('/', [UserController::class, 'create'])
                ->name('admin_user')
                ->middleware('permission:browse-user');
            
            // Lấy thông tin người dùng
            Route::get('detail/{id}', [UserController::class, 'show'])
                ->middleware('permission:read-user')
                ->name('user.detail');
            
            // Tạo mới người dùng
            Route::get('create', [CreateUserController::class, 'create'])
                ->middleware('permission:add-user')
                ->name('user.create');
            Route::post('create', [CreateUserController::class, 'store'])
                ->middleware('permission:add-user')
                ->name('create.user');

            // Cập nhật người dùng
            Route::get('update/{id}', [UpdateUserController::class, 'create'])
                ->middleware('permission:edit-user')
                ->name('user.update');
            Route::post('update', [UpdateUserController::class, 'store'])
                ->middleware('permission:edit-user')
                ->name('update.user');

            Route::get('changepwd', [UserController::class, 'changepwd'])->name('user.changepwd');

            Route::get('remove/{id}', [UserController::class, 'destroy'])
                ->name('user.remove')
                ->middleware('permission:delete-user');
            
            // Tìm thông tin người dùng
            Route::get('search', [SearchUserController::class, 'create'])
                ->middleware('permission:browse-user')
                ->name('user.search');
            Route::post('search', [SearchUserController::class, 'store'])
                ->middleware('permission:browse-user')
                ->name('search.user');

        });
        
        /* ROLES */
        Route::prefix('role')->group(function () {
            Route::get('/view', [RoleController::class, 'index'])->middleware('permission:browse-role')->name('admin_role');
            Route::get('/create', [CreateRoleController::class, 'create'])->middleware('permission:add-role')->name('admin_role_create');
            Route::post('/store', [CreateRoleController::class, 'store'])->middleware('permission:add-role')->name('admin_role_store');
            Route::get('/edit/{id}', [UpdateRoleController::class, 'create'])->middleware('permission:edit-role')->name('admin_role_edit');
            Route::post('/update/{id}', [UpdateRoleController::class, 'store'])->middleware('permission:edit-role')->name('admin_role_update');
            Route::get('/delete/{id}', [RoleController::class, 'delete'])->middleware('permission:delete-role')->name('admin_role_delete');
        });
        
        Route::get('/edit-profile', [AdminProfileController::class, 'index'])->name('admin_profile');
        Route::post('/edit-profile-submit', [AdminProfileController::class, 'profile_submit'])->name('admin_profile_submit');

    });
});