<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();
        $details = $payment->getDetails();

        if (200 === $details['httpStatus']) {
            $request->markCaptured();
            return;
        }

        $request->markFailed();
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface
            && $request->getFirstModel() instanceof SyliusPaymentInterface;
    }
}
