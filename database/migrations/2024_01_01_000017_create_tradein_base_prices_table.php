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
        Schema::create('tradein_base_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('brand');
            $table->string('model');
            $table->string('storage');
            $table->integer('base_price');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand', 'model', 'storage']);
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tradein_base_prices');
    }
};

