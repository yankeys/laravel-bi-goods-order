<?php

namespace Zdp\BI\Models;

class SpCustomer extends Model
{
    protected $table = 'customer';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'province',
        'city',
        'district',

        'type',
        'user_id',
        'user_name',
        'user_shop',

        '_id',
        '_name',
        '_shop',
        '_account',
    ];
}
