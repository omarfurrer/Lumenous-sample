<?php

namespace lumenous\Jobs;

use lumenous\Models\InflationEffect;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use lumenous\Services\Stellar\Payout;
use lumenous\Repositories\Interfaces\ActiveAccountsRepositoryInterface;
use lumenous\Services\Stellar\TransactionBatch;

class GenerateTransactionsJob implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * @var InflationEffect 
     */
    protected $inflationEffect;

    /**
     * Create a new job instance.
     *
     * @param InflationEffect $inflationEffect
     * @return void
     */
    public function __construct(InflationEffect $inflationEffect)
    {
        $this->inflationEffect = $inflationEffect;
    }

    /**
     * Execute the job.
     * 
     * @param Payout $payoutService  
     * @return void
     */
    public function handle(Payout $payoutService, TransactionBatch $transactionBatchService, ActiveAccountsRepositoryInterface $activeAccountsRepository)
    {
        $activeAccounts = $activeAccountsRepository->all();

        $payoutService->init();

        $payoutService->setInflationEffect($this->inflationEffect);

        $accountPayouts = $payoutService->executeActiveAccountsPayout($activeAccounts);

        $charityPayouts = $payoutService->executeCharityPayouts($activeAccounts);

        $transactionBatchService->createWithAppKey($accountPayouts, $charityPayouts);
    }

}
