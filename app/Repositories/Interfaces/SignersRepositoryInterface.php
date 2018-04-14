<?php

namespace lumenous\Repositories\Interfaces;

use lumenous\Models\Transaction;
use lumenous\User;
    
interface SignersRepositoryInterface {

    /**
     * Add a signer to the transaction.
     *
     * @param   Transaction $transaction
     * @param   User $user
     * @return  Signer
     * @throws Exception
     */
    public function addSigner(Transaction $transaction, User $user);

    /**
     * Check if a user has signed a specific transaction.
     * 
     * @param Transaction $transaction
     * @param User $user
     * @return boolean
     */
    public function isSignedByUser(Transaction $transaction, User $user);
}
