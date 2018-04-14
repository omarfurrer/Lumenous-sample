<?php

namespace lumenous\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBatch extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'transaction_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['file_name', 'file_path', 'timestamp', 'signer_count', 'submitted'];

}
