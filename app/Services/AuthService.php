<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * 账户与安全模块业务逻辑
 */
class AuthService
{
    /**
     * 用户注册（注册成功默认学生角色，不返回 token）
     */
    public function register(array $data): User
    {
        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            // User 模型 password casts => hashed，自动 bcrypt 加密
            'password' => $data['password'],
            'role' => UserRole::STUDENT,
        ]);

        Log::channel('business')->info('用户注册成功', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return $user;
    }

    /**
     * 用户登录，返回 token 与用户信息
     *
     * remember=true 有效期30天，否则1天
     */
    public function login(string $username, string $password, bool $remember = false): array
    {
        $user = User::query()->where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new BusinessException('账号或密码错误');
        }

        $expiresAt = $remember ? now()->addDays(30) : now()->addDay();

        $token = $user->createToken('auth', expiresAt: $expiresAt);

        Log::channel('business')->info('用户登录成功', [
            'user_id' => $user->id,
            'remember' => $remember,
        ]);

        return [
            'token' => $token->plainTextToken,
            'user_info' => $this->userInfo($user),
        ];
    }

    /**
     * 退出登录：使当前 token 失效
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        Log::channel('business')->info('用户退出登录', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 修改个人资料（仅登录人自己）
     */
    public function updateProfile(User $user, string $name): User
    {
        $user->update(['name' => $name]);

        Log::channel('business')->info('用户修改个人资料', [
            'user_id' => $user->id,
            'name' => $name,
        ]);

        return $user->refresh();
    }

    /**
     * 用户信息统一输出结构（接口文档 1.2 / 1.4）
     */
    public function userInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role->value,
        ];
    }
}
