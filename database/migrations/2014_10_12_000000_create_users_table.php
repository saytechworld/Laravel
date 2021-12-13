<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('user_uuid');
            $table->string('confirmation_code')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('status')->default(0);
            $table->boolean('confirmed')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });



        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name')->unique();
            $table->longText('description')->nullable();
            $table->boolean('all')->default(0);
            $table->integer('sort')->default(1);
            $table->timestamps();
        });


        Schema::create('user_role', function (Blueprint $table) {
            $table->biginteger('user_id')->unsigned();
            $table->biginteger('role_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
}
