<?php

declare(strict_types=1);

namespace Luttje\Mollie\Messages;

use Illuminate\Support\Facades\View;
use Vanilo\Payment\Contracts\PaymentRequest;

class MolliePaymentRequest implements PaymentRequest
{
    private string $view = 'mollie::_request';
    private string $checkoutUrl;

    public function __construct($checkoutUrl)
    {
        $this->checkoutUrl = $checkoutUrl;
    }

    public function getHtmlSnippet(array $options = []): ?string
    {
        return View::make(
            $this->view,
            [
                'checkoutUrl' => $this->checkoutUrl,
                'autoRedirect' => $this->willRedirect()
            ]
        )->render();
    }

    public function willRedirect(): bool
    {
        return true;
    }

    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }
}
