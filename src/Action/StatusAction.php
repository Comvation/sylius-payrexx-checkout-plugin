<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Controller\PaymentStateController;
use Comvation\SyliusPayrexxCheckoutPlugin\PayrexxApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetStatusInterface;
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
     */
    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        $paymentStatusCurrent = $payment->getState();
        $details = $request->getModel();
        $details = ArrayObject::ensureArrayObject($details);
        $paymentStatus = $this->api->requestPaymentStatus($request);
        $paymentStatus = PaymentStateController::transitionPaymentState(
            $payment, $paymentStatus
        );
        // The next three states are final
        if ($paymentStatus === PaymentInterface::STATE_COMPLETED) {
            $request->markCaptured();
            return;
        }
        if ($paymentStatus === PaymentInterface::STATE_CANCELLED) {
            $request->markCanceled();
            return;
        }
        if ($paymentStatus === PaymentInterface::STATE_FAILED) {
            $request->markFailed();
            return;
        }
        // Cases that shouldn't occur (and haven't during testing)
        if (empty($details['gatewayId'])) {
            @trigger_error(__METHOD__
                . ' WARNING: No gateway, marking as new');
            $request->markNew();
            return;
        }
        if (empty($details['link'])) {
            @trigger_error(__METHOD__
                . ' WARNING: Got a gateway without link, marking as failed');
            $request->markFailed();
            return;
        }
        if ($paymentStatus === PaymentInterface::STATE_NEW) {
            @trigger_error(__METHOD__
                . ' WARNING: Payment status is still "new", trying to go on');
            return;
        }
        if ($paymentStatus === PaymentInterface::STATE_PROCESSING) {
            @trigger_error(__METHOD__
                . ' WARNING: Payment status is still "processing", marking as pending');
            $request->markPending();
            return;
        }
        if ($paymentStatus === PaymentInterface::STATE_AUTHORIZED) {
            // Presuming that it's still possible for the payment to fail
            // or to be cancelled by the customer,
            // this must not be accepted as complete.
            @trigger_error(__METHOD__
                . ' WARNING: Payment status is still "authorized", marking as authorized');
            $request->markAuthorized();
            return;
        }
        throw new \Exception('Unhandled case: paymentStatus ' . $paymentStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface
            && $request->getModel() instanceof \ArrayAccess;
    }
}
