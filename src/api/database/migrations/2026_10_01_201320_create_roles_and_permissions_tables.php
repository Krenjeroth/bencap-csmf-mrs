<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role-based access control, exactly as specified (ADR 0004):
 * roles(title), permissions(title), role_user(role_id, user_id uuid),
 * permission_role(permission_id, role_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->unique();
            $table->string('description')->nullable();
            // Marks System Administrator: every permission, cannot be deleted or renamed.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            // resource.action, for example dashboard.view or users.create.
            $table->string('title', 100)->unique();
            $table->string('description')->nullable();
            // Catalog permissions checked in code; cannot be deleted or renamed.
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['role_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
