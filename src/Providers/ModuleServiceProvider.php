<?php

declare(strict_types=1);

namespace Luttje\Mollie\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Vanilo\Payment\PaymentGateways;
use Luttje\Mollie\MolliePaymentGateway;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function boot()
    {
        parent::boot();

        if ($this->config('gateway.register', true)) {
            PaymentGateways::register(
                $this->config('gateway.id', MolliePaymentGateway::DEFAULT_ID),
                MolliePaymentGateway::class
            );
        }

        if ($this->config('bind', true)) {
            $this->app->bind(MolliePaymentGateway::class, function ($app) {
                return new MolliePaymentGateway(
                    $this->config('apiKey')
                );
            });
        }

        $this->publishes([
            $this->getBasePath() . '/' . $this->concord->getConvention()->viewsFolder() =>
            resource_path('views/vendor/mollie'),
            'vanilo-mollie'
        ]);
    }
}
