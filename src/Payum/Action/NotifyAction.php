<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;

final class NotifyAction
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
     * @param \Payum\Core\Request\Notify $request
     */
    public function execute($request): void
    {
        // noop
    }

    /**
     * Handle supported notification
     *
     * This MUST return true at the right time, when model is a payment, and
     * "handle" the notification (even if that's a noop).
     * Otherwise, Payum throws an exception, and the process is aborted.
     * Only if it succeeds, the GetStatus action is triggered, which
     * in turn requests and updates the payment status from Payrexx.
     */
    public function supports($request): bool
    {
        $isNotify = $request instanceof Notify;
        $token = $request->getToken();
        $model = $request->getModel();
        return true
            && $isNotify
            && $model instanceof PaymentInterface
        ;
    }
}
