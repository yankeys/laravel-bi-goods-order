<?php

namespace Zdp\BI\Services\Sync;

use App\Models\DpGoodsInfo;
use App\Models\DpGoodsType;
use App\Models\DpShopInfo;
use Carbon\Carbon;
use Zdp\BI\Models\SpGoods;
use Zdp\ServiceProvider\Data\Models\OrderGoods;

class GoodsProvider
{
    public function syncGoods()
    {
        OrderGoods
            ::join('orders as o', 'order_goods.order_id', '=', 'o.id')
            ->join('service_providers as sp', 'o.sp_id', '=', 'sp.zdp_user_id')
            ->join('wechat_accounts as wa', 'o.sp_id', '=', 'wa.sp_id')
            ->join('users as u', 'o.user_id', '=', 'u.id')
            ->join('area as ap', 'sp.province_id', '=', 'ap.id')
            ->join('area as ac', 'sp.city_id', '=', 'ac.id')
            ->leftJoin('area as ad', 'sp.county_id', '=', 'ad.id')
            ->join('shop_type as st', 'u.shop_type_id', '=', 'st.id')
            ->where('order_goods.updated_at', '>=', self::getDatetime())
            ->where('order_goods.status', '>=', OrderGoods::PURCHASE)
            ->select([
                'order_goods.order_id',
                'order_goods.goods_id',
                'order_goods.goods_info',
                'order_goods.created_at',

                'sp.zdp_user_id as sp_id',
                'sp.shop_name as sp_shop',
                'sp.user_name as sp_name',
                'ap.name as province',
                'ac.name as city',
                'ad.name as district',

                'u.id as user_id',
                'u.user_name as user_name',
                'u.shop_name as user_shop',
                'st.type_name as type',

                'wa.wechat_name as wechat_account',
            ])
            ->chunk(200, function ($goods) {
                foreach ($goods as $good) {
                    $user = array_except(
                        $good->toArray(),
                        ['shop_type', 'created_at', 'goods_info']);
                    // 处理商品信息
                    $goodsInfo = json_decode($good->goods_info);
                    $goodInfo = self::formatInfo($goodsInfo);
                    $data = array_merge($user, $goodInfo);
                    $shopInfo = self::getShop($good->goods_id);
                    $data['date'] = $good->created_at->toDateString();
                    $data['shop_type'] = self::shopType($shopInfo->shop_type);
                    $data = array_merge(
                        $data,
                        array_except($shopInfo->toArray(), 'shop_type'));
                    $update = array_except($data, 'goods_price');
                    $price = $data['goods_price'];
                    // 写入数据库
                    SpGoods::updateOrCreate($update, ['goods_price' => $price]);
                }
            });
    }

    // 将店铺类型代号转成对应的中文名称存放
    public function shopType($typeId)
    {
        $shopTypeName = [
            DpShopInfo::YIPI         => '一批',
            DpShopInfo::VENDOR       => '厂家',
            DpShopInfo::ERPI         => '二批',
            DpShopInfo::MIDDLEMEN    => '第三方',
            DpShopInfo::DISTRIBUTORS => '配送公司',
            DpShopInfo::TERMINAL     => '终端',
            DpShopInfo::RESTAURANT   => '餐厅',
            DpShopInfo::SUPERMARKET  => '商超零售',
            DpShopInfo::DRIVER       => '司机',
            DpShopInfo::DIRECT_SELL  => '直营',
        ][$typeId];
        if (!$shopTypeName) {
            $shopTypeName = '';
        }

        return $shopTypeName;
    }

    // 处理商品信息
    protected function formatInfo($goodsInfo)
    {
        return [
            'goods_name'  => $goodsInfo->gname,
            'goods_brand' => $goodsInfo->brand,
            'goods_num'   => $goodsInfo->buy_num,
            'goods_price' => $goodsInfo->goods_price,
            'goods_sort'  => DpGoodsType::where('id', $goodsInfo->sortid)
                                        ->value('sort_name'),
        ];
    }

    protected function getShop($goodsId)
    {
        $shop = DpGoodsInfo
            ::join('dp_shopInfo as dsi', 'dp_goods_info.shopid', '=',
                'dsi.shopId')
            ->join('dp_pianqu as dp', 'dsi.pianquId', '=', 'dp.pianquId')
            ->where('dp_goods_info.id', $goodsId)
            ->select([
                'dp_goods_info.gname as goods_name',
                'dp_goods_info.sortid as goods_type_node',

                'dsi.shopId as shop_id',
                'dsi.trenchnum as shop_type',
                'dsi.dianPuName as shop_name',
                'dsi.province as shop_province',
                'dsi.city as shop_city',
                'dsi.county as shop_district',

                'dp.pianqu as shop_market',
            ])
            ->first();

        return $shop;
    }

    /**
     * 获取同步命令的开始时间
     */
    protected function getDatetime()
    {
        $log = SpGoods::orderBy('id', 'desc')
                      ->first();

        if ($log) {
            $time = OrderGoods::where('goods_id', $log->goods_id)
                              ->where('order_id', $log->order_id)
                              ->value('created_at');
        } else {
            $time = Carbon::create(2017, 1, 1)->startOfDay();
        }

        return $time;
    }
}