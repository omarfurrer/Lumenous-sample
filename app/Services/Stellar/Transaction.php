<?php

namespace lumenous\Services\Stellar;

use Illuminate\Support\Facades\Log;
use lumenous\Services\Stellar\PayoutTransactionBuilder;
use lumenous\Models\Payout;
use ZuluCrypto\StellarSdk\Transaction\TransactionBuilder;
use ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use lumenous\Models\Transaction as TransactionModel;
use lumenous\User;
use lumenous\Repositories\Interfaces\SignersRepositoryInterface;
use lumenous\Services\Stellar\Signer as SignerService;
use ZuluCrypto\StellarSdk\Server;
use ZuluCrypto\StellarSdk\Horizon\Exception\PostTransactionException;
use Carbon\Carbon;
use lumenous\Services\Stellar\client;
use lumenous\Models\CharityPayout;
use lumenous\Services\Stellar\CharityPayoutTransactionBuilder;
use Exception;
use phpseclib\Math\BigInteger;

class Transaction {

    /**
     * @var PayoutTransactionBuilder 
     */
    protected $payoutTransactionBuilderService;

    /**
     * @var CharityPayoutTransactionBuilder 
     */
    protected $charityPayoutTransactionBuilderService;

    /**
     * @var  SignersRepositoryInterface
     */
    protected $signersRepository;

    /**
     * @var SignerService
     */
    protected $signerService;

    /**
     * @var client 
     */
    protected $stellarClient;

    /**
     * Transaction Service Constructor.
     * 
     * @param PayoutTransactionBuilder $payoutTransactionBuilderService
     * @param CharityPayoutTransactionBuilder $charityPayoutTransactionBuilderService
     * @param SignersRepositoryInterface $signersRepository
     * @param SignerService $signerService
     * @param client $stellarClient
     */
    function __construct(PayoutTransactionBuilder $payoutTransactionBuilderService, CharityPayoutTransactionBuilder $charityPayoutTransactionBuilderService,
            SignersRepositoryInterface $signersRepository, SignerService $signerService, client $stellarClient)
    {
        $this->payoutTransactionBuilderService = $payoutTransactionBuilderService;
        $this->charityPayoutTransactionBuilderService = $charityPayoutTransactionBuilderService;
        $this->signersRepository = $signersRepository;
        $this->signerService = $signerService;
        $this->stellarClient = $stellarClient;
    }

    /**
     * Using an array of payouts create transactions.
     * 
     * @param array $payouts
     * @param string $sourcePublicKey
     * @param mixed $lastTransaction Insert last transaction to derive sequence number from it. 
     * @return \Illuminate\Support\Collection
     */
    public function createManyFromPayout($payouts, $sourcePublicKey, $lastTransaction = null)
    {
        $transactions = [];
        $sequenceNumber = empty($lastTransaction) ? $lastTransaction : $this->incrementTransactionSequenceNumber($lastTransaction);

        foreach ($payouts as $index => $payout) {
            if ($index != 0) {
                $sequenceNumber = $this->incrementTransactionSequenceNumber($transactions[$index - 1]);
            }
            $transactions[] = $this->createFromPayout($payout, $sourcePublicKey, $sequenceNumber);
        }
        return collect($transactions);
    }

    /**
     * Using a payout create a transaction.
     * 
     * @param Payout $payout
     * @param string $sourcePublicKey
     * @param string $sequenceNumber
     * @return TransactionModel
     */
    public function createFromPayout(Payout $payout, $sourcePublicKey, $sequenceNumber)
    {
        $xdr = $this->payoutTransactionBuilderService->buildUnsignedFromPayout($payout, $sourcePublicKey, $sequenceNumber);
        return $this->fromArray([
                    'tx_xdr' => $xdr,
                    'tx_hash' => $this->getHashFromXdr($xdr),
                    'src_account' => $sourcePublicKey,
                    'submitted' => false,
                    'submitted_at' => null,
                    'is_account_payout' => true,
                    'payout_id' => $payout->id,
                    'is_charity_payout' => false,
                    'charity_payout_id' => null
        ]);
    }

