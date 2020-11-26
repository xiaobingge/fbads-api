<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAuth extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_auth';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['type', 'app_id', 'user_id', 'name', 'avatar', 'email', 'scope', 'access_token', 'expire_in'];
}
