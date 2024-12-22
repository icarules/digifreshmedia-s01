<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class Apikey extends Model
{
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
    protected $fillable = ['client_name', 'tables', 'api_key', 'lease_down_payment', 'lease_interest_rate_general', 'lease_interest_rate', 'status'];
}
