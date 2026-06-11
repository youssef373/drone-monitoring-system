<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('boundary')->nullable()->comment('JSON polygon coordinates');
            $table->decimal('center_lat', 10, 8);
            $table->decimal('center_lng', 11, 8);
            $table->float('radius_meters')->nullable();
            $table->timestamps();

            $table->index(['center_lat', 'center_lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
