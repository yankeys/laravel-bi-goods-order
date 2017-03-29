<?php

namespace Zdp\BI\Services\ServiceProvider;

use Carbon\Carbon;
use Zdp\BI\Models\SpGoods;
use Zdp\ServiceProvider\Data\Models\ShopType;

class StatsOrder
{
    protected $orderGroup  = [
        'day',
        'week',
        'month',
        'year',
        'none',
    ];
    protected $orderFilter = [
        'province',
        'city',
        'district',
        'type',
        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',

        'goods_brand',
        'goods_sort',
        'user_name',
        'user_shop',

        'shop_type',
        'shop_name',
        'shop_province',
        'shop_city',
        'shop_district',
        'shop_market',
    ];
    protected $orderSplit  = [
        'province',
        'city',
        'district',
        'type',
        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',

        'goods_brand',
        'goods_sort',
        'user_name',
        'user_shop',

        'shop_type',
        'shop_name',
        'shop_province',
        'shop_city',
        'shop_district',
        'shop_market',
    ];

    protected $orderShow = [
        'province',
        'city',
        'district',
        'type',
        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',

        'goods_brand',
        'goods_sort',
        'user_name',
        'user_shop',

        'shop_type',
        'shop_name',
        'shop_province',
        'shop_city',
        'shop_district',
        'shop_market',
    ];

    protected $orderSelect = [
        'amount',
        'num',
        'avg',
    ];

    /**
     * @param null       $group
     * @param array|null $time
     * @param array|null $filter
     * @param array|null $split
     *
     * @return array
     */
    public function orderStats(
        $group = null,
        array $time = null,
        array $filter = null,
        array $split = null,
        array $show = null,
        array $select = null
    ) {
        $this->parseGroup($group)
             ->parseTime($time)
             ->parseFilter($filter)
             ->parseSplit($split)
             ->parseShow($show)
             ->parseSelect($select);

        $this->query = SpGoods::query();

        $this->handleTime()
             ->handleFilter()
             ->handleShow();

        $queryAll = clone $this->query;
        $totalSum = $queryAll->sum('goods_price');
        $totalNum = $queryAll->distinct()->count('order_id');

        $this->handleGroup()->handleSelect();

        if (empty($this->split)) {
            $detail = $this->query->get()->toArray();
        } else {
            $detail = [];
            foreach ($this->split as $split) {
                $query = clone $this->query;
                foreach ($split as $name => $value) {
                    $index = implode(",", $value);
                    $detail[$index] =
                        $query->whereIn($name, $value)->get()->toArray();
                }
            }
        }

        return [
            'all_num'    => $totalNum,
            'all_amount' => $totalSum,
            'sort_data'  => $this->sortData,
            'detail'     => $detail,
        ];

    }

    protected function parseGroup($group = null)
    {
        if (in_array($group, $this->orderGroup)) {
            $this->group = $group;
        } else {
            $this->group = 'day';
        }

        return $this;
    }

    protected function parseTime(array $time = null)
    {
        if (empty($time)) {
            $this->time = [
                Carbon::now()->subDay(7),
                Carbon::now(),
            ];
        } elseif (count($time) == 1) {
            $this->time = [
                new Carbon($time[0]),
                new Carbon($time[0]),
            ];
        } else {
            $tmp = [
                new Carbon($time[0]),
                new Carbon($time[1]),
            ];
            sort($tmp);
            $this->time = $tmp;
        }

        return $this;
    }

    protected function parseFilter(array $filter = null)
    {
        $tmp = [];

        if (!empty($filter)) {
            $filter = array_filter($filter, function ($key) use ($filter) {
                return $filter[$key] != null;
            }, ARRAY_FILTER_USE_KEY);
            foreach ($filter as $name => $val) {
                if (in_array($name, $this->orderFilter)) {
                    $tmp[] = [$name => $val];
                }
            }
        }
        $this->filter = $tmp;

        return $this;
    }

    protected function parseSplit(array $split = null)
    {
        // 定义split为数组，即使为空也是数组格式
        $split = (array)$split;

        $tmp = [];

        $splits = array_filter($split, function ($key) use ($split) {
            return $split[$key] != null;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($splits as $split) {
            $splits = array_filter($split, function ($key) use ($split) {
                return $split[$key] != null;
            }, ARRAY_FILTER_USE_KEY);
            foreach ($splits as $name => $value) {
                if (in_array($name, $this->orderSplit)) {
                    $tmp[] = [$name => $value];
                }
            }
        }
        $this->split = $tmp;

        return $this;
    }

    protected function parseShow(array $show = null)
    {
        // 定义split为数组，即使为空也是数组格式
        $show = (array)$show;

        $tmp = [];

        $shows = array_filter($show, function ($key) use ($show) {
            return $show[$key] != null;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($shows as $show) {
            $shows = array_filter($show, function ($key) use ($show) {
                return $show[$key] != null;
            }, ARRAY_FILTER_USE_KEY);
            foreach ($shows as $name => $value) {
                if (in_array($name, $this->orderShow)) {
                    $tmp[] = [$name => $value];
                }
            }
        }
        $this->show = $tmp;

        return $this;
    }

    protected function parseSelect(array $select = null)
    {
        if (empty($select)) {
            $this->select = $this->orderSelect;
        } else {
            $tmp = [];
            foreach ($this->orderSelect as $value) {
                if (in_array($value, $select)) {
                    $tmp[] = $value;
                }
            }
            $this->select = $tmp;
        }

        return $this;
    }

    protected function handleGroup()
    {
        switch ($this->group) {
            case 'day':
                $format = '%Y-%m-%d';
                break;

            case 'week':
                $format = '%x-%v';
                break;

            case 'month':
                $format = '%Y-%m';
                break;

            case 'year':
                $format = '%Y';
                break;

            case 'none':
                break;
        }

        if (!empty($format)) {
            $this->query->selectRaw('DATE_FORMAT(`date`,?) as time', [$format])
                        ->groupBy('time');
        }

        return $this;
    }

    protected function handleSelect()
    {
        if (in_array('avg', $this->select) ||
            in_array('amount', $this->select)
        ) {
            $this->query->selectRaw('SUM(`goods_price`) AS `amount`');
        }
        if (in_array('avg', $this->select) ||
            in_array('num', $this->select)
        ) {
            $this->query->selectRaw('COUNT(DISTINCT `order_id`) AS `num`');
        }

        return $this;
    }

    protected function handleTime()
    {
        $this->query->where('date', '>', $this->time[0])
                    ->where('date', '<', $this->time[1]);

        return $this;
    }

    protected function handleFilter()
    {
        foreach ($this->filter as $filter) {
            foreach ($filter as $name => $value) {
                $this->handleFilterItem($name, $value);
            }
        }

        return $this;
    }

    protected function handleShow()
    {
        $data = [];
        $shows = $this->show;
        if (empty($shows)) {
            $shows = ShopType::select('type_name as type')->get()->toArray();
        }
        foreach ($shows as $val) {
            foreach ($val as $name => $value) {
                $value = (array)$value;
                $query = clone $this->query;
                $index = implode(",", $value);
                $data[$index . '金额'] = $query->whereIn($name, $value)
                                             ->sum('goods_price');
                $data[$index . '单数'] = $query->whereIn($name, $value)
                                             ->distinct()
                                             ->count('order_id');
            }
        }
        $this->sortData = $data;

        return $this;
    }

    protected function handleFilterItem($name, $value)
    {
        if (empty($value)) {
            return;
        }
        $this->query->whereIn($name, $value);
    }
}