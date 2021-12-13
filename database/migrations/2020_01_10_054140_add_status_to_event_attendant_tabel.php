<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToEventAttendantTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_attendants', function (Blueprint $table) {
            $table->tinyInteger('status')->after('team_id')->default(0)->comment("0=>Pending, 1=>Accept, 2=> Reject");
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
