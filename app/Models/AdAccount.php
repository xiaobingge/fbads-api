<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAccount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_account';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['user_id', 'ad_account', 'ad_account_int'];


    public function auth()
    {
        return $this->hasOne(AdAuth::class, 'user_id', 'user_id');
    }
}
