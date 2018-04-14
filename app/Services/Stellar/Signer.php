<?php

namespace lumenous\Services\Stellar;

use \ZuluCrypto\StellarSdk\Server;
use ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use ZuluCrypto\StellarSdk\Keypair;
use lumenous\User;
use lumenous\Models\Transaction;
use lumenous\Repositories\Interfaces\SignersRepositoryInterface;
use lumenous\Services\Stellar\client;

class Signer {

    /**
     * @var SignersRepositoryInterface
     */
    protected $signersRepository;

    /**
     * @var client 
     */
    protected $stellarClient;

    /**
     * Signer constructor.
     * 
     * @param SignersRepositoryInterface $signersRepository
     * @param Client $stellarClient
     */
    public function __construct(SignersRepositoryInterface $signersRepository, client $stellarClient)
    {
        $this->signersRepository = $signersRepository;
        $this->stellarClient = $stellarClient;
    }

    /**
     * 
     * @param Transaction $transaction
     * @param User $user
     * @param String $secretKey
     * @return Transaction
     */
    public function sign(Transaction $transaction, User $user, $secretKey)
    {
        $transactionEnvelope = $this->getTransactionEnvelope($transaction);

        $transactionEnvelope = $this->signEnvelope($transactionEnvelope, $secretKey);

        //check if transaction signing was successful

        $this->signersRepository->addSigner($transaction, $user);

        $transaction->tx_xdr = $transactionEnvelope->toBase64();

        return $transaction;
    }

    /**
     * Sign a transaction envelope.
     * 
     * @param TransactionEnvelope $transactionEnvelope
     * @param String $secretKey
     * @return TransactionEnvelope
     */
    public function signEnvelope(TransactionEnvelope $transactionEnvelope, $secretKey)
    {
        return $transactionEnvelope->sign(Keypair::newFromSeed($secretKey), $this->getServer());
    }

    /**
     * Using a transaction model get transaction envelope.
     * 
     * @param Transaction $transaction
     * @return TransactionEnvelope
     */
    public function getTransactionEnvelope(Transaction $transaction)
    {
        return TransactionEnvelope::fromXdr(new XdrBuffer(base64_decode($transaction->tx_xdr)));
    }

    /**
     * Get the server.
     * 
     * @return Server
     */
    public function getServer()
    {
        return $this->stellarClient->getServer();
    }

    /**
     * Add signers to a transaction.
     *
     * @param   \lumenous\Models\Transaction $transaction
     * @param   array   $signers
     * @return  mixed
     */
//    public function addSigners(\lumenous\Models\Transaction $transaction, $signers = [])
//    {
//        $models = [];
//
//        foreach ($signers as $signer) {
//            // handle performing the actual signing
//            $signer = $this->addSigner($transaction, $signer);
//
//            // mark signer as added in the database
//            $models[] = $this->signersRepository->addSigner($transaction, $signer);
//        }
//
//        return $models;
//    }
}
