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
        Schema::create('classifieds', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('description')->nullable();
            $table->text('condition')->nullable();
            $table->dateTime('date')->nullable();
            $table->decimal('price')->nullable();
            $table->text('full_price')->nullable();
            $table->text('full_address')->nullable();
            $table->text('zipcode')->nullable();
            $table->text('city')->nullable();
            $table->text('seller')->nullable();
            $table->text('seller_name')->nullable();
            $table->boolean('in_english')->default(false);
            $table->boolean('negotiable')->default(false);
            $table->boolean('top_promotion')->default(false);
            $table->boolean('shipping_possible')->default(false);
            $table->boolean('buy_directly')->default(false);
            $table->boolean('flagged')->default(false);
            $table->text('url');
            $table->timestamps();

            $table->unique('url');
            $table->index('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classifieds');
    }
};
