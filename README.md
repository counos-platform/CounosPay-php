# counospay-php
PHP Counos Payment Gateway client
### Installing
Easily install it through Composer:
```
composer require counos/counospay-php
```
## Usage
To use this library, first you must have an Api Key. Go to https://payment.counos.io/account/terminals, then add your terminal and get Api Key.
#### Connect to counos payment
```
use Counos\CounosPay\Payment;

$counospay_api = new Payment("YOUR_COUNOS_PAYMENT_GATEWAY_API_KEY");
```
___
### Get all activated tickers of the terminal
```
try
{
    $tickers = $counospay_api->Tickers();
}
catch (Exception $e)
{
    die("Cant't connect to payment gateway. please try again.");
}
```
___
#### Create a new payment
```
try
{
    /**
     * $invoice_id must be unique per invoice, payment gateway uses this id to check the payment status, it can be a string (eg. inv-1, inv-vps-service-125)
     */
    $invoice_id    = 1;
    $ticker        =  'cca'; //$_POST['ticker'];
    $amount        = 9.99;
    $base_currency = 'USD';
    /**
     * @var $payment Counos\CounosPay\Models\Response\Order
     */
    $payment             = $counospay_api->NewOrderFromFiat($invoice_id, $base_currency, $ticker, $amount, true);
    $new_payment_created = [
        'invoice_id'      => $invoice_id,
        'payment_id'      => $payment->id,
        'confirmations'   => $payment->paymentConfirmations,
        'paid_amount'     => $payment->paidAmount,
        'expected_amount' => $payment->expectedAmount,
        'address'         => $payment->orderAddress,
        'ticker'          => $payment->ticker->keyword,
        'transaction_id'  => $payment->transactionId,
        'payment_uri'     => $payment->paymentUriQrCode,
        'paid'            => $payment->paid,
        'base_ticker'     => $base_currency,
        'base_amount'     => $amount,
    ];
    //In this step you must save this info (e.g. in db) then redirect user to a page that shows payment QR code and other info.
}
catch (Exception $e)
{
    die("Cant't connect to payment gateway. please try again.");
}
```
---
#### Check payment status
In order to periodically check the payment status, it must be declared in cron jobs
```
try
{
    try
    {
        $payment = $counospay_api->OrderStatus($invoice_id);
    }
    catch (Exception $e)
    {
        //log errors
    }

    $db_invoice_model = retrieveInvoiceFromDB($invoice_id);

    /**
     * When a payment has been completed and reaches the defined number of confirmations, 'paid' turns to true, in this stage you must change the invoice status to 'PAID' and do some stuff for order.
     */
    if ($payment->paid)
    {
        $db_invoice_model->status = 'paid';
        //notice the user about the payment status
        //place order
        //and etc.
    }
    else if ($payment->paidAmount >= $payment->expectedAmount)
    {
        $db_invoice_model->paid_status = 'await_confirmations';
        //show a progress bar to the user about how many confirmations...
    }
    else
    {
        //user not yet made the payment
    }
}
catch (Exception $e)
{
    die("Cant't connect to payment gateway. please try again.");
}
function retrieveInvoiceFromDB($invoice_id)
{
    //get invoice model from db and return
}
```
