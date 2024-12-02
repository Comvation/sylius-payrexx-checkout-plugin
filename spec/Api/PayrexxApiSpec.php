<?php declare(strict_types=1);

namespace spec\Comvation\SyliusPayrexxCheckoutPlugin;

use Http\Message\MessageFactory;
use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxApi;
use Payum\Core\HttpClientInterface;
use PhpSpec\ObjectBehavior;

class PayrexxApiSpec extends ObjectBehavior
{
    function let(
        HttpClientInterface $httpClientInterface,
        MessageFactory $messageFactory
    ) {
        $this->beConstructedWith(
            [
                'instance' => 'testinstance',
                'api_key' => '123abc',
                'domain' => 'comvation.shop'
            ],
            $httpClientInterface,
            $messageFactory
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(PayrexxApi::class);
    }

    function it_constructs_a_proper_payrexx_api_object()
    {
        $this->getInstance()->shouldReturn('testinstance');
        $this->getApiKey()->shouldReturn('123abc');
        $this->getDomain()->shouldReturn('comvation.shop');
    }
}
