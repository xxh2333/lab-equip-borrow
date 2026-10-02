<?php

namespace App\Services;

use App\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

/**
 * 设备库存计算服务
 *
 * ⚠️ 两人协作共用：A（提交申请校验）与B（设备列表 available_qty 实时计算）
 * 必须共用本类，保证库存口径一致。谁发现 bug 谁修改，修改后告知对方。
 *
 * 库存口径（已对齐）：可借数量 = total_qty - 占用数量
 * 占用数量 = 状态为 pending（待审核）+ approved（未归还）的申请单数
 */
class StockService
{
    /**
     * 计算设备当前可借数量
     *
     * @param object $device 设备行（需包含 id、total_qty 字段）
     */
    public function availableQty(object $device): int
    {
        $occupied = DB::table('bookings')
            ->where('device_id', $device->id)
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::APPROVED->value,
            ])
            ->count();

        return max(0, (int) $device->total_qty - $occupied);
    }
}
