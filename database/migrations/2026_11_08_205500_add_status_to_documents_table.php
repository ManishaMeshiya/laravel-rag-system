<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status')
                ->default('pending');
        });

        DB::table('documents')
            ->whereIn('id', DB::table('document_chunks')->select('document_id'))
            ->update(['status' => 'ready']);

        DB::table('documents')
            ->whereNotIn('id', DB::table('document_chunks')->select('document_id'))
            ->update(['status' => 'failed']);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
