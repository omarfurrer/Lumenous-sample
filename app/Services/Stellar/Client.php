<?php

namespace lumenous\Services\Stellar;

use ZuluCrypto\StellarSdk\Server;

class client {

    /**
     * Indicates whether to use test Horizon or live.
     * 
     * @var boolean 
     */
    protected $isTestMode;

    /**
     * Stellar SDK server instance
     * 
     * @var Server 
     */
    protected $server;

    /**
     * @var array 
     */
    protected $appCredentials;

    /**
     * Default Constructor
     */
    public function __construct()
    {
        $this->isTestMode = config('lumenaries.horizon_test_mode');

        if ($this->isTestMode) {
            $this->server = Server::testNet();
        } else {
            $this->server = Server::publicNet();
        }

        $this->appCredentials = [
            'public_key' => config('lumenaries.stellar_public_key'),
            'secret_key' => config('lumenaries.stellar_secret_key')
        ];
    }

    /**
     * Returns true if in test mode.
     * 
     * @return Boolean
     */
    public function getIsTestMode()
    {
        return $this->isTestMode;
    }

    /**
     * Returns server instance.
     * 
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Get application stellar public key.
     * 
     * @return String
     */
    public function getAppPublicKey()
    {
        return $this->appCredentials['public_key'];
    }

    /**
     * Get application stellar secret key.
     * 
     * @return String
     */
    public function getAppSecretKey()
    {
        return $this->appCredentials['secret_key'];
    }

}
