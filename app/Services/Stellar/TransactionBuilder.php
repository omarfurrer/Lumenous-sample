<?php

namespace lumenous\Services\Stellar;

use phpseclib\Math\BigInteger;
use Exception;
use lumenous\Services\Stellar\client;
use \ZuluCrypto\StellarSdk\Server;

class TransactionBuilder {

    /**
     * @var client 
     */
    protected $stellarClient;

    /**
     * Default Constructor
     * 
     * @param Client $stellarClient
     */
    public function __construct(client $stellarClient)
    {
        $this->stellarClient = $stellarClient;
    }

    /**
     * Build an unsigned payment transaction 
     * 
     * @param Integer $amount
     * @param string $sourcePublicKey
     * @param string $destinationPublicKey
     * @param string $sequenceNumber
     * @return string
     * @throws Exception
     */
    public function buildUnsigned($amount, $sourcePublicKey, $destinationPublicKey, $sequenceNumber = null)
    {
        if (empty($amount) || empty($sourcePublicKey) || empty($destinationPublicKey)) {
            throw new Exception("Argument missing.");
        }

        $transactionBuilder = $this->getServer()->buildTransaction($sourcePublicKey);

        if (!empty($sequenceNumber)) {
            $transactionBuilder->setSequenceNumber($sequenceNumber);
        }

        return $transactionBuilder
                        ->addLumenPayment($destinationPublicKey, new BigInteger($amount))
                        ->getTransactionEnvelope()
                        ->toBase64();
    }

    /**
     * Get the stellar server instance.
     * 
     * @return Server
     */
    public function getServer()
    {
        return $this->stellarClient->getServer();
    }

}
