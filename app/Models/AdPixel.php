<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPixel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_pixels';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'pixel_id',
        'name',
        'code',
        'status',
    ];
}
