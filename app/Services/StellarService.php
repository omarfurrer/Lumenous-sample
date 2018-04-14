<?php

namespace lumenous\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use ZuluCrypto\StellarSdk\Server;
use lumenous\Models\InflationEffect;
use ZuluCrypto\StellarSdk\Horizon\ApiClient;
use ZuluCrypto\StellarSdk\Model\Ledger;
use ZuluCrypto\StellarSdk\Keypair;
use ZuluCrypto\StellarSdk\XdrModel\Operation\SetOptionsOp;
use phpseclib\Math\BigInteger;
use ZuluCrypto\StellarSdk\Horizon\Exception\PostTransactionException;
Use Exception;
use lumenous\Services\Stellar\client as StellarClient;

class StellarService {

    /**
     * Base URL for fetching inflation pool data.
     */
    CONST INFLATION_POOL_URL = 'https://fed.network/inflation/';

    /**
     * Horizon Base URLS for live & test
     */
    CONST HORIZON_BASE_URL = 'https://horizon.stellar.org/';
    CONST HORIZON_TEST_BASE_URL = 'https://horizon-testnet.stellar.org/';

    /**
     * Operation types
     */
    CONST OPERATION_MANAGE_DATA = 10;
    CONST OPERATION_INFLATION = 9;
    CONST OPERATION_PATH_PAYMENT = 2;

    /**
     * Thresholds types.
     */
    CONST LOW_THRESHOLD = 'low_threshold';
    CONST MEDIUM_THRESHOLD = 'med_threshold';
    CONST HIGH_THRESHOLD = 'high_threshold';

    /**
     * Client used to send requests.
     * 
     * @var Client 
     */
    protected $guzzleClient;

    /**
     * @var StellarClient 
     */
    protected $stellarClient;

    /**
     * Default Constructor
     * 
     * @param StellarClient $stellarClient
     */
    public function __construct(StellarClient $stellarClient)
    {
        $this->stellarClient = $stellarClient;
        $this->init();
    }

    /**
     * init
     */
    public function init()
    {
        $this->guzzleClient = new Client([
            'base_uri' => $this->getIsTestMode() ? self::HORIZON_TEST_BASE_URL : self::HORIZON_BASE_URL
        ]);
    }

    /**
     * Get the inflation pool data of a specific account.
     * 
     * @param string $publicKey
     * @return mixed
     */
    protected function getInflationData($publicKey)
    {
        if (empty($publicKey)) {
            return false;
        }

        $client = new Client(['base_uri' => self::INFLATION_POOL_URL]);

        try {

            $response = $client->request('GET', $publicKey);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorResponse = $e->getResponse();
                $errorCode = $errorResponse->getStatusCode(); // 501
                $errorMessage = $errorResponse->getReasonPhrase(); // "Not Implemented"
            }

            return false;
        }

