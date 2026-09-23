<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the CPR compliance exception note captured when a load's CPR status
     * is set to "Approved as Exception". Cleared to null for any other CPR status.
     */
    public function up(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            if (! Schema::hasColumn('loads', 'cpr_exception_note')) {
                $table->text('cpr_exception_note')->nullable()->after('cpr_check');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loads', function (Blueprint $table) {
            if (Schema::hasColumn('loads', 'cpr_exception_note')) {
                $table->dropColumn('cpr_exception_note');
            }
        });
    }
};
