# Examples

The example below shows parts of the code that you can put in your application.

### CheckoutController

The controller below processes a submitted checkout, prepares the payment and returns the thank you
page with the prepared payment request:

```php
use Illuminate\Http\Request;
use Vanilo\Framework\Models\Order;
use Vanilo\Payment\Factories\PaymentFactory;
use Vanilo\Payment\Models\PaymentMethod;
use Vanilo\Payment\PaymentGateways;

class CheckoutController
{
    public function store(Request $request)
    {
        $order = Order::createFrom($request);
        $paymentMethod = PaymentMethod::find($request->get('paymentMethod'));
        $payment = PaymentFactory::createFromPayable($order, $paymentMethod);
        $gateway = PaymentGateways::make('mollie');
        $paymentRequest = $gateway->createPaymentRequest($payment);
        
        return view('checkout.thank-you', [
            'order' => $order,
            'paymentRequest' => $paymentRequest
        ]);
    }
}
```

### checkout/thank-you.blade.php

This sample blade template contains a thank you page where you can render the payment
initiation/redirection form:

**Blade Template:**

```blade
@extends('layouts.app')
@section('content')
    <div class="container">
        <h1>Thank you</h1>

        <div class="alert alert-success">Your order has been registered with number
            <strong>{{ $order->getNumber() }}</strong>.
        </div>

        <h3>Payment</h3>

        {!! $paymentRequest->getHtmlSnippet(); !!}
    </div>
@endsection
```
*(The default redirection javascript will trigger immediately on page load, so don't spend much time
on this page)*


### MollieReturnController

This Controller forwards incoming webhooks from Mollie to this package. Additionally it shows the
return page to which the user will be redirected from Mollie after payment:

```php
namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vanilo\Payment\Models\Payment;
use Vanilo\Payment\PaymentGateways;
use Vanilo\Payment\Processing\PaymentResponseHandler;


class MollieReturnController extends Controller
{
    public function return(Request $request, Payment $payment)
    {
        // The webhook will let us know if the payment was successful before the user is returned here. 
        // If the webhook occurred on successful payment then $payment->status will be paid here.
        // dd($payment);

        // The payment can have the `paid` status, but the order will still be pending. Someone will still have to process that.
        return view('order.being-processed');
    }
    
    public function webhook(Request $request)
    {
        Log::debug('Mollie webhook', [
            'req' => $request->toArray(),
            'method' => $request->method(),
        ]);
        
        $this->processPaymentResponse($request);

        return new JsonResponse(['message' => 'Received OK']);
    }

    private function processPaymentResponse(Request $request): Payment
    {
        // Forward the incoming webhook request to this package.
        // You don't have to do much on that front, but should you be interested check out the Mollie php client @ https://github.com/mollie/mollie-api-php/blob/5906cf9ff3133a4f47fea47624f3839ac07d0805/examples/payments/webhook.php
        $response = PaymentGateways::make('mollie')->processPaymentResponse($request);

        $payment  = Payment::findByPaymentId($response->getPaymentId());

        if (!$payment) {
            throw new ModelNotFoundException('Could not locate payment with id ' . $response->getPaymentId());
        }

        $handler = new PaymentResponseHandler($payment, $response);
        $handler->writeResponseToHistory();
        $handler->updatePayment();
        $handler->fireEvents();

        return $payment;
    }
}
```

Have fun!

---
Congrats, you've reached the end of this doc! 🎉
