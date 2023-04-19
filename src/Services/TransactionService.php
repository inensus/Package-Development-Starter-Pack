<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\MeterToken;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WavecomPaymentProvider\Providers\WaveComTransactionProvider;
use ParseCsv\Csv;
use Ramsey\Uuid\Uuid;

class TransactionService extends AbstractPaymentAggregatorTransactionService
{
    public function __construct(private Csv $csv)
    {
    }

    public function createTransactionsFromFile(UploadedFile $file): void
    {
        $this->csv->auto($file);

        foreach ($this->csv->data as $transactionData) {
            $transaction = new WaveComTransaction();
            $transaction->setTransactionId($transactionData['transaction_id']);
            $transaction->setSender($transactionData['sender']);
            $transaction->setMessage($transactionData['message']);
            $transaction->setAmount((int)$transactionData['amount']);
            $transaction->setStatus(0);
            $transaction->save();

            $baseTransaction = new Transaction();
            $baseTransaction->setAmount($transaction->getAmount());
            $baseTransaction->setSender($transaction->getSender());
            $baseTransaction->setMessage($transaction->getMessage());
            $baseTransaction->originalTransaction()->associate($transaction);
            $baseTransaction->setType(Transaction::TYPE_IMPORTED);
            $baseTransaction->save();

            $container = TransactionDataContainer::initialize($baseTransaction);


            ProcessPayment::dispatch($transaction->getId())
                // ->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));
        }
    }

    public function setStatus(WaveComTransaction $transaction, bool $status): void
    {
        $mappedStatus = $status ? WaveComTransaction::STATUS_SUCCESS : WaveComTransaction::STATUS_CANCELLED;
        $transaction->setStatus($mappedStatus);
        $transaction->save();
    }
}
