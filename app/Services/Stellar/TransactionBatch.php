<?php

namespace lumenous\Services\Stellar;

use lumenous\Repositories\Interfaces\TransactionBatchesRepositoryInterface;
use lumenous\Models\TransactionBatch as TransactionBatchModel;
use Exception;
use Illuminate\Support\Facades\Mail;
use lumenous\Mail\TransactionBatchCreated;
use lumenous\Repositories\Interfaces\UsersRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use lumenous\Services\Stellar\Transaction as TransactionService;
use lumenous\User;
use lumenous\Services\StellarService;
use lumenous\Events\TransactionBatchSigned;
use lumenous\Events\TransactionBatchCreated as TransactionBatchCreatedEvent;
use lumenous\Events\TransactionBatchSubmitted as TransactionBatchSubmittedEvent;
use lumenous\Mail\TransactionBatchSubmitted;

class TransactionBatch {

    /**
     * @var TransactionService 
     */
    protected $transactionService;

    /**
     * @var TransactionBatchesRepositoryInterface 
     */
    protected $transactionBatchesRepository;

    /**
     * @var UsersRepositoryInterface 
     */
    protected $usersRepository;

    /**
     * @var StellarService 
     */
    protected $stellarService;

    /**
     * TransactionBatch Constructor.
     * 
     * @param TransactionBatchesRepositoryInterface $transactionBatchesRepository
     * @param TransactionService $transactionService
     * @param UsersRepositoryInterface $usersRepository
     * @param StellarService $stellarService
     */
    function __construct(TransactionBatchesRepositoryInterface $transactionBatchesRepository, TransactionService $transactionService
    , UsersRepositoryInterface $usersRepository, StellarService $stellarService)
    {
        $this->transactionBatchesRepository = $transactionBatchesRepository;
        $this->transactionService = $transactionService;
        $this->usersRepository = $usersRepository;
        $this->stellarService = $stellarService;
    }

    /**
     * Using array of payouts create the transaction Batch file and record.
     * 
     * @param array $payouts
     * @param array $charityPayouts
     * @param string $sourcePublicKey
     * @return TransactionBatchModel
     * @throws Exception
     */
    public function create($payouts, $charityPayouts, $sourcePublicKey)
    {
        if (empty($payouts) && empty($charityPayouts)) {
            throw new Exception("Payouts array is empty");
        }

        if (empty($sourcePublicKey)) {
            throw new Exception("Source public key cannot be null");
        }

        $accountTransactions = $this->transactionService->createManyFromPayout($payouts, $sourcePublicKey);
        $charityTransactions = $this->transactionService->createManyFromCharityPayout($charityPayouts, $sourcePublicKey, $accountTransactions->isEmpty() ? null : $accountTransactions->last());

        $transactions = array_merge($accountTransactions->toArray(), $charityTransactions->toArray());

        $transactionBatch = $this->transactionBatchesRepository->create(compact('transactions'));

        event(new TransactionBatchCreatedEvent($transactionBatch));

        return $transactionBatch;
    }

    /**
     * Create transaction batch using app key as source key.
     * 
     * @param array $payouts
     * @param array $charityPayouts
     * @return TransactionBatchModel
     */
    public function createWithAppKey($payouts, $charityPayouts)
    {
        return $this->create($payouts, $charityPayouts, $this->stellarService->getStellarClient()->getAppPublicKey());
    }

    /**
     * Send transaction batch has been submitted email notification to all eligible signers. 
     * 
     * @param TransactionBatchModel $transactionBatch
     */
    public function notifySignersForSubmission(TransactionBatchModel $transactionBatch)
    {
        $this->notifySigners($transactionBatch, TransactionBatchSubmitted::class);
    }

    /**
     * Send transaction batch needs signing email notification to all eligible signers. 
     * 
     * @param TransactionBatchModel $transactionBatch
     */
    public function notifySignersForSigning(TransactionBatchModel $transactionBatch)
    {
        $this->notifySigners($transactionBatch, TransactionBatchCreated::class);
    }

