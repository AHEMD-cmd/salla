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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_subscriber');
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');

            // Add foreign key constraint if you have a users table or creators table
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');

            // Add index for better query performance
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_subscriber');
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
