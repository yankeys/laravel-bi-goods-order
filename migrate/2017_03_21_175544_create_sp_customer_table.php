<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpCustomerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('bi.connection', 'mysql_bi'))
              ->create('sp_customer', function (Blueprint $table) {
                  $table->increments('id');

                  $table->date('date')->index();
                  $table->string('province', 32)->index();
                  $table->string('city', 32)->index();
                  $table->string('district', 32)->nullable()->index();

                  $table->string('type', 16)->index()->comment('客户类型');
                  $table->integer('user_id')->index()->comment('客户id');
                  $table->string('user_name', 10)->comment('客户名字');
                  $table->string('user_shop', 50)->comment('客户店铺名');

                  $table->integer('sp_id')->index()->comment('服务商id');
                  $table->string('sp_name', 20)->index()->comment('服务商姓名');
                  $table->string('sp_shop', 20)->index()->comment('服务商店铺名');
                  $table->string('wechat_account', 50)->index()->comment('公众号名');
              });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('bi.connection', 'mysql_bi'))
              ->drop('sp_customer');
    }
}
