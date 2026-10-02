<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookups the guest form (Sprint 3) lists: region of residence options and
 * the SQD statements. SQD questions are versioned rows, so a new ARTA form
 * revision adds rows instead of changing the schema. Feedback will refer to
 * both with RESTRICT foreign keys; retire rows with is_active instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sqd_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('statement');
            $table->string('form_version', 20);
            // Whether the question counts toward the overall score (ADR 0005, playbook Q14).
            $table->boolean('included_in_overall');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'form_version']);
            $table->index(['form_version', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sqd_questions');
        Schema::dropIfExists('regions');
    }
};
