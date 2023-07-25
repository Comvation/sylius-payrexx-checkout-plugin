<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Payum\Gateway;

use Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action\StatusAction;
use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxApi;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class PayrexxGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'Payrexx',
            'payum.factory_title' => 'Payrexx Payment',
            'payum.action.status' => new StatusAction(),
        ]);
        $config['payum.required_options'] = ['instance', 'api_key', 'domain'];
        $config['payum.api'] = function (ArrayObject $config) {
            $config->validateNotEmpty($config['payum.required_options']);
            $apiConfig = ArrayObject::ensureArrayObject([
                'instance' => $config['instance'],
                'api_key' => $config['api_key'],
                'domain' => $config['domain'],
            ]);
            return new PayrexxApi(
                $apiConfig,
                $config['payum.http_client'],
                $config['httplug.message_factory']
            );
        };
    }
}
