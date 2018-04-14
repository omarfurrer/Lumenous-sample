<?php

namespace lumenous\Repositories;

use lumenous\Models\Signer;
use lumenous\Models\Transaction;
use lumenous\Repositories\Interfaces\SignersRepositoryInterface;
use lumenous\User;
Use Exception;

class EloquentSignersRepository extends EloquentAbstractRepository implements SignersRepositoryInterface {

    public function __construct()
    {
        $this->modelClass = 'lumenous\Models\Signer';
    }

//    /**
//     * Return a collection of signers for a given transaction.
//     *
//     * @param   string  $tag
//     * @return  mixed
//     */
//    public function getByTransaction(Transaction $transaction)
//    {
//        return $transaction->signers()->get();
//    }
//
    /**
     * Add a signer to the transaction.
     *
     * @param   Transaction $transaction
     * @param   User $user
     * @return  Signer
     * @throws Exception
     */
    public function addSigner(Transaction $transaction, User $user)
    {

        if (!$user->hasPermissionTo("sign transactions")) {
            throw new Exception("User does not have permission to sign transaction");
        }

        // TODO: also compare against our whitelist of signers for security

        return Signer::create([
                    'transaction_hash' => $transaction->tx_hash,
                    'user_id' => $user->id
        ]);
    }

    /**
     * Check if a user has signed a specific transaction.
     * 
     * @param Transaction $transaction
     * @param User $user
     * @return boolean
     */
    public function isSignedByUser(Transaction $transaction, User $user)
    {
        return Signer::where('transaction_hash', $transaction->tx_hash)
                        ->where('user_id', $user->id)
                        ->exists();
    }

}
