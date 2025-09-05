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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // رقم الطلب
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->date('start_date'); // تاريخ البدء
            $table->date('end_date'); // تاريخ الانتهاء
            $table->boolean('auto_renewal')->default(false); // تجديد تلقائي
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active'); 
            // نشط / موقّف مؤقت / ملغي
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};