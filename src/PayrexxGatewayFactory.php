<?php

declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin;

use Comvation\SyliusPayrexxCheckoutPlugin\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

/**
 * Payrexx payment gateway factory
 */
final class PayrexxGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'Payrexx',
            'payum.factory_title' => 'Payrexx Payment',
            'payum.action.status' => new StatusAction(),
        ]);
        $config['payum.api'] = function (ArrayObject $config) {
            return new PayrexxApi(
                $config['instance'],
                $config['api_key'],
                $config['domain']
            );
        };
    }
}
