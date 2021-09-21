<?php

namespace spec\Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action\CaptureAction;
use GuzzleHttp\ClientInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;

class CaptureActionSpec extends ObjectBehavior
{
    function let(ClientInterface $client, LoggerInterface $logger)
    {
        $this->beConstructedWith($client, $logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(CaptureAction::class);
    }

    function it_implements_action_interface(): void
    {
        $this->shouldImplement(ActionInterface::class);
    }

    function it_implements_api_aware_interface(): void
    {
        $this->shouldImplement(ApiAwareInterface::class);
    }

    function it_implements_gateway_aware_interface(): void
    {
        $this->shouldImplement(GatewayAwareInterface::class);
    }
}
