<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::backfillMissingStudentCodes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Student codes cannot be removed safely because they may already be in use.
    }
};
