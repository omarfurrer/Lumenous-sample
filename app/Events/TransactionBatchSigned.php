<?php

namespace lumenous\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use lumenous\Models\TransactionBatch;

class TransactionBatchSigned {

    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    /**
     * @var TransactionBatch 
     */
    public $transactionBatch;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TransactionBatch $transactionBatch)
    {
        $this->transactionBatch = $transactionBatch;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

}
