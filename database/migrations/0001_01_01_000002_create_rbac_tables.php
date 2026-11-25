<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('group')->index();
                $table->string('description')->nullable();
                $table->string('code')->default('info');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->string('code')->default('light text-dark');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users_permissions')) {
            Schema::create('users_permissions', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['user_id', 'permission_id']);
            });
        }

        if (! Schema::hasTable('users_roles')) {
            Schema::create('users_roles', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['user_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('roles_permissions')) {
            Schema::create('roles_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['role_id', 'permission_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permissions');
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('users_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
