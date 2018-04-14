<?php

namespace lumenous\Repositories;

use lumenous\Models\TransactionBatch;
use lumenous\Repositories\Interfaces\TransactionBatchesRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Exception;

class EloquentTransactionBatchesRepository extends EloquentAbstractRepository implements TransactionBatchesRepositoryInterface {

    public function __construct()
    {
        $this->modelClass = 'lumenous\Models\TransactionBatch';
    }

    /**
     * Create a new transactions batch and save JSON file to disk. 
     *
     * @param array $fields
     * @return mixed
     */
    public function create(array $fields = null)
    {
        if (empty($fields['transactions'])) {
            throw new Exception("transactions array cannot be empty");
        }

        $timestamp = time();
        $fileName = "transactions-{$timestamp}.json";

        $fileContents = $this->encodeTransactions($fields['transactions']);

        $file = $this->saveFile($fileName, $fileContents);

        if (!$file['success']) {
            throw new Exception("Could not store file to disk");
        }

        $fields['file_name'] = $fileName;
        $fields['file_path'] = $file['file_path'];
        $fields['timestamp'] = $timestamp;

        return parent::create($fields);
    }

    /**
     * Update Transaction batch.
     *
     * @param Integer $id
     * @param array $fields
     * @return mixed
     */
    public function update($id, array $fields = array())
    {
        $transactionBatch = $this->getById($id);
        if (empty($transactionBatch)) {
            throw new Exception("Transaction batch not found.");
        }

        if (!empty($fields['transactions'])) {
            $fileUpdated = $this->updateFile($transactionBatch->file_path, $this->encodeTransactions($fields['transactions']));
            if (!$fileUpdated) {
                throw new Exception('File could not be updated.');
            }
        }

        return parent::update($id, $fields);
    }

    /**
     * Helper function to write JSON representation of transactions to disk.
     * 
     * @param string $name
     * @param array $contents
     * @param string $basePath
     * @return array
     * @throws Exception
     */
    public function saveFile($name, $contents, $basePath = 'transaction-batches/')
    {
        if (empty($name) || empty($contents)) {
            throw new Exception("Unable to save file. Argument missing.");
        }

        $path = $basePath . $name;

        $saved = Storage::put($path, $contents);

        return [
            'success' => $saved,
            'file_path' => $saved ? $path : null
        ];
    }

    /**
     * Update transaction batch transactions file.
     * 
     * @param string $filePath
     * @param string $contents
     * @return boolean
     * @throws Exception
     */
    public function updateFile($filePath, $contents)
    {
        if (empty($filePath) || empty($contents)) {
            throw new Exception('Unable to update file. Argument missing.');
        }
        return Storage::put($filePath, $contents);
    }

    /**
     * Increment signers count.
     * 
     * @param TransactionBatch $transactionBatch
     * @return mixed
     */
    public function incrementSigners(TransactionBatch $transactionBatch)
    {
        return $this->update($transactionBatch->id, [
                    'signer_count' => $transactionBatch->signer_count + 1
        ]);
    }

    /**
     * Mark a transaction batch as submitted.
     * 
     * @param TransactionBatch $transactionBatch
     * @return mixed
     */
    public function markAsSubmitted(TransactionBatch $transactionBatch)
    {
        return $this->update($transactionBatch->id, [
                    'submitted' => true
        ]);
    }

    /**
     * Encode transactions for file saving.
     * 
     * @param array $transactions
     * @return String
     * @throws Exception
     */
    public function encodeTransactions($transactions)
    {
        if (empty($transactions)) {
            throw new Exception("Transactions cannot be empty.");
        }
        return json_encode($transactions, JSON_UNESCAPED_SLASHES);
    }

}
