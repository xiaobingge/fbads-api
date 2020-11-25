<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdOverview extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_overview';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'date',
        'account_id',
        'spend','impression','click','ctr','cpm','cpc','install','landing_page_view','add_cart','purchase','purchase_value','cpa','roas'
    ];
}
