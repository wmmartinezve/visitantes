<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role', 32)->nullable();
            $table->morphs('subject');
            $table->string('action', 64);
            $table->string('channel', 32);
            $table->string('client_id', 64)->nullable();
            $table->uuid('batch_id')->nullable();
            $table->json('properties')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
