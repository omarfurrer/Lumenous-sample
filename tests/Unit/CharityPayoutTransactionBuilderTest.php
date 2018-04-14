<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use lumenous\Services\Stellar\CharityPayoutTransactionBuilder;
use ZuluCrypto\StellarSdk\Model\StellarAmount;
use lumenous\Models\CharityPayout;

class CharityPayoutTransactionBuilderTest extends TestCase {

    use RefreshDatabase;

    /**
     * @var CharityPayoutTransactionBuilder 
     */
    protected $charityPayoutTransactionBuilderService;

    /**
     * source public key to test with. 
     * Warning : DO NOT POST TRANSACTION FROM THIS ACCOUNT TO PERSIST SEQUENCE NUMBER. 
     * current SQN = 33602157676593153
     * 
     * @var string 
     */
    protected $sourcePublicKey;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->charityPayoutTransactionBuilderService = $this->app->make(CharityPayoutTransactionBuilder::class);
        $this->sourcePublicKey = 'GANCN4SEI56VVZLHAKFXUQBQEQZAJYWNPVU4I6PGQXKY2PVHAFL3MSH6';
    }

    /**
     * Test generating unsigned XDR for payment operation transaction from a Charity Payout.
     * Warning : IF sequence number of source account changes, test will fail.
     * 
     * @return void
     */
    public function testUnsignedCharityPayoutXdr()
    {

        $charityPayout = factory(CharityPayout::class)->create([
            'amount' => 10 * StellarAmount::STROOP_SCALE,
            'charity_public_key' => 'GCSMQEALJWYON37LA4N2VRWKWIGQFFQMQWLLCR3B7MNV6P3OFX5GHNFK'
        ]);

        $xdr = $this->charityPayoutTransactionBuilderService->buildUnsignedFromCharityPayout($charityPayout, $this->sourcePublicKey);

        $correctXdr = "AAAAABom8kRHfVrlZwKLekAwJDIE4s19acR55oXVjT6nAVe2AAAAZAB3YPwAAAABAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAApMgQC02w5u/rBxuqxsqyDQKWDIWWsUdh+xtfP24t+mMAAAAAAAAAAAX14QAAAAAAAAAAAA==";
        $this->assertEquals($correctXdr, $xdr, "Make sure src account sequence number did not change 33602157676593153");
    }

}
