<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // 学号/邮箱，账号唯一（接口文档 1.1）
            $table->string('username')->unique()->comment('账号：学号/邮箱');
            $table->string('name')->comment('用户真实姓名');
            $table->string('password')->comment('密码bcrypt加密');
            // 角色：1学生，2管理员（接口文档 1.1）
            $table->unsignedTinyInteger('role')->default(1)->comment('角色：1学生，2管理员');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
