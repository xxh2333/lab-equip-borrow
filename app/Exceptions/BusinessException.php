<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use Exception;

/**
 * 业务异常
 *
 * Service 层业务校验失败时主动抛出，由 Handler 统一渲染
 */
class BusinessException extends Exception
{
    public readonly ResponseCode $codeEnum;

    public function __construct(string $message, ResponseCode $code = ResponseCode::BUSINESS_ERROR)
    {
        $this->codeEnum = $code;

        parent::__construct($message, $code->value);
    }
}
