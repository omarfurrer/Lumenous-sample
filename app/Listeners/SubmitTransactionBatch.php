<?php

namespace lumenous\Listeners;

use lumenous\Events\TransactionBatchSigned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use lumenous\Services\Stellar\TransactionBatch as TransactionBatchService;

class SubmitTransactionBatch implements ShouldQueue {

    /**
     * @var TransactionBatchService
     */
    protected $transactionBatchService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(TransactionBatchService $transactionBatchService)
    {
        $this->transactionBatchService = $transactionBatchService;
    }

    /**
     * Handle the event.
     *
     * @param  TransactionBatchSigned  $event
     * @return void
     */
    public function handle(TransactionBatchSigned $event)
    {
        $transactionBatch = $event->transactionBatch;
        if (!$this->transactionBatchService->isEligbleForSubmission($transactionBatch)) {
            return false;
        }

        $this->transactionBatchService->submit($transactionBatch);
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\OrderShipped  $event
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(TransactionBatchSigned $event, $exception)
    {
        //
    }

}
