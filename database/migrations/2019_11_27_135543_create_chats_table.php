<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('chat_uuid');
           	$table->biginteger('one_user_id')->unsigned();
            $table->biginteger('two_user_id')->unsigned();
            $table->longText('message')->nullable();
            $table->timestamps();
            
            $table->foreign('one_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('two_user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
           	$table->string('message_uuid');
           	$table->biginteger('chat_id')->unsigned();
           	$table->biginteger('user_id')->unsigned();
            $table->longText('message')->nullable();
            $table->timestamps();
            
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
    }
}
