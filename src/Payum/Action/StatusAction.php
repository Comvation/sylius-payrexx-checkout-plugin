<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxApi;
use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxPayumPaymentStatusMapper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

final class StatusAction
    implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = PayrexxApi::class;
    }

    /**
     * {@inheritDoc}
     * @param GetStatus $request
     */
    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        /** @var array */
        $details = $payment->getDetails();
        if (empty($details['gatewayId'])) {
            throw new RequestNotSupportedException('Missing Gateway');
        }
        echo __METHOD__. PHP_EOL;
        echo ('request gateway id '.$details['gatewayId']) . PHP_EOL;
        $payrexxPaymentState = $this->api
            ->requestPaymentStatus($details['gatewayId']);
        $paymentState =
            PayrexxPayumPaymentStatusMapper::transitionPaymentState(
                $payment,
                $payrexxPaymentState,
            );
// TEST: $paymentState = PaymentInterface::STATE_COMPLETED;
        echo ('payrexx payment state '.$payrexxPaymentState.' -> payum '.$paymentState) . PHP_EOL;
        // The next three states are final
        if ($paymentState === PaymentInterface::STATE_COMPLETED) {
            $request->markCaptured();
            return;
        }
        if ($paymentState === PaymentInterface::STATE_CANCELLED) {
            $request->markCanceled();
            return;
        }
        if ($paymentState === PaymentInterface::STATE_FAILED) {
            $request->markFailed();
            return;
        }
        // If the payment is interrupted at Payrexx (e.g., by the customer
        // clicking the close icon), its status remains "waiting".
        // The current payment needs to be cancelled in order to
        // be able to restart with a new one.
        if ($paymentState === PaymentInterface::STATE_PROCESSING) {
            @trigger_error(__METHOD__
                . ' INFO: Payment has been aborted, cancelling');
            $request->markCanceled();
            return;
        }
        // Cases that shouldn't occur (and haven't during testing)
        if (empty($details['link'])) {
            @trigger_error(__METHOD__
                . ' WARNING: Got a Gateway without link, marking as failed');
            $request->markFailed();
            return;
        }
        if ($paymentState === PaymentInterface::STATE_NEW) {
            @trigger_error(__METHOD__
                . ' WARNING: Payment status is still "new", trying to go on');
            return;
        }
        if ($paymentState === PaymentInterface::STATE_AUTHORIZED) {
            // Presuming that it's still possible for the payment to fail
            // or to be cancelled by the customer,
            // this must not be accepted as complete.
            @trigger_error(__METHOD__
                . ' WARNING: Payment status is still "authorized",'
                . ' marking as authorized');
            $request->markAuthorized();
            return;
        }
        throw new RequestNotSupportedException(
            'Unhandled payment state ' . $paymentState
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface
            && $request->getModel() instanceof PaymentInterface;
    }
}
