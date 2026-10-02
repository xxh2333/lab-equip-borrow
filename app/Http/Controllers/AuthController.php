<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 账户与安全模块（接口文档 一）
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * 1.1 用户注册
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return Result::success('注册成功', $this->authService->userInfo($user));
    }

    /**
     * 1.2 用户登录
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login(
            $request->validated('username'),
            $request->validated('password'),
            $request->validated('remember', false),
        );

        return Result::success('登录成功', $data);
    }

    /**
     * 1.3 退出登录
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return Result::success('退出成功');
    }

    /**
     * 1.4 获取当前登录用户信息
     */
    public function me(Request $request): JsonResponse
    {
        // TODO: 临时调试，确认后删除
        dump('guard class:', get_class(\Auth::guard('sanctum')));
        dump('default guard:', \Auth::getDefaultDriver());

        return Result::success('获取成功', $this->authService->userInfo($request->user()));
    }

    /**
     * 1.5 修改个人资料
     */
    public function profile(ProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated('name'));

        return Result::success('修改成功', $this->authService->userInfo($user));
    }
}
