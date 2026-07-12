<?php

use App\Support\ParroquiaDeduplicator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parroquias')) {
            return;
        }

        app(ParroquiaDeduplicator::class)->run();
    }

    public function down(): void
    {
        // Fusión de datos; no se revierte.
    }
};
