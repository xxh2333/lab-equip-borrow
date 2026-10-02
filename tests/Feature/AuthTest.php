<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 模块一：账户与安全模块自测（接口文档 一）
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $registerData = [
        'username' => '25010220113',
        'name' => '张三',
        'password' => '123456',
    ];

    // ========== 1.1 用户注册 ==========

    public function test_register_success(): void
    {
        $res = $this->postJson('/api/auth/register', $this->registerData);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', '25010220113')
            ->assertJsonPath('data.name', '张三')
            ->assertJsonPath('data.role', 1)
            ->assertJsonStructure(['trace_id']);

        // 不返回 token
        $this->assertArrayNotHasKey('token', $res->json('data'));

        // 密码 bcrypt 加密存储
        $user = User::where('username', '25010220113')->first();
        $this->assertNotSame('123456', $user->password);
    }

    public function test_register_duplicate_username(): void
    {
        User::factory()->create(['username' => '25010220113']);

        $res = $this->postJson('/api/auth/register', $this->registerData);

        $res->assertOk()
            ->assertJsonPath('code', 10001)
            ->assertJsonPath('msg', '该账号已被注册')
            ->assertJsonPath('success', false);
    }

    public function test_register_missing_params(): void
    {
        $res = $this->postJson('/api/auth/register', ['username' => '25010220113']);

        $res->assertOk()
            ->assertJsonPath('code', 10001)
            ->assertJsonPath('success', false);
    }

    // ========== 1.2 用户登录 ==========

    public function test_login_success(): void
    {
        User::factory()->create($this->registerData);

        $res = $this->postJson('/api/auth/login', [
            'username' => '25010220113',
            'password' => '123456',
        ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.user_info.username', '25010220113')
            ->assertJsonPath('data.user_info.role', 1)
            ->assertJsonStructure(['data' => ['token', 'user_info'], 'trace_id']);

        $this->assertNotEmpty($res->json('data.token'));
    }

    public function test_login_wrong_password(): void
    {
        User::factory()->create($this->registerData);

        $res = $this->postJson('/api/auth/login', [
            'username' => '25010220113',
            'password' => 'wrong-password',
        ]);

        $res->assertOk()
            ->assertJsonPath('code', 40001)
            ->assertJsonPath('msg', '账号或密码错误')
            ->assertJsonPath('success', false);
    }

    public function test_login_user_not_exists(): void
    {
        $res = $this->postJson('/api/auth/login', [
            'username' => 'nobody',
            'password' => '123456',
        ]);

        $res->assertOk()->assertJsonPath('code', 40001);
    }

    // ========== 1.3 退出登录 / 1.4 获取当前用户 ==========

    public function test_me_requires_token(): void
    {
        $res = $this->getJson('/api/auth/me');

        $res->assertOk()->assertJsonPath('code', 20001);
    }

    public function test_me_with_token(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me');

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', $user->username)
            ->assertJsonPath('data.role', 1);
    }

    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create();

        $login = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);
        $token = $login->json('data.token');

        // 退出登录
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');
        $res->assertOk()->assertJsonPath('code', 0);

        // 原 token 失效
        \Laravel\Sanctum\Sanctum::authenticateAccessTokensUsing(function ($accessToken, $isValid) {
            dump('token auth callback fired, token id:', $accessToken?->id, 'isValid:', $isValid);

            return $isValid;
        });
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');
        dump('me response: ', $res->json());
        $res->assertOk()->assertJsonPath('code', 20001);
    }

    // ========== 1.5 修改个人资料 ==========

    public function test_profile_update(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', ['name' => '李四']);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.name', '李四');

        $this->assertSame('李四', $user->refresh()->name);
    }

    public function test_profile_requires_login(): void
    {
        $res = $this->putJson('/api/auth/profile', ['name' => '李四']);

        $res->assertOk()->assertJsonPath('code', 20001);
    }
}
