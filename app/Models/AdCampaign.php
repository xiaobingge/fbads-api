<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_campaigns';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'name',
        'effective_status',
        'status',
        'bid_strategy',
        'daily_budget',
        'pacing_type',
//        'switch_status',
//        'object_actions_desc',
//        'cost_per_action_type_desc',

        'campaign_name',
        'campaign_id',
        'objective',
        'account_id',
        'bid_strategy',
        'daily_budget',
        'budget_remaining',
        'buying_type',
        'lifetime_budget',
        'promoted_object',
        'spend_cap',
        'topline_id',


        'created_time',
        'end_time',
        'start_time',
        'updated_time',
    ];
}
