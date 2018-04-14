<?php

namespace lumenous\Repositories\Interfaces;

Use lumenous\Models\TransactionBatch;

interface TransactionBatchesRepositoryInterface {

    /**
     * Helper function to write JSON representation of transactions to disk.
     * 
     * @param string $name
     * @param array $contents
     * @param string $basePath
     * @return array
     * @throws Exception
     */
    public function saveFile($name, $contents, $basePath = 'transaction-batches/');

    /**
     * Update transaction batch transactions file.
     * 
     * @param string $filePath
     * @param string $contents
     * @return boolean
     * @throws Exception
     */
    public function updateFile($filePath, $contents);

    /**
     * Increment signers count.
     * 
     * @param TransactionBatch $transactionBatch
     * @return mixed
     */
    public function incrementSigners(TransactionBatch $transactionBatch);

    /**
     * Mark a transaction batch as submitted.
     * 
     * @param TransactionBatch $transactionBatch
     * @return mixed
     */
    public function markAsSubmitted(TransactionBatch $transactionBatch);

    /**
     * Encode transactions for file saving.
     * 
     * @param array $transactions
     * @return String
     * @throws Exception
     */
    public function encodeTransactions($transactions);
}
