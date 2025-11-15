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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->nullable();
            $table->uuid('customer_id');
            $table->uuid('store_id')->nullable();
            $table->integer('total_price');
            $table->enum('payment_status', ['pending', 'paid', 'canceled'])->default('pending');
            $table->enum('order_status', ['draft', 'in_store', 'on_the_way', 'delivered'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->index('customer_id');
            $table->index('store_id');
            $table->index('order_status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

