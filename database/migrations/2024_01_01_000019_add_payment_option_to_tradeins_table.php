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
        Schema::table('tradeins', function (Blueprint $table) {
            $table->enum('payment_option', ['cash', 'voucher', 'tradein'])->nullable()->after('final_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tradeins', function (Blueprint $table) {
            $table->dropColumn('payment_option');
        });
    }
};