    /**
     * Using an array of charity payouts create transactions.
     * 
     * @param array $charityPayouts
     * @param string $sourcePublicKey
     * @param mixed $lastTransaction Insert last transaction to derive sequence number from it.  
     * @return \Illuminate\Support\Collection
     */
    public function createManyFromCharityPayout($charityPayouts, $sourcePublicKey, $lastTransaction = null)
    {
        $transactions = [];
        $sequenceNumber = empty($lastTransaction) ? $lastTransaction : $this->incrementTransactionSequenceNumber($lastTransaction);

        foreach ($charityPayouts as $index => $charityPayout) {
            if ($index != 0) {
                $sequenceNumber = $this->incrementTransactionSequenceNumber($transactions[$index - 1]);
            }
            $transactions[] = $this->createFromCharityPayout($charityPayout, $sourcePublicKey, $sequenceNumber);
        }
        return collect($transactions);
    }

    /**
     * Using a charity payout create a transaction.
     * 
     * @param CharityPayout $charityPayout
     * @param string $sourcePublicKey
     * @param string $sequenceNumber
     * @return TransactionModel
     */
    public function createFromCharityPayout(CharityPayout $charityPayout, $sourcePublicKey, $sequenceNumber)
    {
        $xdr = $this->charityPayoutTransactionBuilderService->buildUnsignedFromCharityPayout($charityPayout, $sourcePublicKey, $sequenceNumber);
        return $this->fromArray([
                    'tx_xdr' => $xdr,
                    'tx_hash' => $this->getHashFromXdr($xdr),
                    'src_account' => $sourcePublicKey,
                    'submitted' => false,
                    'submitted_at' => null,
                    'is_account_payout' => false,
                    'payout_id' => null,
                    'is_charity_payout' => true,
                    'charity_payout_id' => $charityPayout->id
        ]);
    }

    /**
     * Convert multiple model raw data to a collection of Transaction Models.
     * 
     * @param array $data
     * @return type
     */
    public function manyFromArray($data)
    {
        $transactions = [];
        foreach ($data as $modelData) {
            $transactions[] = $this->fromArray($modelData);
        }
        return collect($transactions);
    }

    /**
     * Using an array of raw data convert to Transaction Model.
     * 
     * @param array $data
     * @return TransactionModel
     */
    public function fromArray($data)
    {
        return new TransactionModel((array) $data);
    }

    /**
     * Get hash from transaction.
     * 
     * @param string $xdr
     * @return string
     */
    public function getHashFromXdr($xdr)
    {
        $transaction = TransactionBuilder::fromXdr(new XdrBuffer(base64_decode($xdr)), $this->getServer());
        return $transaction->getHashAsString();
    }

    /**
     * Sign a transaction.
     * 
     * @param TransactionModel $transaction
     * @param User $user
     * @param String $secretKey
     * @return Transaction
     * @throws Exception
     */
    public function sign(TransactionModel $transaction, User $user, $secretKey)
    {
        if ($this->isSignedByUser($transaction, $user)) {
            throw new Exception("Transaction already signed");
        }

        return $this->signerService->sign($transaction, $user, $secretKey);
    }

    /**
     * Submit a transaction.
     * 
     * @param TransactionModel $transaction
     * @return boolean|TransactionModel
     */
    public function submit(TransactionModel $transaction)
    {
        $transactionEnvelope = $this->signerService->getTransactionEnvelope($transaction);
//        dd($transactionEnvelope->toBase64());
        try {
            $this->getServer()->submitB64Transaction($transactionEnvelope->toBase64());
        } catch (PostTransactionException $exc) {
            Log::critical("Unable to submit transaction", [
                'message' => $exc->getMessage(),
                'transaction' => $transaction->toJson()
            ]);
            return false;
        }

        $transaction->submitted = true;
        $transaction->submitted_at = Carbon::now();

        return $transaction;
    }

