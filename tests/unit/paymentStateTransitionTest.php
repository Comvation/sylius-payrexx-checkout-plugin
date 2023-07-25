<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin;

use App\Entity\Payment\Payment;
use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxPayumPaymentStatusMapper;
use Payrexx\Models\Response\Transaction;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\PaymentInterface;

class paymentStateTransitionTest extends TestCase
{
    private function processTransition(string  $stateIn, string $event): string
    {
        $mapper = new PayrexxPayumPaymentStatusMapper();
        $payment = new Payment();
        $payment->setState($stateIn);
        $stateOut = $mapper->transitionPaymentState($payment, $event);
        return $stateOut;
    }

    /**
     * All known states and events must transition
     */
    public function testPaymentStateTransitionsValid(): void
    {
        foreach (PayrexxPayumPaymentStatusMapper::semPaymentState
            as $stateIn => $events
        ) {
            foreach ($events as $event => $stateExpected) {
                $stateOut = $this->processTransition($stateIn, $event);
                $this->assertEquals($stateExpected, $stateOut);
            }
        }
    }

    /**
     * Unknown states must not be changed
     */
    public function testPaymentStateTransitionInvalidState(): void
    {
        $stateIn = 'stateDoesNotExist';
        $event = Transaction::WAITING;
        $stateExpected = $stateIn;
        $stateOut = $this->processTransition($stateIn, $event);
        $this->assertEquals($stateExpected, $stateOut);
    }

    /**
     * Unknown events must not cause a transition
     */
    public function testPaymentStateTransitionInvalidEvent(): void
    {
        $stateIn = PaymentInterface::STATE_NEW;
        $event = 'eventDoesNotExist';
        $stateExpected = $stateIn;
        $stateOut = $this->processTransition($stateIn, $event);
        $this->assertEquals($stateExpected, $stateOut);
    }
}
