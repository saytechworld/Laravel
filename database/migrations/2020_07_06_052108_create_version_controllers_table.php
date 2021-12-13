<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersionControllersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('android_version_controllers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('version')->nullable();
            $table->integer('status')->default(1)->comment('0=>Optional, 1=>Mandatory');
            $table->timestamps();
        });

        Schema::create('ios_version_controllers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('version')->nullable();
            $table->integer('status')->default(1)->comment('0=>Optional, 1=>Mandatory');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('version_controllers');
    }
}
