<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_pages';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'page_id',
        'name',
        'global_brand_page_name',
        'link',
        'tasks',
        'access_token',
        'picture',
        'is_published',
        'status',
    ];

}
