<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToEventAttenders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_attendants', function (Blueprint $table) {
            $table->tinyInteger('event_type')->after('attendant_type')->comment("0=>Individual, 1=>Team")->default(0);
            $table->integer('team_id')->after('event_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_attendants', function (Blueprint $table) {
            //
        });
    }
}
