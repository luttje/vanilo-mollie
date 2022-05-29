<?php

declare(strict_types=1);

namespace Luttje\Mollie;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Luttje\Mollie\Messages\MolliePaymentRequest;
use Luttje\Mollie\Messages\MolliePaymentResponse;
use Luttje\Mollie\Models\MolliePaymentStatus;
use Vanilo\Contracts\Address;
use Vanilo\Payment\Contracts\Payment;
use Vanilo\Payment\Contracts\PaymentGateway;
use Vanilo\Payment\Contracts\PaymentRequest;
use Vanilo\Payment\Contracts\PaymentResponse;

class MolliePaymentGateway implements PaymentGateway
{
    public const DEFAULT_ID = 'mollie';

    protected $apiKey;
    private $client;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function getMollieClient(): \Mollie\Api\MollieApiClient
    {
        if(!$this->client) {
            $this->client = new \Mollie\Api\MollieApiClient();
            $this->client->setApiKey($this->apiKey);
        }
        
        return $this->client;
    }

    public static function getName(): string
    {
        return 'Mollie';
    }

    public function createPaymentRequest(Payment $payment, Address $shippingAddress = null, array $options = []): PaymentRequest
    {
        $client = $this->getMollieClient();
        $data = [
            'amount' => [
                'currency' => $payment->getCurrency(),
                'value' => (string) $payment->getAmount()
            ],
            'description' => $payment->getPayable()->getTitle(),
            'redirectUrl' => route('payment.mollie.return', $payment),
            'webhookUrl'  => route('payment.mollie.webhook'),
            'metadata' => [
                'payment_id' => $payment->getPaymentId(),
            ],
        ];

        $molliePayment = $client->payments->create($data);

        return new MolliePaymentRequest($molliePayment->getCheckoutUrl());
    }

    public function processPaymentResponse(Request $request, array $options = []): PaymentResponse
    {
        $client = $this->getMollieClient();
        $molliePayment = $client->payments->get($request->id);

        return new MolliePaymentResponse(
            $molliePayment->metadata->payment_id,
            new MolliePaymentStatus($molliePayment->status),
            $molliePayment->description,
            $molliePayment->getAmountRefunded() > 0 ? -$molliePayment->getAmountRefunded() : $molliePayment->getSettlementAmount(),
            $molliePayment->settlementId
        );
    }

    public function isOffline(): bool
    {
        return false;
    }
}
