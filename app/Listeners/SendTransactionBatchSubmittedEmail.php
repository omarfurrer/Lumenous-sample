<?php

namespace lumenous\Listeners;

use lumenous\Events\TransactionBatchSubmitted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use lumenous\Services\Stellar\TransactionBatch as TransactionBatchService;

class SendTransactionBatchSubmittedEmail implements ShouldQueue {

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
     * @param  TransactionBatchSubmitted  $event
     * @return void
     */
    public function handle(TransactionBatchSubmitted $event)
    {
        $transactionBatch = $event->transactionBatch;
        $this->transactionBatchService->notifySignersForSubmission($transactionBatch);
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\TransactionBatchSubmitted  $event
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(TransactionBatchSubmitted $event, $exception)
    {
        //
    }

}
