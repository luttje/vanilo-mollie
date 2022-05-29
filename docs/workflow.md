# Mollie Payment Workflow

The typical Vanilo Payments workflow with Mollie consists of the following steps:

1. Create an **Order** (or any ["Payable"](https://vanilo.io/docs/2.x/payments#payables))
2. Obtain the **payment method** from the checkout
3. Get the appropriate **gateway instance** associated with the payment method
4. Generate a **payment request** using the gateway
5. Inject the **HTML snippet** on the checkout/thankyou page
6. The HTML snippet will automatically redirect the browser to Mollie.
7. While the user is at Mollie a webhook will be called to inform this package of the payment
   status.
8. The return url you provide is where the user will be sent after payment.

## Obtain Gateway Instance

Once you have an order (or any other payable), then the starting point of payment operations is
obtaining a gateway instance:

```php
// @var Luttje\Mollie\MolliePaymentGateway
$gateway = \Vanilo\Payment\PaymentGateways::make('mollie');
```

The gateway provides you two essential methods:

- `createPaymentRequest` - Assembles the payment initiation request from an order (payable) that can
  be injected on your checkout page.
- `processPaymentResponse` - Processes the HTTP POST webhook from Mollie after a payment attempt.

## Starting Online Payments

**Controller:**

```php
use Vanilo\Foundation\Factories\OrderFactory;
use Vanilo\Payment\Factories\PaymentFactory;
use Vanilo\Payment\Models\PaymentMethod;
use Vanilo\Payment\PaymentGateways;
use Vanilo\Product\Models\Product;

class OrderController
{
    /**
     * Quickly buy a single item with the user selected payment method
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Vanilo\Product\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function submit(Request $request, Product $product)
    {
        $factory = app(OrderFactory::class);
        $order = $factory->createFromDataArray([], [
            [
                'product' => $product
            ]
        ]);

        $paymentMethod = PaymentMethod::find($request->get('paymentMethod'));
        $payment = PaymentFactory::createFromPayable($order, $paymentMethod);
        $gateway = PaymentGateways::make('mollie');
        $paymentRequest = $gateway->createPaymentRequest($payment);
        
        return view('order.confirmation', [
            'order' => $order,
            'paymentRequest' => $paymentRequest
        ]);
    }
}
```

**Blade Template:**

```blade
{!! $paymentRequest->getHtmlSnippet(); !!}
```

The generated HTML snippet will contain a prepared HTML Form that will automatically redirect the
user to Mollie.

### Payment Request Options

The gateway's `createPaymentRequest` method accepts additional parameters that can be used to
customize the generated request.

The signature of the method is the following:

```php
public function createPaymentRequest(
    Payment $payment,
    Address $shippingAddress = null,
    array $options = []
    ): PaymentRequest
```

1. The first parameter is the `$payment`. Every attempt to settle a payable is a new `Payment`
   record.
2. The second one is the `$shippingAddress` in case it differs from billing address. It can be left
   NULL.
3. The third parameters is an array with possible `$options`. Currently no options are implemented.

#### Customizing The Generated HTML

Laravel lets you [override the views from vendor
packages](https://laravel.com/docs/8.x/packages#overriding-package-views).

Simply put, if you create the `resources/views/vendor/mollie/_request.blade.php` file in your
application, then this blade view will be used instead of the one supplied by the package.

To get the default view from the package and start customizing it, use this command:

```bash
php artisan vendor:publish --tag=vanilo-mollie
```

This will copy the default blade view used to render the HTML form into the
`resources/views/vendor/mollie/` folder of your application. After that, the `getHtmlSnippet()`
method will use the copied blade template to render the HTML snippet for Mollie payment requests.

## Return and Webhook URLs

You should implement a controller to handle incoming Mollie requests and redirects. In the next
chapter you can find an example of such a `MollieReturnController`.

This is how you should register the routes to that controller:

```php
//web.php
Route::group(['prefix' => 'payment/mollie', 'as' => 'payment.mollie.'], function() {
    Route::get('return/{payment}', [MollieReturnController::class, 'return'])->name('return');
    Route::post('webhook', [MollieReturnController::class, 'webhook'])->name('webhook');
});
```

**IMPORTANT!**: Make sure to **disable CSRF verification** for these URLs, by adding them as
exceptions to `app/Http/Middleware/VerifyCsrfToken`:

```php
class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/payment/mollie/*'
    ];
}
```

---

**Next**: [Examples &raquo;](examples.md)
