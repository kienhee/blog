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
        Schema::create('finance_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Ví dụ: mua quần áo, xăng xe, ăn uống, liên hoan, mua đồ...v.v');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_type');
    }
};