        return $response->getBody()->getContents();
    }

    /**
     * Get the inflation pool entries for a specific account.
     * 
     * @param string $publicKey
     * @return mixed
     */
    public function getInflationPoolEntries($publicKey)
    {
        $inflationData = $this->getInflationData($publicKey);

        if (!$inflationData) {
            return $inflationData;
        }

        $inflationData = json_decode($inflationData, true);

        if (empty($inflationData['entries'])) {
            return false;
        }

        return $inflationData['entries'];
    }

    /**
     * Get effects of an account. Supports streaming efects with callback.
     * 
     * @param string $publicKey
     * @param string $type
     * @param string $cursor
     * @param integer $limit
     * @param string $order
     * @param boolean $stream
     * @param function $streamCallback
     * @return mixed
     */
    public function getAccountEffects(
    $publicKey, $type = null, $cursor = null, $limit = null, $order = 'desc', $stream = false, $streamCallback = null
    )
    {
        $options = [
            'query' => compact('cursor', 'limit', 'order')
        ];

        // Set appropriate headers/request options for streaming
        if ($stream) {
            // If no callback and stream is true, return false
            if (empty($streamCallback)) {
                return false;
            }
            $options = $this->_prepareStreamRequestOptions($options);
        }

        $body = $this->_sendRequest("accounts/{$publicKey}/effects", 'GET', $options, false);

        return $this->_parseGetAndStreamResponse($body, $stream, $type, $streamCallback);
    }

    /**
     * Get operations of an account. Supports streaming operations with callback.
     * 
     * @param string $publicKey
     * @param string $type
     * @param string $cursor
     * @param integer $limit
     * @param string $order
     * @param boolean $stream
     * @param function $streamCallback
     * @return mixed
     */
    public function getAccountOperations(
    $publicKey, $type = null, $cursor = null, $limit = null, $order = 'desc', $stream = false, $streamCallback = null)
    {

        $options = [
            'query' => compact('cursor', 'limit', 'order')
        ];

        // Set appropriate headers/reuqest options for streaming
        if ($stream) {
            // If no callback and stream is true, return false
            if (empty($streamCallback)) {
                return false;
            }
            $options = $this->_prepareStreamRequestOptions($options);
        }

        $body = $this->_sendRequest("accounts/{$publicKey}/operations", 'GET', $options, false);

        return $this->_parseGetAndStreamResponse($body, $stream, $type, $streamCallback);
    }

    /**
     * Prepare request options for streaming request.
     * 
     * @param type $options
     * @return string
     */
    protected function _prepareStreamRequestOptions($options)
    {
        $options['stream'] = true;
        $options['read_timeout'] = null;
        $options['headers'] = [
            'Accept' => 'text/event-stream',
        ];
        return $options;
    }

    /**
     * Parses the response back from an endpoint which can fetch/stream data.
     * 
     * @param String $body
     * @param Boolean $stream
     * @param String $type
     * @param function $streamCallback
     * @return mixed
     */
    protected function _parseGetAndStreamResponse($body, $stream, $type, $streamCallback)
    {
        // If no stream, return results
        if (!$stream) {

            $contents = json_decode($body, true);
            $contents = collect($contents['_embedded']['records']);

            // Filter specific operation type
            if (!empty($type)) {
                $contents = $contents->filter(function($operation) use($type) {
                    return $operation['type_i'] == $type;
                });
            }

            return $contents->all();
        }

        // If stream, parse results and return one by one in callback 
        while (!$body->eof()) {
            $line = '';

            $char = null;
            while ($char != "\n") {
                $line .= $char;
                $char = $body->read(1);
            }

            // Ignore empty lines
            if (!$line)
                continue;

            // Ignore "data: hello" handshake
            if (strpos($line, 'data: "hello"') === 0)
                continue;

            // Ignore lines that don't start with "data: "
            $sentinel = 'data: ';
            if (strpos($line, $sentinel) !== 0)
                continue;

            // Remove sentinel prefix
            $json = substr($line, strlen($sentinel));

            $decoded = json_decode($json, true);

            if ($decoded) {
                // Filter specific operation type
                if (!empty($type)) {
                    if ($decoded['type_i'] != $type) {
                        continue;
                    }
                }
                $streamCallback($decoded);
            }
        }
    }

    /**
     * Stream ledgers with callback function.
     * 
     * @param string $cursor
     * @param callable $callback
     * @return void
     */
    public function streamLedgers($cursor = 'now', $callback)
    {
        if (empty($callback)) {
            return false;
        }

        if ($this->getIsTestMode()) {
            $client = ApiClient::newTestnetClient();
        } else {
            $client = ApiClient::newPublicClient();
        }

        $client->streamLedgers($cursor, $callback);
    }

    /**
     * Send lumens from app account to any other account.
     * 
     * @param string $sendToKey
     * @param integer $amount
     * @return mixed
     */
    public function sendNativePayment($sendToKey, $amount)
    {
        return $this->sendNativePaymentFrom($sendToKey, $amount, $this->getStellarClient()->getAppPublicKey(), $this->appCredentials['secret_key']);
    }

    /**
     * Send lumens from any account to any other account.
     * 
     * @param string $toPublicKey
     * @param integer $amount
     * @param string $fromPublicKey
     * @param string $fromSecretKey
     * @return mixed
     */
    public function sendNativePaymentFrom($toPublicKey, $amount, $fromPublicKey, $fromSecretKey)
    {
        if (empty($toPublicKey) || empty($amount) || empty($fromPublicKey) || empty($fromSecretKey)) {
            return false;
        }

        try {
            // using Big Integer to make sure that passed value is in STROOPS
            $result = $this->getServer()->getAccount($fromPublicKey)->sendNativeAsset($toPublicKey, new BigInteger($amount), $fromSecretKey);
        } catch (PostTransactionException $e) {
            Log::error('Unable to send payment',
                       [
                'message' => $e->getMessage(),
                'send_to_key' => $toPublicKey,
                'send_from_key' => $fromPublicKey,
                'amount' => $amount
            ]);
            return FALSE;
        } catch (\Exception $e) {
            Log::error('Unable to send payment',
                       [
                'message' => $e->getMessage(),
                'send_to_key' => $toPublicKey,
                'send_from_key' => $fromPublicKey,
                'amount' => $amount
            ]);
            return FALSE;
        }

        return $result;
    }

    /**
     * Get account native balance.
     * 
     * @param string $publicKey
     * @param boolean $inStroops
     * @return mixed
     */
    public function getNativeBalance($publicKey, $inStroops = false)
    {
        if (empty($publicKey)) {
            return false;
        }

        $account = $this->getServer()->getAccount($this->getStellarClient()->getAppPublicKey());

        return $inStroops ? $account->getNativeBalanceStroops() : $account->getNativeBalance();
    }

    /**
     * Using a local inflation object, return the sequence number of the associated ledger.
     * 
     * @param InflationEffect $effect
     * @return mixed
     */
    public function getLedgerSequenceFromInflationEffect(InflationEffect $effect)
    {
        $operationLink = $effect->data['_links']['operation']['href'];
        $operationID = explode('/operations/', $operationLink)[1];

        $operation = $this->getOperation($operationID);

        if (!$operation) {
            return $operation;
        }

        $transactionHash = $operation['transaction_hash'];

        $transaction = $this->getTransaction($transactionHash);

        if (!$transaction) {
            return $transaction;
        }

        return $transaction['ledger'];
    }

    /**
     * Get a specific operation using its ID. 
     * 
     * @param string $id
     * @return mixed
     */
    public function getOperation($id)
    {
        if (empty($id)) {
            return false;
        }

        return $this->_sendRequest("operations/{$id}");
    }

    /**
     * Get a specific transaction using its hash. 
     * 
     * @param string $hash
     * @return mixed
     */
    public function getTransaction($hash)
    {
        if (empty($hash)) {
            return false;
        }

        return $this->_sendRequest("transactions/{$hash}");
    }

    /**
     * Get a specific ledger using its sequence number. 
     * 
     * @param string $sequence
     * @return mixed
     */
    public function getLedger($sequence)
    {
        if (empty($sequence)) {
            return false;
        }

        return $this->_sendRequest("ledgers/{$sequence}");
    }

    /**
     * Set the Inflation destination of an account.
     * 
     * @param String $publicKey
     * @param String $secretKey
     * @param String $inflationDestination
     * @return mixed
     */
    public function SetInflationDestination($publicKey, $secretKey, $inflationDestination)
    {
        if (empty($publicKey) || empty($secretKey) || empty($inflationDestination)) {
            return FALSE;
        }

        $optionsOperation = new SetOptionsOp();
        $optionsOperation->setInflationDestination($inflationDestination);

        return $this->getServer()->buildTransaction($publicKey)
                        ->addOperation($optionsOperation)
                        ->submit($secretKey);
    }

    /**
     * Create a number of test accounts.
     * 
     * @param Integer $numberOfAccounts
     * @return boolean|array
     */
    public function createAccounts($numberOfAccounts = 10)
    {
        if (!$this->getIsTestMode()) {
            return false;
        }

        $accounts = [];
        for ($i = 0; $i < $numberOfAccounts; $i++) {
            $accounts[] = $this->createAccount();
        }

        return $accounts;
    }

    /**
     * Create a stellar test account and fund it.
     * 
     * @param boolean $fundAccount
     * @return boolean|array array holding account information id/public/secret
     */
    public function createAccount($fundAccount = true)
    {
        if (!$this->getIsTestMode()) {
            return false;
        }

        $keypair = Keypair::newFromRandom();

        $id = $keypair->getAccountId();
        $publicKey = $keypair->getPublicKey();
        $secretKey = $keypair->getSecret();

        if ($fundAccount) {
            $accountFunded = $this->fundAccount($publicKey);
            if (!$accountFunded) {
                return $accountFunded;
            }
        }

        return compact('id', 'publicKey', 'secretKey');
    }

    /**
     * Fund a test account by its public key.
     * 
     * @param String $publicKey
     * @return boolean
     */
    public function fundAccount($publicKey)
    {
        if (empty($publicKey)) {
            return FALSE;
        }

        $response = $this->_sendRequest("friendbot?addr={$publicKey}");
        if (!$response) {
            return $response;
        }

        return true;
    }

    /**
     * Get a specific threshold for app account.
     * 
     * @param String $type
     * @return String
     * @throws Exception
     */
    public function getAppThreshold($type)
    {
        if (empty($type)) {
            throw new Exception("Threshold cannot be empty.");
        }

        return $this->getThreshold($this->getStellarClient()->getAppPublicKey(), $type);
    }

    /**
     * Get specific account threshold.
     * 
     * @param String $publicKey
     * @param String $type
     * @return Integer
     * @throws Exception
     */
    public function getThreshold($publicKey, $type)
    {
        if (empty($publicKey) || empty($type)) {
            throw new Exception("Argument missing");
        }
        return $this->getThresholds($publicKey)[$type];
    }

    /**
     * Get Thresholds related to an account.
     * 
     * @param String $publicKey
     * @return array
     * @throws Exception
     */
    public function getThresholds($publicKey)
    {
        if (empty($publicKey)) {
            throw new Exception("Public key cannot be empty");
        }

        return $this->getServer()->getAccount($publicKey)->getThresholds();
    }

    /**
     * send a guzzle HTTP request.
     * 
     * @param string $uri
     * @param string $method
     * @param array $options
     * @param boolean $jsonFormat
     * @return mixed
     */
    protected function _sendRequest($uri, $method = 'GET', $options = [], $jsonFormat = true)
    {
        try {
            $response = $this->guzzleClient->request($method, $uri, $options);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $errorResponse = $e->getResponse();
                $errorCode = $errorResponse->getStatusCode();
                $errorMessage = $errorResponse->getReasonPhrase();
                Log::error('Error getting operation', [
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage
                ]);
            }
            return false;
        }

        $response = $jsonFormat ? json_decode($response->getBody()->getContents(), true) : $response->getBody();

        return $response;
    }

    /**
     * Return Horizon server instance.
     * 
     * @return Server
     */
    public function getServer()
    {
        return $this->stellarClient->getServer();
    }

    /**
     * Return true if app is in test mode.
     * 
     * @return Boolean
     */
    public function getIsTestMode()
    {
        return $this->stellarClient->getIsTestMode();
    }

    /**
     * Return Stellar Client instance.
     * 
     * @return StellarClient
     */
    public function getStellarClient()
    {
        return $this->stellarClient;
    }

