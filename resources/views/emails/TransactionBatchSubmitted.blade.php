Hello, {{ $user->email }}

A new transaction batch has been submitted.

@component('mail::button', ['url' => url('/dashboard/admin/transactions/batches'), 'color' => 'green'])
View Transactions
@endcomponent