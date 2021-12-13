<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyTeamIdEventAttendantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_attendants', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('event_attendants', function (Blueprint $table) {
            $table->bigInteger('team_id')->after('event_type')->nullable()->unsigned();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade')->onUpdate('cascade');
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
