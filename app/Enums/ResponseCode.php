<?php

namespace App\Enums;

/**
 * 统一响应码枚举
 *
 * 两位后端共用，谁发现 bug 谁修改，修改后告知对方
 */
enum ResponseCode: int
{
    /**
     * 成功
     */
    case SUCCESS = 0;

    /**
     * 参数异常
     */
    case PARAM_ERROR = 10001;

    /**
     * 未登录
     */
    case UNAUTHORIZED = 20001;

    /**
     * 无权限
     */
    case FORBIDDEN = 20002;

    /**
     * Token失效
     */
    case TOKEN_EXPIRED = 20004;

    /**
     * 数据不存在
     */
    case DATA_NOT_FOUND = 30001;

    /**
     * 数据重复
     */
    case DATA_DUPLICATED = 30003;

    /**
     * 业务异常
     */
    case BUSINESS_ERROR = 40001;

    /**
     * 当前状态不可操作
     */
    case STATUS_NOT_ALLOWED = 40002;

    /**
     * 库存不足
     */
    case STOCK_NOT_ENOUGH = 40004;

    /**
     * 数据库异常
     */
    case DATABASE_ERROR = 60001;

    /**
     * 系统异常
     */
    case SYSTEM_ERROR = 90001;

    public function msg(): string
    {
        return match ($this) {
            self::SUCCESS => '操作成功',
            self::PARAM_ERROR => '参数错误',
            self::UNAUTHORIZED => '未登录',
            self::FORBIDDEN => '无权限访问',
            self::TOKEN_EXPIRED => '登录已过期',
            self::DATA_NOT_FOUND => '记录不存在',
            self::DATA_DUPLICATED => '数据重复',
            self::BUSINESS_ERROR => '业务处理失败',
            self::STATUS_NOT_ALLOWED => '当前状态不可操作',
            self::STOCK_NOT_ENOUGH => '库存不足',
            self::DATABASE_ERROR => '数据库异常',
            self::SYSTEM_ERROR => '系统异常，请联系管理员',
        };
    }
}
