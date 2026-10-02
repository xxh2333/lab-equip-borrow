<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 模块三：借用申请模块自测（接口文档 三）
 *
 * 注意：devices 表由后端B负责迁移（尚未合并），
 * 此处用 Schema 临时创建测试 fixture，B 的迁移合并后应删除本 fixture。
 */
class BookingTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // ===== 临时 devices 表 fixture（B 迁移合并后删除） =====
        if (! Schema::hasTable('devices')) {
            Schema::create('devices', function ($table) {
                $table->id();
                $table->string('name')->comment('设备名称');
                $table->string('category')->comment('设备分类');
                $table->text('description')->nullable()->comment('设备描述');
                $table->unsignedInteger('total_qty')->default(1)->comment('总库存');
                $table->unsignedTinyInteger('status')->default(1)->comment('1可借2维护中3已下架');
                $table->timestamps();
            });
        }

        $this->user = User::factory()->create();
    }

    /**
     * 测试辅助：创建设备，返回设备id
     */
    private function createDevice(array $overrides = []): int
    {
        return (int) DB::table('devices')->insertGetId(array_merge([
            'name' => '示波器',
            'category' => '电子仪器',
            'description' => '数字示波器',
            'total_qty' => 5,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * 测试辅助：创建借用单
     */
    private function createBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $this->user->id,
            'device_id' => $this->createDevice(),
            'start_time' => Carbon::today()->toDateString(),
            'end_time' => Carbon::today()->addDays(3)->toDateString(),
            'purpose' => '课程实验',
            'status' => BookingStatus::APPROVED->value,
        ], $overrides));
    }

    // ========== 3.1 发起借用申请 ==========

    public function test_store_success(): void
    {
        $deviceId = $this->createDevice();

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
                'purpose' => '课程实验',
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.device_id', $deviceId)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['trace_id']);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'device_id' => $deviceId,
            'status' => 'pending',
        ]);
    }

    public function test_store_requires_login(): void
    {
        $res = $this->postJson('/api/bookings', ['device_id' => 1]);

        $res->assertOk()->assertJsonPath('code', 20001);
    }

    public function test_store_device_not_exists(): void
    {
        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => 99999,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 10001)
            ->assertJsonPath('msg', '设备不存在');
    }

    public function test_store_start_time_before_today(): void
    {
        $deviceId = $this->createDevice();

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::yesterday()->toDateString(),
                'end_time' => Carbon::today()->toDateString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 10001)
            ->assertJsonPath('msg', '借用开始日期不能早于今天');
    }

    public function test_store_end_time_before_start_time(): void
    {
        $deviceId = $this->createDevice();

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::yesterday()->toDateString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 10001)
            ->assertJsonPath('msg', '借用结束日期不能早于开始日期');
    }

    public function test_store_device_maintaining_rejected(): void
    {
        $deviceId = $this->createDevice(['status' => 2]); // 维护中

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 40001)
            ->assertJsonPath('msg', '该设备当前不可借用');
    }

    public function test_store_device_off_shelf_rejected(): void
    {
        $deviceId = $this->createDevice(['status' => 3]); // 已下架

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()->assertJsonPath('code', 40001);
    }

    public function test_store_stock_not_enough_when_approved_occupied(): void
    {
        // 库存1台，已被他人 approved 占用
        $other = User::factory()->create();
        $deviceId = $this->createDevice(['total_qty' => 1]);
        $this->createBooking([
            'user_id' => $other->id,
            'device_id' => $deviceId,
            'status' => BookingStatus::APPROVED->value,
        ]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()
            ->assertJsonPath('code', 40004)
            ->assertJsonPath('msg', '该设备当前无可用库存，请选择其他时间或设备');
    }

    public function test_store_stock_not_enough_when_pending_occupied(): void
    {
        // 库存1台，pending 待审核也占用库存（已对齐的库存口径）
        $other = User::factory()->create();
        $deviceId = $this->createDevice(['total_qty' => 1]);
        $this->createBooking([
            'user_id' => $other->id,
            'device_id' => $deviceId,
            'status' => BookingStatus::PENDING->value,
        ]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()->assertJsonPath('code', 40004);
    }

    public function test_store_returned_booking_not_occupy_stock(): void
    {
        // 库存1台，已归还的申请不占用库存，可以再次申请
        $deviceId = $this->createDevice(['total_qty' => 1]);
        $this->createBooking([
            'device_id' => $deviceId,
            'status' => BookingStatus::RETURNED->value,
        ]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings', [
                'device_id' => $deviceId,
                'start_time' => Carbon::today()->toDateString(),
                'end_time' => Carbon::today()->addDays(3)->toDateString(),
            ]);

        $res->assertOk()->assertJsonPath('code', 0);
    }

    // ========== 3.2 我的借用记录 ==========

    public function test_my_bookings_list(): void
    {
        $deviceId = $this->createDevice(['name' => '信号发生器']);
        $mine = $this->createBooking(['device_id' => $deviceId, 'status' => BookingStatus::PENDING->value]);

        // 其他用户的申请不应出现在我的列表
        $other = User::factory()->create();
        $this->createBooking(['user_id' => $other->id, 'device_id' => $deviceId]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/bookings/my?page=1&page_size=10');

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.page', 1)
            ->assertJsonPath('data.page_size', 10)
            ->assertJsonPath('data.list.0.id', $mine->id)
            ->assertJsonPath('data.list.0.device_name', '信号发生器')
            ->assertJsonPath('data.list.0.status', 'pending');
    }

    public function test_my_bookings_requires_login(): void
    {
        $res = $this->getJson('/api/bookings/my');

        $res->assertOk()->assertJsonPath('code', 20001);
    }

    // ========== 3.3 归还设备 ==========

    public function test_return_success(): void
    {
        $booking = $this->createBooking(['status' => BookingStatus::APPROVED->value]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/return");

        $res->assertOk()
            ->assertJsonPath('code', 0)
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.status', 'returned');

        $this->assertSame('returned', $booking->refresh()->status->value);
    }

    public function test_return_not_own_booking_forbidden(): void
    {
        $other = User::factory()->create();
        $booking = $this->createBooking(['user_id' => $other->id]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/return");

        $res->assertOk()
            ->assertJsonPath('code', 20002)
            ->assertJsonPath('msg', '无权操作该申请单');
    }

    public function test_return_pending_booking_rejected(): void
    {
        $booking = $this->createBooking(['status' => BookingStatus::PENDING->value]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/return");

        $res->assertOk()
            ->assertJsonPath('code', 40002)
            ->assertJsonPath('msg', '当前申请状态不可归还');
    }

    public function test_return_already_returned_rejected(): void
    {
        $booking = $this->createBooking(['status' => BookingStatus::RETURNED->value]);

        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/return");

        $res->assertOk()->assertJsonPath('code', 40002);
    }

    public function test_return_booking_not_exists(): void
    {
        $res = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/bookings/99999/return');

        $res->assertOk()
            ->assertJsonPath('code', 30001)
            ->assertJsonPath('msg', '申请单不存在');
    }
}
