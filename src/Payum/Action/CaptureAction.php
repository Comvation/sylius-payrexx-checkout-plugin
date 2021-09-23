<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Payum\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Api\PayrexxApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
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
        /** @var PaymentInterface */
        $payment = $request->getModel();
        /** @var array */
        $details = $payment->getDetails();
        if ($details['gatewayId'] ?? null) {
            return;
        }
        $details = $this->api->createGateway($request);
        if (empty($details['gatewayId'])) {
            throw new RequestNotSupportedException(
                'Failed to create Payrexx Gateway'
            );
        }
        $payment->setDetails($details);
        $link = $details['link'] ?? null;
        if (!$link) {
            throw new RequestNotSupportedException('Missing link');
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
