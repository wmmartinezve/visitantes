<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_records', function (Blueprint $table) {
            $table->uuid('client_id')->primary();
            $table->string('type', 64);
            $table->unsignedBigInteger('server_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['type', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_records');
    }
};
