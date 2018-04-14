<?php

namespace lumenous\Services\Stellar;

use lumenous\Models\CharityPayout;
use Exception;
use lumenous\Services\Stellar\TransactionBuilder;

class CharityPayoutTransactionBuilder {

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
     * Using and array of charity payouts generate array of XDR payment transactions.
     * 
     * @param array $charityPayouts
     * @param string $sourcePublicKey
     * @return array
     * @throws Exception
     */
    public function buildUnsignedFromCharityPayouts($charityPayouts, $sourcePublicKey)
    {
        if (empty($charityPayouts)) {
            throw new Exception("Charity Payouts array cannot be empty");
        }
        if (empty($sourcePublicKey)) {
            throw new Exception("Source account public key cannot be empty");
        }

        $xdrs = [];

        foreach ($charityPayouts as $charityPayout) {
            $xdrs[] = $this->buildUnsignedFromCharityPayout($charityPayout, $sourcePublicKey);
        }

        return $xdrs;
    }

    /**
     * Using a charity payout build an unsigned transaction.
     * 
     * @param CharityPayout $charityPayout
     * @param string $sourcePublicKey
     * @param string $sequenceNumber
     * @return string
     * @throws Exception
     */
    public function buildUnsignedFromCharityPayout(CharityPayout $charityPayout, $sourcePublicKey, $sequenceNumber = null)
    {
        if (empty($charityPayout)) {
            throw new Exception("Charity Payout object empty");
        }

        $amount = $charityPayout->amount;
        $destinationPublicKey = $charityPayout->charity_public_key;

        return $this->transactionBuilder->buildUnsigned($amount, $sourcePublicKey, $destinationPublicKey, $sequenceNumber);
    }

}
