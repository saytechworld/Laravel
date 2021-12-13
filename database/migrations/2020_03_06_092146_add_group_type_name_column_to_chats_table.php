<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGroupTypeNameColumnToChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->bigInteger('team_id')->unsigned()->nullable()->after('message_type');
            $table->integer('chat_type')->default(1)->comment("1=>Normal, 2=>Group")->nullable()->after('team_id');
            $table->string('group_name')->nullable()->after('chat_type');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
