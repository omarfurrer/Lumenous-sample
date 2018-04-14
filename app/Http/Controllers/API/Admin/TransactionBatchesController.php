<?php

namespace lumenous\Http\Controllers\API\Admin;

use Illuminate\Http\Request;
use lumenous\Repositories\Interfaces\TransactionBatchesRepositoryInterface;
use lumenous\Models\TransactionBatch;
use lumenous\Http\Requests\TransactionBatches\ShowTransactionBatchRequest;
use lumenous\Http\Controllers\Controller;
use lumenous\Services\Stellar\TransactionBatch as TransactionBatchService;
use lumenous\Http\Requests\TransactionBatches\SignTransactionBatchRequest;
use lumenous\Http\Requests\TransactionBatches\IndexTransactionBatchesRequest;
use lumenous\User;

class TransactionBatchesController extends Controller {

    /**
     * @var TransactionBatchesRepositoryInterface 
     */
    protected $transactionBatchesRepository;

    /**
     * @var TransactionBatchService 
     */
    protected $transactionBatchService;

    /**
     * Create a new command instance.
     *
     * @param TransactionBatchesRepositoryInterface $transactionBatchesRepository 
     * @param TransactionBatchService $transactionBatchService 
     * @return void
     */
    public function __construct(TransactionBatchesRepositoryInterface $transactionBatchesRepository, TransactionBatchService $transactionBatchService)
    {
        parent::__construct();
        $this->transactionBatchesRepository = $transactionBatchesRepository;
        $this->transactionBatchService = $transactionBatchService;
    }

    /**
     * Retrieve all transaction batches.
     * 
     * @param IndexTransactionBatchesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexTransactionBatchesRequest $request)
    {
        $transactionBatches = $this->transactionBatchesRepository->all();
        return response()->json(compact('transactionBatches'));
    }

    /**
     * Get specific transaction batch.
     * 
     * @param GetUserPayoutsRequest $request
     * @param TransactionBatch $transactionbatch
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ShowTransactionBatchRequest $request, TransactionBatch $transactionbatch)
    {
        $transactionBatch = $this->transactionBatchService->get($transactionbatch->id);
        return response()->json(compact('transactionBatch'));
    }

    /**
     * Sign a transaction batch.
     * 
     * @param SignTransactionBatchRequest $request
     * @param TransactionBatch $transactionbatch
     * @return \Illuminate\Http\JsonResponse
     */
    public function sign(SignTransactionBatchRequest $request, TransactionBatch $transactionbatch)
    {
        set_time_limit(300);
        $transactionBatch = $this->transactionBatchService->sign($transactionbatch, $this->authUser, $request->private_key);
        return response()->json(compact('transactionBatch'));
    }

    /**
     * Check if user has signed transaction batch.
     * 
     * @param TransactionBatch $transactionbatch
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function isSignedByUser(TransactionBatch $transactionbatch, User $user)
    {
        $signed = $this->transactionBatchService->isSignedByUser($transactionbatch, $user);
        return response()->json(compact('signed'));
    }

}
