<?php

namespace lumenous\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use lumenous\User;
use lumenous\Models\TransactionBatch;

class TransactionBatchCreated extends Mailable {

    use Queueable,
        SerializesModels;

    /**
     * @var User 
     */
    public $user;

    /**
     * @var TransactionBatch 
     */
    public $transactionBatch;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(TransactionBatch $transactionBatch, User $user)
    {
        $this->transactionBatch = $transactionBatch;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Transaction batch needs signing")->markdown('emails.signTransactionBatch');
    }

}
