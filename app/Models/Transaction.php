<?php

namespace lumenous\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tx_xdr', 'tx_hash', 'src_account', 'signed', 'signed_at', 'submitted', 'submitted_at',
        'is_account_payout', 'payout_id', 'is_charity_payout', 'charity_payout_id'];

    /**
     * @return mixed
     */
    public function signers()
    {
        return $this->hasMany('lumenous\Models\Signer', 'transaction_id');
    }

}
