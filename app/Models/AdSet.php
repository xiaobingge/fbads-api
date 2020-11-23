<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdSet extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_adsets';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'adset_id',
        'account_id',
        'effective_status',
        'status',
        'lifetime_budget',
        'daily_budget',
        'bid_amount',
        'billing_event',
        'name',
        'campaign_id',
        'optimization_goal',
        'targeting',
        'promoted_object',
        'attribution_spec',
        'destination_type',

        'created_time',
        'end_time',
        'start_time',
        'updated_time',
    ];


    public function accountAndAuth()
    {
        return $this->hasOne(AdAccount::class, 'ad_account_int', 'account_id')->with('auth');
    }
}
