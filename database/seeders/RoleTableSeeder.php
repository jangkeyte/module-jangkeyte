<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = array([
            'name' => 'Quản trị viên tối cao',
            'slug' => 'administrator',
            'description' => 'Quản trị viên có quyền điều hành cao nhất, người tạo ra hệ thống.',
            'code' => 'light text-dark',
        ],[
            'name' => 'Quản trị viên',
            'slug' => 'admin',
            'description' => 'Quản trị viên có quyền điều hành rất cao nắm quyền điều khiển toàn bộ hệ thống.',
            'code' => 'light text-dark',
        ],[
            'name' => 'Quản lý',
            'slug' => 'manager',
            'description' => 'Phụ trách quản lý và kiểm soát những người dùng trực thuộc bên dưới.',
            'code' => 'light text-dark',
        ],[
            'name' => 'Trưởng nhóm',
            'slug' => 'leader',
            'description' => 'Phụ trách nhân sự thuộc bộ phận công ty tiếp nhận.',
            'code' => 'light text-dark',
        ],[
            'name' => 'Người dùng',
            'slug' => 'user',
            'description' => 'Người dùng phụ trách đăng bài và kiểm soát các hoạt động công ty chỉ định.',
            'code' => 'light text-dark',
        ]);

        foreach($data as $item) {
            DB::table('roles')->insert([  
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'code' => $item['code'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
