<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('drone_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['low_battery', 'critical_battery', 'geofence_violation', 'signal_loss', 'emergency']);
            $table->string('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['drone_id', 'type', 'resolved_at']);
            $table->index(['severity', 'created_at']);
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
