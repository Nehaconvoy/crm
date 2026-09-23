<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the compliance exception note captured when a carrier's MC status
     * is set to "Approved as Exception". Prefixed with the reviewing user's name
     * (e.g. "Jane Doe : reason"), and cleared to null for any other MC status.
     */
    public function up(): void
    {
        Schema::table('externals', function (Blueprint $table) {
            if (! Schema::hasColumn('externals', 'compliance_exception_note')) {
                $table->text('compliance_exception_note')->nullable()->after('mc_check');
            }
        });
    }

    public function down(): void
    {
        Schema::table('externals', function (Blueprint $table) {
            if (Schema::hasColumn('externals', 'compliance_exception_note')) {
                $table->dropColumn('compliance_exception_note');
            }
        });
    }
};
