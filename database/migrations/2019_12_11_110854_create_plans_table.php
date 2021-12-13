<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->longText('description');
            $table->double('price',15,2)->default(0);
            $table->boolean('status')->default(0);
            $table->integer('validity')->default(1);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_uuid');
            $table->string('transaction_id')->nullable();
            $table->biginteger('user_id')->unsigned();
            $table->integer('plan_id')->unsigned()->nullable();
            $table->biginteger('session_request_id')->unsigned()->nullable();
            $table->integer('order_type')->default(0)->comment("0:plan;1:session_request;");
            $table->double('price',15,2)->default(0);
            $table->timestamp('plan_end_date')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('session_request_id')->references('id')->on('session_requests')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('plans');
    }
}
