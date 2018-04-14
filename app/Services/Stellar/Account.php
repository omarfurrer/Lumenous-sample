<?php

namespace lumenous\Services\Stellar;

use Exception;
use Illuminate\Support\Facades\Log;
use \ZuluCrypto\StellarSdk\Keypair;
use \ZuluCrypto\StellarSdk\XdrModel\Operation\SetOptionsOp;
use \ZuluCrypto\StellarSdk\XdrModel\SignerKey;
use \ZuluCrypto\StellarSdk\XdrModel\Signer;
use ZuluCrypto\StellarSdk\Horizon\Exception\PostTransactionException;
use lumenous\Services\Stellar\client;

class Account {

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
     * Handle adding a signer to an existing account
     * 
     * @param string $masterAccountPublicKey
     * @param string $masterAccountPrivateKey
     * @param string $signerPublicKey
     * @param string $signerWeight
     * @return boolean
     * @throws Exception
     */
    public function addSigner($masterAccountPublicKey, $masterAccountPrivateKey, $signerPublicKey, $signerWeight = 1)
    {
        if (empty($masterAccountPrivateKey) || empty($masterAccountPrivateKey) || empty($signerPublicKey)) {
            throw new Exception('A key is missing');
        }

        if ($signerWeight < 0 || $signerWeight > 255) {
            throw new Exception('Wrong Weight');
        }

        $newSigner = Keypair::newFromPublicKey($signerPublicKey);
        $signerKey = SignerKey::fromKeypair($newSigner);
        $newAccountSigner = new Signer($signerKey, $signerWeight);

        $optionsOperation = new SetOptionsOp();
        $optionsOperation->updateSigner($newAccountSigner);

        $transaction = $this->stellarClient
                ->getServer()
                ->buildTransaction($masterAccountPublicKey)
                ->addOperation($optionsOperation);

        try {
            $response = $transaction->submit($masterAccountPrivateKey);
        } catch (PostTransactionException $e) {
            Log::error('Unable to add signer to account',
                       [
                'message' => $e->getMessage(),
                'account_public_key' => $masterAccountPublicKey,
                'signer_public_key' => $signerPublicKey,
                'signer_weight' => $signerWeight
            ]);
            return false;
        } catch (Exception $e) {
            Log::error('Unable to add signer to account',
                       [
                'message' => $e->getMessage(),
                'account_public_key' => $masterAccountPublicKey,
                'signer_public_key' => $signerPublicKey,
                'signer_weight' => $signerWeight
            ]);
            return false;
        }

        return true;
    }

}
