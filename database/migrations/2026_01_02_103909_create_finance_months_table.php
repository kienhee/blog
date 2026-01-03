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
        Schema::create('finance_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('year_id')->constrained('finance_years')->onDelete('cascade');
            $table->integer('total_money')->default(0)->comment('Số tiền lương của tháng');
            $table->integer('fix_money')->default(0)->comment('Số tiền có định để chi trả chi phí trong tháng cố định');
            $table->integer('invest_money')->default(0)->comment('Số tiền được mang đi đầu tư hoặc lưu trữ');
            $table->integer('remaining_money')->default(0)->comment('Số tiền cuối tháng còn lại sẽ được lưu lại');
            $table->json('note')->nullable()->comment('Note một vài mục đích chi tiêu của tháng');
            $table->timestamps();

            $table->index(['user_id', 'year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_months');
    }
};
