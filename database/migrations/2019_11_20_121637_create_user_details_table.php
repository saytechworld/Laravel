<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->biginteger('user_id')->unsigned();
            $table->longText('image')->nullable();
            $table->string('mobile')->nullable();
            $table->enum('gender',['M','F'])->nullable();
            $table->date('dob')->nullable();
            $table->longText('address_line_1')->nullable();
            $table->longText('address_line_2')->nullable();
            $table->biginteger('country_id')->unsigned()->nullable();
            $table->biginteger('state_id')->unsigned()->nullable();
            $table->biginteger('city_id')->unsigned()->nullable();
            $table->biginteger('zipcode_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('zipcode_id')->references('id')->on('zipcodes')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_details');
    }
}
