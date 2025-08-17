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
        Schema::create('telegram_settings', function (Blueprint $table) {
            $table->id();
            $table->text('welcome_message')->nullable();
            $table->text('order_message')->nullable();
            $table->text('order_not_found_message')->nullable();
            $table->text('telegram_order_already_delivered_message')->nullable();
            $table->timestamps();
        });
        
    }
    
    // Schema::table('orders', function (Blueprint $table) {
    //     $table->string('serial_number')->nullable();
    // });
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_settings');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
