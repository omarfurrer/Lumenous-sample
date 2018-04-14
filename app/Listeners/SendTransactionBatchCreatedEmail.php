<?php

namespace lumenous\Listeners;

use lumenous\Events\TransactionBatchCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use lumenous\Services\Stellar\TransactionBatch as TransactionBatchService;

class SendTransactionBatchCreatedEmail implements ShouldQueue {

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
    public function handle(TransactionBatchCreated $event)
    {
        $transactionBatch = $event->transactionBatch;
        $this->transactionBatchService->notifySignersForSigning($transactionBatch);
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\OrderShipped  $event
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(TransactionBatchCreated $event, $exception)
    {
        //
    }

}
