<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceGoodsRs extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'facebook_goods_rs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'resource_product_id',
        'product_id',
        'shop_type',
        'type',
        'shop_index',
        'add_time',
    ];
}
