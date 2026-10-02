<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 管理员账号 Seeder
 *
 * 注册接口只创建学生，管理员通过本 Seeder 创建：php artisan db:seed
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('123456'),
                'role' => UserRole::ADMIN,
            ]
        );
    }
}
