<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('file_type');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->tinyInteger('file_type')->default(1)->comment("1=> Video, 2=> Photo")->after('file_name');
            $table->tinyInteger('privacy')->default(1)->comment("1=> Public, 0=> Private")->after('file_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            //
        });
    }
}
