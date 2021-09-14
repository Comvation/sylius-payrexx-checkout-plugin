<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Action;

use Comvation\SyliusPayrexxCheckoutPlugin\Helper\ApiHelper;
use Comvation\SyliusPayrexxCheckoutPlugin\Helper\GatewayHelper;
use Comvation\SyliusPayrexxCheckoutPlugin\Helper\PaymentStateHelper;
use Comvation\SyliusPayrexxCheckoutPlugin\PayrexxApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Payrexx\Models\Response\Gateway as PayrexxGateway;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private ClientInterface $client;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger
    )
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        if (!$payment) {
            throw new LogicException('Missing Payment');
        }

        $this->logger->info(__METHOD__, ['payment' => var_export($payment, true)]);
        $this->logger->info(__METHOD__, ['first payment' => var_export($request->getFirstModel(), true)]);

        /** @var array */
        $details = $payment->getDetails();

        $this->logger->info(__METHOD__, ['details' => $details]);

        if (
            $payment->getState() !== PaymentInterface::STATE_NEW
            // Allow retrying a payment while it's still pending
            && $payment->getState() !== PaymentInterface::STATE_PROCESSING
        ) {
            // MUST NOT return to Payrexx in any other case
            return;
        }

        if (empty($details['gatewayStatus'])) {
            $details = $this->createGateway($request);
            $payment->setDetails($details);
        }

        $link = $details['link'] ?? null;
        if (!$link) {
            throw new LogicException('Missing link');
        }

        $this->logger->info(__METHOD__, ['link' => $link]);

        throw new HttpRedirect($link);
    }

    /**
     * Request a Payrexx Gateway and return the essential parts
     *
     * Returns Gateway data on success, or information on the error.
     * @return array
     */
    private function createGateway($request)
    {
        $token = $request->getToken();
        $targetUrl = $token->getTargetUrl();

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        $params = GatewayHelper::getParameters($payment);
        $params += [
            // Charge on authorization operation requires parameter
            // "preAuthorization" to be true
            'chargeOnAuthorization' => 1,
            'preAuthorization' => 1,
            'successRedirectUrl' => $targetUrl,
            'failedRedirectUrl' => $targetUrl,
            'cancelRedirectUrl' => $targetUrl,
        ];
        $this->logger->info(__METHOD__ . ' params, unsigned', $params);
        $paramsSigned = ApiHelper::sign($this->api, $params);
        $this->logger->info(__METHOD__ . ' params, signed', $params);
        $apiUrl = ApiHelper::getUrl($this->api, 'Gateway');
        $this->logger->info(__METHOD__, ['url' => $apiUrl]);
        try {
            $response = $this->client->request(
                'POST',
                $apiUrl,
                [
                    // Payrexx uses plain old "form data".
                    // Make sure to encode according to RFC3986
                    // (e.g., space as '%20', not '+')
                    'body' => http_build_query(
                        $paramsSigned,
                        '',
                        '&',
                        PHP_QUERY_RFC3986
                    ),
                ]
            );
            $body = json_decode((string)$response->getBody());
            $gateway = (new PayrexxGateway())->fromArray(current($body->data));
            $this->logger->info(__METHOD__, ['gateway' => $gateway]);
            $details = [
                'httpStatus' => $response->getStatusCode(),
                'gatewayStatus' => $body->status,
                'id' => $gateway->getId(),
                'referenceId' => $gateway->getReferenceId(),
                'link' => $gateway->getLink(),
                'amount' => $gateway->getAmount(),
                'currency' => $gateway->getCurrency(),
            ];
            if ($body->status !== 'success') {
                throw new RequestException(
                    'Failed to obtain a valid Gateway',
                    $request,
                    $response
                );
            }
            PaymentStateHelper::updateStates(
                $payment,
                $gateway->getStatus(),
                $this->logger
            );
        } catch (RequestException $exception) {
            unset($details['info']);
            $response = $exception->getResponse();
            $details['httpStatus'] = $response->getStatusCode();
            $details['gatewayStatus'] = 'error';
            $details['message'] = $exception->getMessage();
        }
        return $details;
    }

    public function supports($request): bool
    {
        return $request instanceof Capture
            && $request->getModel() instanceof PaymentInterface;
    }

    public function setApi($api): void
    {
        if (!$api instanceof PayrexxApi) {
            throw new UnsupportedApiException(
                'Expected an instance of ' . PayrexxApi::class . ', but got ' . $api::class
            );
        }
        $this->api = $api;
    }
}
