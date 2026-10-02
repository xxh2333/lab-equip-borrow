<?php

namespace App\Enums;

/**
 * 借用单状态枚举（bookings.status）
 *
 * 两人协作约定：A、B 共用此枚举，禁止两边写魔数
 */
enum BookingStatus: string
{
    /**
     * 待审核
     */
    case PENDING = 'pending';

    /**
     * 已通过
     */
    case APPROVED = 'approved';

    /**
     * 已拒绝
     */
    case REJECTED = 'rejected';

    /**
     * 已归还
     */
    case RETURNED = 'returned';

    /**
     * 是否占用库存（协作约定：pending + approved 均占用库存）
     */
    public function occupyStock(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED], true);
    }
}
