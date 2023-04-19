<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Inensus\WavecomPaymentProvider\Http\Requests\UploadTransactionRequest;
use Inensus\WavecomPaymentProvider\Services\TransactionService;

class WaveComTransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function uploadTransaction(UploadTransactionRequest $request)
    {
        $file = $request->getFile();
        $this->transactionService->createTransactionsFromFile($file);
    }
}
