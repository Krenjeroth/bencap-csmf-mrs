<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data from the 2026 Citizen's Charter: offices, service types
 * (Internal / External) and the services each office offers.
 *
 * Records are deactivated rather than deleted once anything refers to them;
 * foreign keys are RESTRICT so feedback (Sprint 3) can never lose its office
 * or service. Lookup tables use auto-increment keys (ADR 0003).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            // URL-safe key for the guest form address (/f/{slug}); codes contain spaces.
            $table->string('slug', 60)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            // The charter is revised yearly; a new edition is imported as a new year.
            $table->unsignedSmallInteger('charter_year');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['office_id', 'name', 'charter_year']);
            $table->index(['office_id', 'is_active', 'sort_order']);
            $table->index('service_type_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // An Admin works with one office's data; System Administrators have none.
            $table->foreignId('office_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
        });
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_types');
        Schema::dropIfExists('offices');
    }
};