    /**
     * Notify users eligible to sign with a specific email.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @param String $email
     */
    public function notifySigners(TransactionBatchModel $transactionBatch, $email)
    {
        $signers = $this->usersRepository->getSigners();

        // send email notifications
        $this->sendEmailNotifications($transactionBatch, $signers, $email);
    }

    /**
     * Using an array of users and a Mailable class send email notification.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @param array $users
     * @param String $email
     */
    public function sendEmailNotifications(TransactionBatchModel $transactionBatch, $users, $email)
    {
        foreach ($users as $user) {
            $this->sendEmailNotification($transactionBatch, $user, $email);
        }
    }

    /**
     * Send an email notifying a user with a specific email.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @param User $user
     * @param String $email
     * @throws Exception
     */
    public function sendEmailNotification(TransactionBatchModel $transactionBatch, $user, $email)
    {
        if (empty($transactionBatch) || empty($user)) {
            throw new Exception('Cannot send email. Argument missing.');
        }

        if (empty($user->email)) {
            throw new Exception('Cannot send email. User record missing email.');
        }

        Mail::to($user)->send(app()->make($email, [$transactionBatch, $user]));
    }

    /**
     * Get a transaction batch details and its associated transactions. 
     * 
     * @param Integer $id
     * @param boolean $withTransactions
     * @return TransactionBatchModel
     * @throws Exception
     */
    public function get($id, $withTransactions = true)
    {
        $transactionBatch = $this->transactionBatchesRepository->getById($id);
        if (!$transactionBatch) {
            throw new Exception("Transaction Batch with {$id} not found");
        }

        if ($withTransactions) {
            $file = Storage::get($transactionBatch->file_path);
            if (!$file) {
                throw new Exception("Could not find file with path {$transactionBatch->file_path}");
            }

            $transactionBatch['transactions'] = $this->transactionService->manyFromArray(json_decode($file));
        }

        return $transactionBatch;
    }

    /**
     * Sign a transaction batch.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @param User $user
     * @param String $secretKey
     * @return TransactionModel
     */
    public function sign(TransactionBatchModel $transactionBatch, User $user, $secretKey)
    {
        $transactionBatch = $this->get($transactionBatch->id);

        $transactions = $transactionBatch->transactions;

        foreach ($transactions as $key => $transaction) {
            $transactions[$key] = $this->transactionService->sign($transaction, $user, $secretKey);
        }

        $this->transactionBatchesRepository->update($transactionBatch->id, compact('transactions'));

        $this->transactionBatchesRepository->incrementSigners($transactionBatch);

        event(new TransactionBatchSigned($transactionBatch));

        return $this->get($transactionBatch->id);
    }

    /**
     * Check if transaction has minimum signers amount.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @return Integer
     */
    public function isEligbleForSubmission(TransactionBatchModel $transactionBatch)
    {
        $signersCount = $transactionBatch->signer_count;
        $threshold = $this->stellarService->getAppThreshold(StellarService::MEDIUM_THRESHOLD);

        return $signersCount >= $threshold;
    }

    /**
     * Loop through all transactions in a batch and submit them.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @return TransactionBatchModel
     */
    public function submit(TransactionBatchModel $transactionBatch)
    {
        $transactionBatch = $this->get($transactionBatch->id);

        $transactions = $transactionBatch->transactions;

        foreach ($transactions as $key => $transaction) {
            $transactions[$key] = $this->transactionService->submit($transaction);
        }

        $this->transactionBatchesRepository->update($transactionBatch->id, compact('transactions'));

        $this->transactionBatchesRepository->markAsSubmitted($transactionBatch);

        event(new TransactionBatchSubmittedEvent($transactionBatch));

        return $this->get($transactionBatch->id);
    }

    /**
     * Check if transaction batch is signed by user.
     * 
     * @param TransactionBatchModel $transactionBatch
     * @param User $user
     * @return Boolean
     */
    public function isSignedByUser(TransactionBatchModel $transactionBatch, User $user)
    {
        $transactionBatch = $this->get($transactionBatch->id);
        return $this->transactionService->isAllSignedByUser($transactionBatch->transactions, $user) && $transactionBatch->signer_count > 0;
    }

}
