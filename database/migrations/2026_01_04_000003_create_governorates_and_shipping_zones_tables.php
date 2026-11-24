<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rate_cents');
            $table->unsignedInteger('free_threshold_cents')->nullable();
            $table->unsignedSmallInteger('delivery_days_min')->default(1);
            $table->unsignedSmallInteger('delivery_days_max')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('governorates');
    }
};
