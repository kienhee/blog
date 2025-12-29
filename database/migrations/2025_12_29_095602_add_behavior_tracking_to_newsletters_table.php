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
        Schema::table('newsletters', function (Blueprint $table) {
            $table->decimal('scroll_percentage', 5, 2)->nullable()->after('status')->comment('Phần trăm scroll của người dùng');
            $table->integer('time_on_page')->nullable()->after('scroll_percentage')->comment('Thời gian ở trên trang (giây)');
            $table->boolean('is_human')->default(true)->after('time_on_page')->comment('Đánh giá là người thật hay bot');
            $table->tinyInteger('spam_score')->default(0)->after('is_human')->comment('Điểm spam (0-100, càng cao càng spam)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropColumn(['scroll_percentage', 'time_on_page', 'is_human', 'spam_score']);
        });
    }
};
