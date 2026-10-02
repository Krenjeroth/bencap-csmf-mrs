<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Office hierarchy (playbook Q5): the OG-* offices sit under the Office of
 * the Governor. One level only: a parent is always a top-level office
 * (enforced in OfficeRequest), so the tree can never loop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('offices')->restrictOnDelete();
        });

        // Databases seeded before this migration: place the OG-* offices under
        // OG. Fresh databases have no offices yet; OfficeSeeder sets parents.
        $og = DB::table('offices')->where('code', 'OG')->value('id');
        if ($og !== null) {
            DB::table('offices')
                ->where('code', 'like', 'OG-%')
                ->whereNull('parent_id')
                ->update(['parent_id' => $og]);
        }
    }

    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