    /**
     * Check if all transactions in array are signed by user.
     * 
     * @param array $transactions
     * @param User $user
     * @return Boolean
     */
    public function isAllSignedByUser($transactions, User $user)
    {
        foreach ($transactions as $transaction) {
            if (!$this->isSignedByUser($transaction, $user)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if transaction is signed by a user.
     * 
     * @param TransactionModel $transaction
     * @param User $user
     * @return Boolean
     */
    public function isSignedByUser(TransactionModel $transaction, User $user)
    {
        return $this->signersRepository->isSignedByUser($transaction, $user);
    }

    /**
     * Return Horizon stellar server instance.
     * 
     * @return Server
     */
    public function getServer()
    {
        return $this->stellarClient->getServer();
    }

    /**
     * Return sequence number from transaction XDR.
     * 
     * @param TransactionModel $transaction
     * @return BigInteger
     */
    public function getTransactionSequenceNumber(TransactionModel $transaction)
    {
        return TransactionBuilder::fromXdr(new XdrBuffer(base64_decode($transaction->tx_xdr)), $this->getServer())->getSequenceNumber();
    }

    /**
     * Increment a transaction's sequence number.
     * 
     * @param TransactionModel $transaction
     * @return BigInteger
     */
    public function incrementTransactionSequenceNumber(TransactionModel $transaction)
    {
        return $this->getTransactionSequenceNumber($transaction)->add(new BigInteger(1));
    }

    /**
     * Add signers to a transaction.
     *
     * @param   \lumenous\Models\Transaction $transaction
     * @param   array   $signers
     * @return  mixed
     */
//    public function addSigners(\lumenous\Models\Transaction $transaction, $signers = [])
//    {
//        $models = [];
//
//        foreach ($signers as $signer) {
//            // TODO: handle performing the actual signing
//            // mark signer as added in the database
//            $models[] = $this->signersRepository->addSigner($transaction, $signer);
//        }
//
//        return $models;
//    }

    /**
     * Sign a transaction.
     *
     * @param   TransactionModel $transaction
     * @param   User $user
     * @param   string $secretKey
     */
//    public function sign(TransactionModel $transaction, User $user, $secretKey)
//    {
//        // locate our transaction
//        $transaction = $this->getById($request->get('transaction_id'));
//        if (empty($transaction)) {
//            Log::critical('Could not find a transaction by id to sign.',
//                          [
//                'transaction_id' => $request->get('transaction_id'),
//                'user_id' => !empty($user) ? $user->id : null,
//            ]);
//
//            return false;
//        }
//
//        // find our signer
//        $signer = $this->signersRepository->locate($user, $transaction);
//        if (empty($signer)) {
//            Log::critical('Could not find a matching signer.', [
//                'transaction_id' => $transaction->id,
//                'user_id' => !empty($user) ? $user->id : null
//            ]);
//
//            return false;
//        }
//
//        // mark as signed if we haven't already done so
//        if (!$signer->signed) {
//            $this->signersRepository->sign($signer);
//        }
//
//        // TODO: does this change after each signer?
//        // update the transaction XDR
//        $this->transactionRepository->update([
//            'tx_xdr' => $request->get('tx_xdr')
//        ]);
//
//        // check if signing has been completed
//        if ($this->hasCompletedSigning($transaction)) {
//            return $this->transactionRepository->markAsSigned($transaction);
//        }
//
//        return $transaction;
//    }

    /**
     * Find transaction by tag.
     *
     * @param   string  $tag
     * @return  mixed
     */
//    public function getByTag($tag)
//    {
//        return $this->transactionRepository->getByTag($tag);
//    }

    /**
     * Given a transaction tag, return the signers.
     *
     * @param   string  $tag
     * @return  bool
     */
//    public function getSigners($tag)
//    {
//        $transaction = $this->getByTag($tag);
//        if (empty($transaction)) {
//            Log::critical('Could not find transaction by tag when attempting to retrieve signers.', [
//                'tag' => $tag
//            ]);
//            return false;
//        }
//
//        return $this->getSignersByTransaction($transaction);
//    }

    /**
     * Get signers by transaction.
     *
     * @param \lumenous\Models\Transaction $transaction
     * @return mixed
     */
//    public function getSignersByTransaction(\lumenous\Models\Transaction $transaction)
//    {
//        return $this->signersRepository->getByTransaction($transaction);
//    }

    /**
     * Handle marking a signed transaction as submitted.
     *
     * @param   string  $tag
     * @return  mixed
     */
//    public function submit($tag)
//    {
//        $transaction = $this->getByTag($tag);
//        if (empty($transaction)) {
//            Log::critical('Could not find transaction by tag when attempting to submit transaction.', [
//                'tag' => $tag
//            ]);
//
//            return false;
//        }
//
//        if (!$transaction->signed) {
//            Log::critical('Transaction cannot be submitted until it has been fully signed.', [
//                'transaction_id' => $transaction->id
//            ]);
//
//            return false;
//        }
//
//        return $this->transactionRepository->markAsSubmitted($transaction);
//    }

    /**
     * Check if we've completed signing a transaction.
     *
     * @param \lumenous\Models\Transaction $transaction
     * @return bool
     */
//    public function hasCompletedSigning(\lumenous\Models\Transaction $transaction)
//    {
//        $signers = $this->getSignersByTransaction($transaction);
//        if ($signers->isEmpty()) {
//            return false;
//        }
//
//        foreach ($signers as $signer) {
//            if (!$signer->signed) {
//                return false;
//            }
//        }
//
//        return true;
//    }
}
