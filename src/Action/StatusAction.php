<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    /**
     * Dummy StatusAction handler
     *
     * Status updates are handled exclusively by the NotificationController.
     * This one must be present, and is called several times by Payum, however.
     * @param GetStatusInterface
     */
    public function execute($request): void
    {
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface
            && $request->getFirstModel() instanceof SyliusPaymentInterface;
    }
}
