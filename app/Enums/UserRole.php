<?php

namespace App\Enums;

/**
 * 用户角色枚举（users.role）
 */
enum UserRole: int
{
    /**
     * 学生
     */
    case STUDENT = 1;

    /**
     * 管理员
     */
    case ADMIN = 2;

    public function msg(): string
    {
        return match ($this) {
            self::STUDENT => '学生',
            self::ADMIN => '管理员',
        };
    }
}
