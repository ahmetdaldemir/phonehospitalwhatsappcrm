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
        Schema::create('tradeins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('brand');
            $table->string('model');
            $table->string('storage')->nullable();
            $table->string('color')->nullable();
            $table->enum('condition', ['A', 'B', 'C'])->default('B');
            $table->integer('battery_health')->nullable();
            $table->json('photos')->nullable();
            $table->integer('offer_min')->nullable();
            $table->integer('offer_max')->nullable();
            $table->uuid('store_id')->nullable();
            $table->enum('status', ['new', 'waiting_device', 'completed', 'canceled'])->default('new');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->index('customer_id');
            $table->index('store_id');
            $table->index('status');
            $table->index(['brand', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tradeins');
    }
};

