<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceGoods extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'facebook_goods';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
