<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('drone_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('altitude')->comment('meters');
            $table->unsignedTinyInteger('battery_level')->comment('0-100%');
            $table->unsignedTinyInteger('signal_strength')->nullable()->comment('0-100%');
            $table->float('speed')->nullable()->comment('m/s');
            $table->float('heading')->nullable()->comment('degrees 0-360');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['drone_id', 'recorded_at']);
            $table->index(['latitude', 'longitude']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_records');
    }
};
