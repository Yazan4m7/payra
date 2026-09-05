<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('token_hash', 64);
            $table->string('timezone')->default('Asia/Amman');
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('biometric_employee_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained('biometric_devices')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('external_employee_id');
            $table->timestamps();
            $table->unique(['biometric_device_id', 'external_employee_id'], 'bio_map_device_external_uq');
            $table->unique(['biometric_device_id', 'employee_id'], 'bio_map_device_employee_uq');
        });

        Schema::create('biometric_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained('biometric_devices')->cascadeOnDelete();
            $table->string('external_event_id');
            $table->string('external_employee_id');
            $table->enum('event_type', ['in', 'out']);
            $table->timestampTz('event_at');
            $table->string('payload_hash', 64);
            $table->foreignId('attendance_entry_id')->nullable()->constrained('attendance_entries')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['biometric_device_id', 'external_event_id'], 'bio_event_device_external_uq');
            $table->index(['external_employee_id', 'event_at'], 'bio_event_employee_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_events');
        Schema::dropIfExists('biometric_employee_mappings');
        Schema::dropIfExists('biometric_devices');
    }
};
