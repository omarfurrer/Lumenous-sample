<?php

namespace lumenous\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use lumenous\Mail\ContactUs;
use lumenous\Http\Requests\Pages\ContactUsRequest;
use lumenous\Services\Stellar\TransactionBatch;
use lumenous\Services\Stellar\Transaction;
Use lumenous\Services\Stellar\Signer as SignerService;
use ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
Use ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use ZuluCrypto\StellarSdk\Keypair;
Use ZuluCrypto\StellarSdk\Server;
use lumenous\Services\StellarService;
use lumenous\Services\Stellar\Payout as PayoutService;

class PagesController extends Controller {

    protected $transactionBatchService;
    protected $transactionService;
    protected $signerService;
    protected $stellarService;
    protected $payoutService;

    function __construct(TransactionBatch $transactionBatchService, Transaction $transactionService, SignerService $signerService
    , StellarService $stellarService, PayoutService $payoutService)
    {
        $this->transactionBatchService = $transactionBatchService;
        $this->transactionService = $transactionService;
        $this->signerService = $signerService;
        $this->signerService = $signerService;
        $this->stellarService = $stellarService;
        $this->payoutService = $payoutService;
        return parent::__construct();
    }

    /**
     * Displays home page.
     * 
     * @return \Illuminate\View\View
     */
    public function getHome()
    {
//        factory(\lumenous\Models\ActiveAccount::class, 1)->states('real-stellar-account')->create([
//            'user_id' => \lumenous\User::find(1)->id
//        ]);
//        $activeAccounts = factory(\lumenous\Models\ActiveAccount::class, 10)->states('real-stellar-account')->create();
//        $inflationEffect = factory(\lumenous\Models\InflationEffect::class, 1)->create()->first();
//        $activeAccounts = factory(\lumenous\Models\ActiveAccount::class, 3)->states('real-stellar-account')->create();
//        $inflationEffect = factory(\lumenous\Models\InflationEffect::class, 1)->create([
//                    'amount' => 100000
//                ])->first();
//        $payouts = factory(\lumenous\Models\Payout::class, 3)->states('real-stellar-account')->create();
//        $payouts = factory(\lumenous\Models\Payout::class, 3)->states('real-stellar-account')->make();
//        $payouts = [];
//        $this->payoutService->init();
//        $this->payoutService->setInflationEffect($inflationEffect);
//        $payouts = $this->payoutService->executeActiveAccountsPayout($activeAccounts);
//        $charityPayouts = $this->payoutService->executeCharityPayouts($activeAccounts);
//        $transactions = $this->transactionService->createManyFromPayout($payouts, config('lumenaries.stellar_public_key'));
//        $transactions = $this->transactionService->createManyFromPayout($payouts, config('lumenaries.stellar_public_key'));
//        $charityPayouts = factory(\lumenous\Models\CharityPayout::class, 3)->states('real-stellar-account')->make();
//        $charityTransactions = $this->transactionService->createManyFromCharityPayout($charityPayouts, config('lumenaries.stellar_public_key'));
//        $tb = $this->transactionBatchService->createWithAppKey($payouts, $charityPayouts);
//        dd($tb);
//        $this->transactionService->create($payouts->first(), config('lumenaries.stellar_public_key'));
//        dd($transactions);
//        dd($tb);
//        $this->transactionBatchService->notifySigners($tb);
//        $this->transactionBatchService->sign($tb, \lumenous\User::find(2), "SBIUNFCGUCXS6JUH4FII6LEKSX2ZUSVFP4PEIUSLUPGFPWCOFEJQBWNK");
//        $this->transactionBatchService->sign($tb, \lumenous\User::find(1), "SDZE42SYD6BDEDIXSI4MRBR3HMKDDIE3BH5UAVT5O6RNHLDGD7TAD6CU");
//        $server = Server::testNet();
//           $transactionEnvelope = \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope::fromXdr(new \ZuluCrypto\StellarSdk\Xdr\XdrBuffer(base64_decode("AAAAADntA24LoSLcXQAi1C1TejLZYkWXEH9gg1n7CepmVemCAAAAZABkx6wAAAAxAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAApMgQC02w5u/rBxuqxsqyDQKWDIWWsUdh+xtfP24t+mMAAAAAAAAAAFloLwAAAAAAAAAAAWZV6YIAAABAjQZi0/uQ0LPIe5C/V4LDxSEMmPCe1Irp5zDMtdn+JfhO+wRMLmmXz4PtMZOhnXYRashGWYseK6KZT2ud/rZiDQ==")));
//        $xdr = $transactionEnvelope->sign(\ZuluCrypto\StellarSdk\Keypair::newFromSeed("SDZE42SYD6BDEDIXSI4MRBR3HMKDDIE3BH5UAVT5O6RNHLDGD7TAD6CU"),
//                                                                                                      \ZuluCrypto\StellarSdk\Server::testNet())->toBase64();
//        $transactionEnvelope = \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope::fromXdr(new \ZuluCrypto\StellarSdk\Xdr\XdrBuffer(base64_decode("AAAAADntA24LoSLcXQAi1C1TejLZYkWXEH9gg1n7CepmVemCAAAAZABkx6wAAAAxAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAApMgQC02w5u/rBxuqxsqyDQKWDIWWsUdh+xtfP24t+mMAAAAAAAAAAFloLwAAAAAAAAAAAA==")));
//        $xdr = $transactionEnvelope->sign(\ZuluCrypto\StellarSdk\Keypair::newFromSeed("SBIUNFCGUCXS6JUH4FII6LEKSX2ZUSVFP4PEIUSLUPGFPWCOFEJQBWNK"),
//      
        // GD4JRFLPF4AGYQTLCMZ7Q7DRLGQZQTGWOOKDUCNRVKG66G5ZVYYFT76M
        // GC6SMUK2VBDYPLOK67NBVSYYCIBJNUTU5CD7BHXLAXZ6C35TL63DOHDP
//        $sourceKeypair = Keypair::newFromSeed('SAA2U5UFW65DW3MLVX734BUQIHAWANQNBLTFT47X2NVVBCN7X6QC5AOG');
//        dd($sourceKeypair->getPublicKey());
//        $tb = $this->transactionBatchService->get(1);
//        $transaction = $tb->transactions[1];
//        $this->transactionService->submit($transaction);
//      GBDFMZ3TWL5K43WQFCCEQMEK7FLBX2NTXFZHGYWV2OBE6SUOIMM2KRXU          
//        $keyPair = Keypair::newFromSeed("SBTS6EEZKX2IVJVUEX26CQODY6VY7JCWJMKVZOEQ25AFBUARY4H6J2VN");
//        dd($keyPair->getAccountId());
//        $decodedXdr = base64_decode("AAAAAADvRqsOLIENfT1qiZRt/YCC0JLvPY9Ad07QfNhb1IyAAAAAZAB51DQAAAACAAAAAAAAAAAAAAABAAAAAAAAAAEAAAAAWbNjWKDzuOLv8FyVxdd1gbxKjJD3E7FSfrLjZl2tatgAAAAAAAAAAFloLwAAAAAAAAAAAVvUjIAAAABAhYeaID3i4+o7jVpkP1LlVcDC2YxgPgzwulVHOArHE0ADeuK442E6AlQl7K3JnU8GYNsaUH9RUG6WNTnS6lLbDQ==");
//        $transactionEnvelope = TransactionEnvelope::fromXdr(new XdrBuffer($decodedXdr));
//        $xdr = $transactionEnvelope->sign($keyPair, $server)->toBase64();
//        print_r($xdr);
//        dd($this->transactionBatchService->isEligbleForSubmission(\lumenous\Models\TransactionBatch::find(24)));
//        dd($this->stellarService->getThresholds("GBDFMZ3TWL5K43WQFCCEQMEK7FLBX2NTXFZHGYWV2OBE6SUOIMM2KRXU"));
//        $this->transactionBatchService->submit(\lumenous\Models\TransactionBatch::find(3));
        return view('pages.home');
    }

    /**
     * Displays contact us page.
     * 
     * @return \Illuminate\View\View
     */
    public function getContact()
    {
        return view('pages.contact-us');
    }

    /**
     * Submit contact us form.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function postContact(ContactUsRequest $request)
    {
        $name = $request->get('name');
        $email = $request->get('email');
        $subject = $request->get('subject');
        $message = $request->get('message');

        $to = config('lumenaries.contact-us-email');
        Mail::to($to)->send(new ContactUs(compact('name', 'email', 'subject', 'message')));

        return view('pages.contact-us');
    }

    /**
     * Display About page.
     * 
     * @return \Illuminate\View\View
     */
    public function getAbout()
    {
        return view('pages.about');
    }

}
