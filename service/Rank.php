<?php

namespace Zdp\BI\Services\ServiceProvider;

use Carbon\Carbon;
use Zdp\BI\Models\SpGoods;

class Rank
{
    protected $goodsSelect = [
        'date',
        'province',
        'city',
        'district',

        'order_id',
        'goods_id',
        'goods_type_node',
        'goods_brand',
        'goods_sort',
        'goods_name',
        'goods_price',
        'goods_num',

        'user_id',
        'type',
        'user_name',
        'user_shop',

        'shop_id',
        'shop_type',
        'shop_name',
        'shop_province',
        'shop_city',
        'shop_district',
        'shop_market',

        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',
    ];

    /**
     * 订单排行
     *
     * @param            $rule
     * @param            $contrast
     * @param array|null $time
     * @param            $page
     * @param            $size
     *
     * @return array
     */
    public function orderRank(
        $rule,
        $contrast,
        array $time = null,
        $page,
        $size
    ) {
        $this->query = SpGoods::query();
        $this->handleTime($time)
             ->handleRule($contrast, $rule)
             ->handleGroup($contrast);

        $data = $this->query->orderBy('data', 'desc')
                            ->paginate($size, ['*'], null, $page);

        return [
            'data'      => $data->items(),
            'total'     => $data->total(),
            'current'   => $data->currentPage(),
            'last_page' => $data->lastPage(),
        ];
    }

    /**
     * 商品排行
     *
     * @param            $rule
     * @param array|null $select
     * @param array|null $time
     * @param            $page
     * @param            $size
     *
     * @return array
     */
    public function goodsRank(
        $rule,
        array $select = null,
        array $time = null,
        $page,
        $size
    ) {
        $this->query = SpGoods::query();

        $this->handleTime($time)
             ->handleSelect($select)
             ->handleGoodsRule('goods_name', $rule)
             ->handleGroup('goods_id');

        $data = $this->query->orderBy('data', 'desc')
                            ->paginate($size, ['*'], null, $page);

        return [
            'data'      => $data->items(),
            'total'     => $data->total(),
            'current'   => $data->currentPage(),
            'last_page' => $data->lastPage(),
        ];
    }

    protected function handleTime(array $time = null)
    {
        if (empty($time)) {
            $time = [
                Carbon::now()->subDay(7),
                Carbon::now(),
            ];
        }
        sort($time);

        $this->query->where('date', '>', $time[0])
                    ->where('date', '<', $time[1]);

        return $this;
    }

    protected function handleSelect(array $selects = null)
    {
        if (!empty($selects)) {
            foreach ($selects as $select) {
                foreach ($select as $name => $value) {
                    if (in_array($name, $this->goodsSelect)) {
                        $value = (array)$value;
                        $this->query->whereIn($name, $value);
                    }
                }
            }
        }

        return $this;
    }

    protected function handleRule($group, $rule)
    {
        switch ($rule) {
            case 'amount':
                $this->query->select($group)
                            ->selectRaw('SUM(`goods_price`) AS `data`');
                break;
            case 'num':
                $this->query->select($group)
                            ->selectRaw('COUNT(DISTINCT `order_id`) AS `data`');
                break;
            case  'avg':
                $this->query->select($group)
                            ->selectRaw('SUM(`goods_price`)/COUNT(DISTINCT `order_id`) AS `data`');
                break;
        }

        return $this;
    }

    protected function handleGoodsRule($group, $rule)
    {
        switch ($rule) {
            case 'amount':
                $this->query->select($group)
                            ->selectRaw('SUM(`goods_price`) AS `data`');
                break;
            case 'num':
                $this->query->select($group)
                            ->selectRaw('SUM(`goods_num`) AS `data`');
                break;
        }

        return $this;
    }

    protected function handleGroup($group)
    {
        $this->query->groupBy($group);

        return $this;
    }
}