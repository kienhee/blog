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
        // Tạo bảng finance_type trước (vì các bảng khác phụ thuộc vào nó)
        Schema::create('finance_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Ví dụ: mua quần áo, xăng xe, ăn uống, liên hoan, mua đồ...v.v');
            $table->timestamps();
        });

        // Tạo bảng finance_years
        Schema::create('finance_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('year');
            $table->json('target')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year']);
        });

        // Tạo bảng finance_months
        Schema::create('finance_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('year_id')->constrained('finance_years')->onDelete('cascade');
            $table->integer('month')->comment('Tháng (1-12)');
            $table->integer('total_money')->default(0)->comment('Số tiền lương của tháng');
            $table->integer('remaining_money')->default(0)->comment('Số tiền cuối tháng còn lại sẽ được lưu lại');
            $table->json('note')->nullable()->comment('Note một vài mục đích chi tiêu của tháng');
            $table->timestamp('locked_time')->nullable()->comment('Thời gian khóa tháng, không cho phép chỉnh sửa');
            $table->timestamps();

            $table->unique(['year_id', 'month'], 'finance_months_year_month_unique');
            $table->index(['user_id', 'year_id']);
        });

        // Tạo bảng finance_days
        Schema::create('finance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date')->comment('Thời gian');
            $table->foreignId('finance_type_id')->constrained('finance_type')->onDelete('cascade');
            $table->integer('money')->default(0)->comment('Số tiền');
            $table->foreignId('month_id')->constrained('finance_months')->onDelete('cascade');
            $table->string('note')->nullable()->comment('Ghi chú');
            $table->timestamps();

            $table->index(['user_id', 'month_id']);
            $table->index('date');
            $table->index('finance_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_days');
        Schema::dropIfExists('finance_months');
        Schema::dropIfExists('finance_years');
        Schema::dropIfExists('finance_type');
    }
};

