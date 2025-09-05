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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('desc')->nullable();
            $table->integer('duration'); // in days
            $table->integer('number_of_files')->nullable(); // null means unlimited
            $table->boolean('unlimited_files')->default(false);
            $table->integer('number_of_downloads')->nullable(); // null means unlimited
            $table->boolean('unlimited_downloads')->default(false);
            $table->boolean('telegram_status')->default(false);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};