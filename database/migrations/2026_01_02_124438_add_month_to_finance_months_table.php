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
        Schema::table('finance_months', function (Blueprint $table) {
            $table->integer('month')->after('year_id')->comment('Tháng (1-12)');
            
            // Thêm unique constraint để đảm bảo mỗi năm chỉ có 1 tháng mỗi số
            $table->unique(['year_id', 'month'], 'finance_months_year_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_months', function (Blueprint $table) {
            $table->dropUnique('finance_months_year_month_unique');
            $table->dropColumn('month');
        });
    }
};
