Hello, {{ $user->email }}

A new transaction batch is available for you to sign.

@component('mail::button', ['url' => url('/dashboard/admin/transactions/batches/'.$transactionBatch->id.'/sign'), 'color' => 'green'])
Sign Transactions
@endcomponent