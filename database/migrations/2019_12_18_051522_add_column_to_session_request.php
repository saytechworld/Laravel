<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToSessionRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('session_requests', function (Blueprint $table) {
            $table->tinyInteger('request_by')->default(1)->after('end_session_time')->comment("1=>Athelete, 2=>coach");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('session_requests', function (Blueprint $table) {
            //
        });
    }
}
