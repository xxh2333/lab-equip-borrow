<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * bookings 表由后端A建表维护；审核逻辑由后端B在Service中处理（协作约定）
     * device_id 关联 devices 表（后端B维护），只加索引不加外键，避免迁移顺序依赖
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('申请人用户id');
            $table->unsignedBigInteger('device_id')->index()->comment('设备id');
            $table->date('start_time')->comment('借用开始日期');
            $table->date('end_time')->comment('借用结束日期');
            $table->string('purpose', 255)->nullable()->comment('用途说明');
            // 状态：pending待审核/approved已通过/rejected已拒绝/returned已归还
            $table->string('status', 20)->default('pending')->index()->comment('申请状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
