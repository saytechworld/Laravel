<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->biginteger('country_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null')->onUpdate('cascade');
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->biginteger('country_id')->unsigned()->nullable();
            $table->biginteger('state_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null')->onUpdate('cascade');

        });

        Schema::create('zipcodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->biginteger('country_id')->unsigned()->nullable();
            $table->biginteger('state_id')->unsigned()->nullable();
            $table->biginteger('city_id')->unsigned()->nullable();
            $table->string('zip_code');
            $table->boolean('status')->default(0);
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zipcodes');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
}
