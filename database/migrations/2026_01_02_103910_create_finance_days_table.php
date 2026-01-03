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
    }
};
