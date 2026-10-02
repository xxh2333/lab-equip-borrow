<?php

namespace App\Enums;

/**
 * 设备状态枚举（devices.status）
 *
 * 两人协作约定：A、B 共用此枚举，禁止两边写魔数
 */
enum DeviceStatus: int
{
    /**
     * 可借
     */
    case AVAILABLE = 1;

    /**
     * 维护中
     */
    case MAINTAINING = 2;

    /**
     * 已下架
     */
    case OFF_SHELF = 3;

    public function msg(): string
    {
        return match ($this) {
            self::AVAILABLE => '可借',
            self::MAINTAINING => '维护中',
            self::OFF_SHELF => '已下架',
        };
    }
}
