<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAd extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_ads';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'ad_id',
        'account_id',
        'adset_id',
        'campaign_id',
        'effective_status',
        'status',
        'name',
        'creative_id',
        'creative',
        'tracking_specs',
//        'switch_status',
//        'creative'
    ];


    public function accountAndAuth()
    {
        return $this->hasOne(AdAccount::class, 'ad_account_int', 'account_id')->with('auth');
    }

}
