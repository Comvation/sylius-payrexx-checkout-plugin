<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin;

use Comvation\SyliusPayrexxCheckoutPlugin\Action\CaptureAction;
use Comvation\SyliusPayrexxCheckoutPlugin\Action\StatusAction;
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
            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
        ]);
        $config['payum.required_options'] = ['instance', 'api_key', 'domain'];
        $config['payum.api'] = function (ArrayObject $config) {
            $config->validateNotEmpty($config['payum.required_options']);
            return new PayrexxApi(
                [
                    'instance' => $config['instance'],
                    'api_key' => $config['api_key'],
                    'domain' => $config['domain'],
                ],
                $config['payum.http_client'],
                $config['httplug.message_factory']
            );
        };
    }
}
