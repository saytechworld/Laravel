<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->bigInteger('delete_one')->nullable()->unsigned()->after('read_flag');
            $table->bigInteger('delete_two')->nullable()->unsigned()->after('delete_one');
            $table->integer('delete_everyone')->nullable()->after('delete_two');
            $table->foreign('delete_one')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('delete_two')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
}
