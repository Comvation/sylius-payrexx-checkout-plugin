<?php

namespace spec\Comvation\SyliusPayrexxCheckoutPlugin;

use Comvation\SyliusPayrexxCheckoutPlugin\PayrexxApi;
use PhpSpec\ObjectBehavior;

class PayrexxApiSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith("testinstance", "123abc", "comvation.shop");
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(PayrexxApi::class);
    }

    function it_constructs_a_proper_payrexx_api_object()
    {
        $this->getInstance()->shouldReturn("testinstance");
        $this->getApiKey()->shouldReturn("123abc");
        $this->getDomain()->shouldReturn("comvation.shop");
    }
}