//    public function getPayments($publicKey, $type = null, $limit = 10, $order = 'desc')
//    {
//        if (empty($publicKey)) {
//            return false;
//        }
//
//        $client = new Client(['base_uri' => $this->isTestMode ? self::HORIZON_TEST_BASE_URL : self::HORIZON_BASE_URL]);
//
//        try {
//            $response = $client->request('GET', "accounts/{$publicKey}/payments", [
//                'query' => compact('limit', 'order')
//            ]);
//        } catch (RequestException $e) {
//            if ($e->hasResponse()) {
//                $errorResponse = $e->getResponse();
//                $errorCode = $errorResponse->getStatusCode();
//                $errorMessage = $errorResponse->getReasonPhrase();
//            }
//            return false;
//        }
//
//        $contents = json_decode($response->getBody()->getContents(), true);
//        $contents = collect($contents['_embedded']['records']);
//
//        if (!empty($type)) {
//            $contents = $contents->filter(function($payment) use($type) {
//                return $payment['type_i'] == $type;
//            });
//        }
//
//        return $contents->all();
//    }

    /**
     * Get dest key set at the inflation pool details of an account.
     * 
     * @param string $publicKey
     * @return mixed
     */
//    public function getInflationDestinationKey($publicKey)
//    {
//        $inflationData = $this->getInflationData($publicKey);
//
//        if (!$inflationData) {
//            return $inflationData;
//        }
//
//        $inflationData = json_decode($inflationData, true);
//
//        if (empty($inflationData['inflationdest'])) {
//            return false;
//        }
//
//        return $inflationData['inflationdest'];
//    }
}
