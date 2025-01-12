<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class Apikey extends Model
{
    /**
     * The connection associated with the model.
     *
     * @var string
     */
//    protected $connection = 's08';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_name',
        'tables',
        'api_key',
        'lease_down_payment',
        'lease_final_payment',
        'lease_default_term',
        'lease_max_age',
        'lease_min_term',
        'lease_max_term',
        'lease_interest_rate_general',
        'lease_interest_rate',
        'use_lease_price_filter',
        'status'
    ];
}
