<?php

use Illuminate\Support\Facades\Route;
use JangKeyte\Http\Controllers\Auth\LoginController;
use JangKeyte\Http\Controllers\Auth\ProfileController;
use JangKeyte\Http\Controllers\CoreController;
use JangKeyte\Http\Controllers\Media\MediaController;
use JangKeyte\Http\Controllers\Role\RoleController;
use JangKeyte\Http\Controllers\Setting\SettingController;
use JangKeyte\Http\Controllers\Studio\ModuleStudioController;
use JangKeyte\Http\Controllers\User\UserController;

Route::middleware(['web', 'locale'])->group(function (): void {
    // Language Switcher
    Route::get('/switch-language/{language}', [CoreController::class, 'switchLanguage'])
        ->name('switch_language');

    // Guest / Auth Routes (Distinct route names for route:cache!)
    Route::prefix('login')->group(function (): void {
        Route::get('/', [LoginController::class, 'create'])->name('login');
        Route::post('/', [LoginController::class, 'store'])->name('login.submit');
    });

    // Authenticated Routes
    Route::middleware(['auth'])->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        // Central Composable Dashboard (delegates to DashboardHandlerInterface)
        Route::get('/dashboard', [CoreController::class, 'dashboard'])
            ->middleware('permission:browse-dashboard')
            ->name('dashboard');

        Route::prefix('admin')->group(function (): void {
            // Self Profile
            Route::get('/edit-profile', [ProfileController::class, 'edit'])->name('admin_profile');
            Route::post('/edit-profile', [ProfileController::class, 'update'])->name('admin_profile_submit');

            // User Management
            Route::prefix('user')->group(function (): void {
                Route::get('/', [UserController::class, 'index'])
                    ->middleware('permission:browse-user')
                    ->name('admin_user');
                Route::get('/detail/{id}', [UserController::class, 'show'])
                    ->middleware('permission:read-user')
                    ->name('user.detail');
                Route::get('/create', [UserController::class, 'create'])
                    ->middleware('permission:add-user')
                    ->name('user.create');
                Route::post('/create', [UserController::class, 'store'])
                    ->middleware('permission:add-user')
                    ->name('create.user');
                Route::get('/update/{id}', [UserController::class, 'edit'])
                    ->middleware('permission:edit-user')
                    ->name('user.update');
                Route::put('/update/{id}', [UserController::class, 'update'])
                    ->middleware('permission:edit-user')
                    ->name('update.user');
                Route::delete('/remove/{id}', [UserController::class, 'destroy'])
                    ->middleware('permission:delete-user')
                    ->name('user.remove');
            });

            // Role & Permission Management
            Route::prefix('role')->group(function (): void {
                Route::get('/view', [RoleController::class, 'index'])
                    ->middleware('permission:browse-role')
                    ->name('admin_role');
                Route::get('/create', [RoleController::class, 'create'])
                    ->middleware('permission:add-role')
                    ->name('admin_role_create');
                Route::post('/store', [RoleController::class, 'store'])
                    ->middleware('permission:add-role')
                    ->name('admin_role_store');
                Route::get('/edit/{id}', [RoleController::class, 'edit'])
                    ->middleware('permission:edit-role')
                    ->name('admin_role_edit');
                Route::put('/update/{id}', [RoleController::class, 'update'])
                    ->middleware('permission:edit-role')
                    ->name('admin_role_update');
                Route::delete('/delete/{id}', [RoleController::class, 'destroy'])
                    ->middleware('permission:delete-role')
                    ->name('admin_role_delete');
            });

            // Site Settings Management
            Route::prefix('settings')->group(function (): void {
                Route::get('/', [SettingController::class, 'index'])
                    ->middleware('permission:browse-setting')
                    ->name('admin.settings.index');
                Route::put('/', [SettingController::class, 'update'])
                    ->middleware('permission:edit-setting')
                    ->name('admin.settings.update');
                Route::post('/store', [SettingController::class, 'store'])
                    ->middleware('permission:add-setting')
                    ->name('admin.settings.store');
            });

            // Centralized Media Manager
            Route::prefix('media')->group(function (): void {
                Route::get('/', [MediaController::class, 'index'])
                    ->middleware('permission:browse-media')
                    ->name('admin.media.index');
                Route::post('/folder', [MediaController::class, 'createFolder'])
                    ->middleware('permission:add-media')
                    ->name('admin.media.folder');
                Route::post('/upload', [MediaController::class, 'upload'])
                    ->middleware('permission:add-media')
                    ->name('admin.media.upload');
                Route::delete('/delete', [MediaController::class, 'destroy'])
                    ->middleware('permission:delete-media')
                    ->name('admin.media.delete');
            });

            // Visual Module & CRUD Studio
            Route::prefix('modules')->group(function (): void {
                Route::get('/', [ModuleStudioController::class, 'index'])
                    ->middleware('permission:browse-module')
                    ->name('admin.modules.index');
                Route::get('/create-crud', [ModuleStudioController::class, 'createCrud'])
                    ->middleware('jangkeyte.builder')
                    ->name('admin.modules.create_crud');
                Route::post('/store-crud', [ModuleStudioController::class, 'storeCrud'])
                    ->middleware('jangkeyte.builder')
                    ->name('admin.modules.store_crud');
                Route::patch('/{module}/toggle', [ModuleStudioController::class, 'toggle'])
                    ->where('module', '[A-Z][A-Za-z0-9]*')
                    ->middleware('jangkeyte.builder')
                    ->name('admin.modules.toggle');
                Route::delete('/{module}', [ModuleStudioController::class, 'destroy'])
                    ->where('module', '[A-Z][A-Za-z0-9]*')
                    ->middleware('jangkeyte.builder')
                    ->name('admin.modules.destroy');
            });
        });
    });
});
