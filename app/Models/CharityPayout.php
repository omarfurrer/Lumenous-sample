<?php

namespace lumenous\Models;

use Illuminate\Database\Eloquent\Model;

class CharityPayout extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'charity_payouts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['amount', 'transaction_fee', 'transaction_hash', 'charity_public_key', 'inflation_effect_id'];

}
