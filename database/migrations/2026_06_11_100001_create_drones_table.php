<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drones', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'emergency'])->default('active');
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->float('current_altitude')->nullable()->comment('meters');
            $table->unsignedTinyInteger('battery_level')->nullable()->comment('0-100%');
            $table->foreignId('geofence_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_telemetry_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'battery_level']);
            $table->index(['current_lat', 'current_lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drones');
    }
};
