<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use lumenous\Services\Stellar\PayoutTransactionBuilder;
use ZuluCrypto\StellarSdk\Model\StellarAmount;
use lumenous\Models\Payout;

class PayoutTransactionBuilderTest extends TestCase {

    use RefreshDatabase;

    /**
     * @var PayoutTransactionBuilder 
     */
    protected $payoutTransactionBuilderService;

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
        $this->payoutTransactionBuilderService = $this->app->make(PayoutTransactionBuilder::class);
        $this->sourcePublicKey = 'GANCN4SEI56VVZLHAKFXUQBQEQZAJYWNPVU4I6PGQXKY2PVHAFL3MSH6';
    }

    /**
     * Test generating unsigned XDR for payment operation transaction from a Payout.
     * Warning : IF sequence number of source account changes, test will fail.
     * 
     * @return void
     */
    public function testUnsignedPayoutXdr()
    {

        $payout = factory(Payout::class)->create([
            'account_payout_amount' => 10 * StellarAmount::STROOP_SCALE,
            'user_id' => function() {
                return factory(\lumenous\User::class)->create([
                            'stellar_public_key' => 'GCSMQEALJWYON37LA4N2VRWKWIGQFFQMQWLLCR3B7MNV6P3OFX5GHNFK'
                ]);
            }
        ]);

        $xdr = $this->payoutTransactionBuilderService->buildUnsignedFromPayout($payout, $this->sourcePublicKey);

        $correctXdr = "AAAAABom8kRHfVrlZwKLekAwJDIE4s19acR55oXVjT6nAVe2AAAAZAB3YPwAAAABAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAApMgQC02w5u/rBxuqxsqyDQKWDIWWsUdh+xtfP24t+mMAAAAAAAAAAAX14QAAAAAAAAAAAA==";
        $this->assertEquals($correctXdr, $xdr, "Make sure src account sequence number did not change 33602157676593153");
    }

//    public function testKeypair()
//    {
//        $sourceKeypair = Keypair::newFromSeed('SAA2U5UFW65DW3MLVX734BUQIHAWANQNBLTFT47X2NVVBCN7X6QC5AOG');
//        $this->assertEquals("GD4JRFLPF4AGYQTLCMZ7Q7DRLGQZQTGWOOKDUCNRVKG66G5ZVYYFT76M", $sourceKeypair->getPublicKey());
//        $sourceKeypair = Keypair::newFromSeed('SCEH7K2RZFAZ3OJ6GV74M53LZSHS3S2HFFRMEMEOV4NCSO5J6CQCFNTV');
//        $this->assertEquals("GC743SXHRGBU4LPWION2MNJWB7CDE2HGN66MAZWSHHATPW5EXWEBMQTT", $sourceKeypair->getPublicKey());
//    }

}
