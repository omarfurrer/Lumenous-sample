<?php

namespace lumenous\Services\Stellar;

use lumenous\Models\Payout;
use Exception;
use lumenous\Services\Stellar\TransactionBuilder;

class PayoutTransactionBuilder {

    /**
     * @var TransactionBuilder 
     */
    protected $transactionBuilder;

    /**
     * Default Constructor
     * 
     * @param TransactionBuilder $transactionBuilder
     */
    public function __construct(TransactionBuilder $transactionBuilder)
    {
        $this->transactionBuilder = $transactionBuilder;
    }

    /**
     * Using and array of payouts generate array of XDR payment transactions.
     * 
     * @param array $payouts
     * @param string $sourcePublicKey
     * @return array
     * @throws Exception
     */
    public function buildUnsignedFromPayouts($payouts, $sourcePublicKey)
    {
        if (empty($payouts)) {
            throw new Exception("Payouts array cannot be empty");
        }
        if (empty($sourcePublicKey)) {
            throw new Exception("Source account public key cannot be empty");
        }

        $xdrs = [];

        foreach ($payouts as $payout) {
            $xdrs[] = $this->buildUnsignedFromPayout($payout, $sourcePublicKey);
        }

        return $xdrs;
    }

    /**
     * Using a payout build an unsigned transaction.
     * 
     * @param Payout $payout
     * @param string $sourcePublicKey
     * @param string $sequenceNumber
     * @return string
     * @throws Exception
     */
    public function buildUnsignedFromPayout(Payout $payout, $sourcePublicKey, $sequenceNumber = null)
    {
        if (empty($payout)) {
            throw new Exception("Payout object empty");
        }

        $amount = $payout->account_payout_amount;
        $destinationPublicKey = $payout->user->stellar_public_key;

        return $this->transactionBuilder->buildUnsigned($amount, $sourcePublicKey, $destinationPublicKey, $sequenceNumber);
    }

}
