<?php

namespace Zdp\BI\Services\ServiceProvider;

use Zdp\BI\Models\SpCustomer;
use Carbon\Carbon;
use Zdp\ServiceProvider\Data\Models\ServiceProvider;

/**
 * Class StatsService
 *
 * @property array  $time   日期限制
 * @property string $group  分组方式
 * @property array  $split  分裂项
 * @property array  select  需要sum的字段
 * @property array  $filter 筛选项
 */
class StatsCustomer
{
    protected $statisticQuery;
    protected $statisticOption = [];
    protected $customerFilter  = [
        'province',
        'city',
        'district',
        'type',
        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',
    ];
    protected $customSplit     = [
        'province',
        'city',
        'district',
        'type',
        'sp_id',
        'sp_name',
        'sp_shop',
        'wechat_account',
    ];
    protected $customerGroup   = [
        'day',
        'week',
        'month',
        'year',
        'none',
    ];

    /**
     * 服务商管理(统计列表/搜索)
     *
     * @param      $page
     * @param      $size
     * @param null $searchType
     * @param null $content
     *
     * @return array
     */
    public function index(
        $page,
        $size,
        $searchType = null,
        $content = null
    ) {
        $query = ServiceProvider::query();

        if (!empty($searchType)) {
            switch ($searchType) {
                case 1:
                    $query->where('shop_name', 'like', '%' . $content . '%');
                    break;
                case 2:
                    $query->where('mobile', $content);
                    break;
            }
        }

        $allProviders = $query->where('status', ServiceProvider::PASS)
                              ->paginate($size, ['*'], null, $page);

        $applies = array_map(function ($a) {
            return self::formatForAdmin($a);
        }, $allProviders->items());

        return [
            'list'      => $applies,
            'total'     => $allProviders->total(),
            'current'   => $allProviders->currentPage(),
            'last_page' => $allProviders->lastPage(),
        ];
    }

    protected function formatForAdmin(ServiceProvider $provider)
    {
        return [
            'uid'          => $provider->zdp_user_id,
            'shop_name'    => $provider->shop_name,
            'user_name'    => $provider->user_name,
            'mobile'       => $provider->mobile,
            'province'     => $provider->province,
            'city'         => $provider->city,
            'district'     => $provider->county,
            'market_ids'   => $provider->market_ids,
            'customer_num' => $provider->customerNum,
        ];
    }

    /**
     * 客户统计页面
     *
     * @param null       $group
     * @param array|null $time
     * @param array|null $filter
     * @param array|null $split
     *
     * @return array
     */
    public function customerStats(
        $group = null,
        array $time = null,
        array $filter = null,
        array $split = null
    ) {
        $this->parseGroup($group)
             ->parseStatisticTime($time)
             ->parseStatisticFilter($filter)
             ->parseSplit($split);

        $this->statisticQuery = SpCustomer::query();

        $this->handleTime()
             ->handleFilter();

        // 当前筛选项内的客户数量
        $sortNum = [];
        foreach ($this->split as $split) {
            foreach ($split as $name => $value) {
                $query = clone $this->statisticQuery;
                $index = implode(",", $value);
                $sortNum[$index] = $query->whereIn($name, $value)->count('id');
            }
        }

        $query = clone $this->statisticQuery;
        $total = $query->count('id');

        $this->handleGroup();
        $this->statisticQuery->selectRaw('COUNT(`id`) AS `number`');

        if (empty($this->split)) {
            $detail = $this->statisticQuery->get()->toArray();
        } else {
            $detail = [];
            foreach ($this->split as $split) {
                $query = clone $this->statisticQuery;
                foreach ($split as $name => $value) {
                    $index = implode(",", $value);
                    $detail[$index] =
                        $query->whereIn($name, $value)->get()->toArray();
                }
            }
        }

        return [
            'total'    => $total,
            'sort_num' => $sortNum,
            'detail'   => $detail,
        ];
    }

    protected function parseGroup($group = null)
    {
        if (in_array($group, $this->customerGroup) !== false) {
            $this->group = $group;
        } else {
            $this->group = 'day';
        }

        return $this;
    }

    protected function parseStatisticTime(array $time = null)
    {
        self::parseFilterTime($time);
        $this->ensureTimeRange();

        return $this;
    }

    protected function parseStatisticFilter(array $filter = null)
    {
        $tmp = [];

        if (!empty($filter)) {
            $filter = array_filter($filter, function ($key) use ($filter) {
                return $filter[$key] != null;
            }, ARRAY_FILTER_USE_KEY);
            foreach ($filter as $name => $val) {
                if (in_array($name, $this->customerFilter)) {
                    $tmp[] = [$name => $val];
                }
            }
        }

        $this->filter = $tmp;

        return $this;
    }

    protected function parseSplit(array $split = null)
    {
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
                if (in_array($name, $this->customSplit)) {
                    $tmp[] = [$name => $value];
                }
            }
        }
        $this->split = $tmp;

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

    protected function handleFilterItem($name, $value)
    {
        if (is_numeric($value) || empty($value)) {
            return;
        }

        if (!in_array($name, $this->customerFilter)) {
            return;
        }

        $value = (array)$value;
        $this->statisticQuery
            ->whereIn($name, $value);
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
            $this->statisticQuery
                ->selectRaw(
                    'DATE_FORMAT(`date`, ?) as `time`',
                    [$format]
                )
                ->groupBy('time');
        }

        return $this;
    }

    /**
     * 根据分组方式定义默认时间分组
     */
    protected function ensureTimeRange()
    {
        if (empty($this->time)) {
            return;
        }

        /**
         * @var Carbon $start
         * @var Carbon $end
         */
        $start = $this->time[0];
        $end = $this->time[1];

        if (empty($start) || empty($end)) {
            return;
        }

        switch ($this->group) {
            case 'week':
                $this->time = [$start->startOfWeek(), $end->endOfWeek()];
                break;

            case 'month':
                $this->time = [$start->startOfMonth(), $end->endOfMonth()];
                break;

            case 'year':
                $this->time = [$start->startOfYear(), $end->endOfYear()];
                break;

            case 'day':
            default:
                $this->time = [$start->startOfDay(), $end->endOfDay()];
                break;
        }
    }

    protected function parseFilterTime(array $time = null)
    {
        if (empty($time)) {
            $this->time = [
                Carbon::now()->subDays(7),
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

    protected function handleTime()
    {
        list($start, $end) = $this->time;

        $this->statisticQuery->where('date', '>=', $start)
                             ->where('date', '<=', $end);

        return $this;
    }
}