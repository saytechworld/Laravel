<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->biginteger('coach_id')->unsigned();
            $table->biginteger('athelete_id')->unsigned();
            $table->biginteger('chat_id')->unsigned();
            $table->string('chat_session_uuid');
            $table->integer('status')->default(1)->comment("Pending:1;CoachAccept:2;CoachReject:3;AtheleteAccept:4;AtheleteReject:5;");
            $table->double('session_price', 15,2)->nullable();
            $table->timestamp('start_session_time')->nullable();
            $table->timestamp('end_session_time')->nullable();
            $table->timestamps();
            $table->foreign('coach_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('athelete_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_requests');
    }
}
