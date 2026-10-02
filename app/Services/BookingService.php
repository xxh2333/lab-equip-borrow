<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DeviceStatus;
use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 借用申请模块业务逻辑（学生端，接口文档 三）
 */
class BookingService
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * 3.1 发起设备借用申请
     */
    public function create(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // 锁定设备行，防止并发超借（devices 为B维护的表，此处只读）
            $device = DB::table('devices')
                ->where('id', $data['device_id'])
                ->lockForUpdate()
                ->first();

            if (! $device) {
                throw new BusinessException('设备不存在', ResponseCode::DATA_NOT_FOUND);
            }

            // 校验设备状态必须可借（维护中/已下架阻止新申请）
            if ((int) $device->status !== DeviceStatus::AVAILABLE->value) {
                throw new BusinessException('该设备当前不可借用');
            }

            // 校验可用库存（pending + approved 占用）
            if ($this->stockService->availableQty($device) < 1) {
                throw new BusinessException(
                    '该设备当前无可用库存，请选择其他时间或设备',
                    ResponseCode::STOCK_NOT_ENOUGH
                );
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'] ?? null,
                'status' => BookingStatus::PENDING->value,
            ]);

            Log::channel('business')->info('学生提交借用申请', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'device_id' => $device->id,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);

            return $booking;
        });
    }

    /**
     * 3.2 我的借用申请记录（分页，关联设备名称）
     */
    public function myBookings(User $user, int $page = 1, int $pageSize = 10): array
    {
        $paginator = Booking::query()
            ->where('user_id', $user->id)
            ->join('devices', 'devices.id', '=', 'bookings.device_id')
            ->select(['bookings.*', 'devices.name as device_name'])
            ->orderByDesc('bookings.id')
            ->paginate($pageSize, page: $page);

        return [
            'list' => collect($paginator->items())->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'device_name' => $booking->device_name,
                'start_time' => $booking->start_time->toDateString(),
                'end_time' => $booking->end_time->toDateString(),
                'purpose' => $booking->purpose,
                'status' => $booking->status->value,
                'created_at' => $booking->created_at->toDateTimeString(),
            ])->all(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
        ];
    }

    /**
     * 3.3 学生申请归还设备
     */
    public function returnDevice(User $user, int $bookingId): Booking
    {
        return DB::transaction(function () use ($user, $bookingId) {
            $booking = Booking::query()->lockForUpdate()->find($bookingId);

            if (! $booking) {
                throw new BusinessException('申请单不存在', ResponseCode::DATA_NOT_FOUND);
            }

            // 权限校验：只能操作自己的申请
            if ($booking->user_id !== $user->id) {
                throw new BusinessException('无权操作该申请单', ResponseCode::FORBIDDEN);
            }

            // 业务校验：只有 approved 状态允许归还
            if ($booking->status !== BookingStatus::APPROVED) {
                throw new BusinessException(
                    '当前申请状态不可归还',
                    ResponseCode::STATUS_NOT_ALLOWED
                );
            }

            $booking->status = BookingStatus::RETURNED;
            $booking->save();

            Log::channel('business')->info('学生归还设备', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'device_id' => $booking->device_id,
            ]);

            return $booking;
        });
    }
}
