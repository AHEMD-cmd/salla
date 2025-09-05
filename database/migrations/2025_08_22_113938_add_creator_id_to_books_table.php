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
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->after('id');

            // Add foreign key constraint if you have a users table or creators table
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');

            // Add index for better query performance
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['creator_id']);

            // Drop the column
            $table->dropColumn('creator_id');
        });
    }
};
