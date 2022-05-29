<?php

declare(strict_types=1);

namespace Luttje\Mollie\Messages;

use Konekt\Enum\Enum;
use Vanilo\Payment\Contracts\PaymentResponse;
use Vanilo\Payment\Contracts\PaymentStatus;
use Vanilo\Payment\Models\PaymentStatusProxy;
use Luttje\Mollie\Models\MolliePaymentStatus;


class MolliePaymentResponse implements PaymentResponse
{
    private string $paymentId;

    private ?float $amountPaid;

    private MolliePaymentStatus $nativeStatus;

    private ?PaymentStatus $status = null;

    private string $message;

    private ?string $transactionId;

    public function __construct(
        string $paymentId,
        MolliePaymentStatus $nativeStatus,
        string $message,
        ?float $amountPaid = null,
        ?string $transactionId = null
    ) {
        $this->paymentId = $paymentId;
        $this->nativeStatus = $nativeStatus;
        $this->amountPaid = $amountPaid;
        $this->message = $message;
        $this->transactionId = $transactionId;
    }

    public function wasSuccessful(): bool
    {
        return $this->nativeStatus === MolliePaymentStatus::STATUS_PAID;
    }

    public function getMessage(): string
    {
        // Just an example, feel free to implement a different logic
        return $this->message ?? $this->nativeStatus->label();
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getAmountPaid(): ?float
    {
        // Make sure to return a negative amount if the transaction
        // the response represents was a refund, partial refund cancellation or similar etc
        return $this->amountPaid;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getStatus(): PaymentStatus
    {
        if (null === $this->status) {
            switch ($this->getNativeStatus()->value()) {
                case MolliePaymentStatus::STATUS_OPEN:
                case MolliePaymentStatus::STATUS_PENDING:
                    $this->status = PaymentStatusProxy::PENDING();
                    break;
                case MolliePaymentStatus::STATUS_AUTHORIZED:
                    $this->status = PaymentStatusProxy::AUTHORIZED();
                    break;
                case MolliePaymentStatus::STATUS_EXPIRED:
                    $this->status = PaymentStatusProxy::TIMEOUT();
                    break;
                case MolliePaymentStatus::STATUS_PAID:
                    $this->status = PaymentStatusProxy::PAID();
                    break;
                default:
                    $this->status = PaymentStatusProxy::CANCELED();
            }
        }

        return $this->status;
    }

    public function getNativeStatus(): Enum
    {
        return $this->nativeStatus;
    }
}
