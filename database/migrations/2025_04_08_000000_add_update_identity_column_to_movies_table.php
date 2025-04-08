<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdateIdentityColumnToMoviesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This migration is only needed if update_identity column doesn't already exist
        if (!Schema::hasColumn('movies', 'update_identity')) {
            Schema::table('movies', function (Blueprint $table) {
                $table->string('update_identity', 2048)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We don't remove the column as it might be part of the original schema
    }
}