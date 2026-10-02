<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingListRequest;
use App\Http\Requests\CreateBookingRequest;
use App\Services\BookingService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 借用申请模块（学生端，接口文档 三）
 */
class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    /**
     * 3.1 发起设备借用申请
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create($request->user(), $request->validated());

        return Result::success('申请提交成功，等待审核', [
            'id' => $booking->id,
            'device_id' => $booking->device_id,
            'status' => $booking->status->value,
        ]);
    }

    /**
     * 3.2 我的借用申请记录
     */
    public function my(BookingListRequest $request): JsonResponse
    {
        $data = $this->bookingService->myBookings(
            $request->user(),
            $request->validated('page'),
            $request->validated('page_size'),
        );

        return Result::success('获取成功', $data);
    }

    /**
     * 3.3 学生申请归还设备
     */
    public function returnDevice(Request $request, int $id): JsonResponse
    {
        $booking = $this->bookingService->returnDevice($request->user(), $id);

        return Result::success('归还成功', [
            'id' => $booking->id,
            'status' => $booking->status->value,
        ]);
    }
}
