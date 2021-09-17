<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\PayrexxApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Sylius\Component\Payment\Model\PaymentInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
        /** @var PaymentInterface */ // TODO: Whatis
        $payment = $request->getModel();
        $details = $payment->getDetails();
        if ($details['gatewayId'] ?? null) {
            return;
        }
        /** @var array */
        $details = $this->api->createGateway($request);
        $payment->setDetails($details);
        $link = $details['link'] ?? null;
        if (!$link) {
            throw new LogicException('Missing link');
        }
        throw new HttpRedirect($link); // Payum flushes first
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof PaymentInterface;
    }
}
