<?php

namespace lumenous\Models;

use Illuminate\Database\Eloquent\Model;

class InflationEffect extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'inflation_effects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['effect_id', 'amount', 'data'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

}
