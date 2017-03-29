<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('bi.connection', 'mysql_bi'))
              ->create('sp_goods', function (Blueprint $table) {
                  $table->increments('id');

                  $table->date('date')->index();

                  $table->integer('order_id')->index()->comment('订单id');
                  $table->integer('goods_id')->unsigned()->common('商品id');
                  $table->string('goods_type_node', 32)->common('商品类型串');
                  $table->string('goods_brand', 32)->index()->common('商品品牌');
                  $table->string('goods_sort', 32)->index()->common('商品分类');
                  $table->string('goods_name', 32)->index()->common('商品名字');
                  $table->decimal('goods_price', 11, 2)->unsigned()->index()->common('销售金额');
                  $table->integer('goods_num')->default(1)->index()->common('销售数量');

                  $table->integer('user_id')->index()->comment('客户id');
                  $table->string('type', 16)->index()->comment('客户类型');
                  $table->string('user_name', 10)->comment('客户名字');
                  $table->string('user_shop', 50)->comment('客户店铺名');

                  $table->integer('shop_id')->unsigned()->index()->common('卖家id');
                  $table->string('shop_type', 16)->index()->common('卖家店铺类型');
                  $table->string('shop_name', 32)->index()->common('卖家店铺类型');
                  $table->string('shop_province', 32)->index()->common('卖家省份');
                  $table->string('shop_city', 32)->index()->common('卖家城市');
                  $table->string('shop_district', 32)->nullable()->index()->common('卖家区县');
                  $table->string('shop_market', 32)->index()->common('卖家市场');

                  $table->integer('sp_id')->index()->comment('服务商id');
                  $table->string('sp_name', 20)->index()->comment('服务商名字');
                  $table->string('sp_shop', 20)->index()->comment('服务商店铺名');
                  $table->string('wechat_account', 50)->index()->comment('公众号名');
                  $table->string('province', 32)->index();
                  $table->string('city', 32)->index();
                  $table->string('district', 32)->nullable()->index();
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
              ->drop('sp_goods');
    }
}
