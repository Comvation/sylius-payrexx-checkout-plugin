<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Controller;

use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Payum\Core\Payum;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityToken;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotifyController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        private PaymentRepositoryInterface $paymentRepository,
        private Payum $payum,
    ) {}

    private function handleException(\Exception $e)
    {
        $error = sprintf(
            '%1$s: %3$s',
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
        );
        $this->logger->error($error);
        return new Response('Invalid Request', 500);
    }

    public function __invoke(
        Request $request,
    ): Response {
        try {
            return $this->handle($request);
        } catch (
            InvalidArgumentException
            | HttpException
            | LogicException
            $e
        ) {
            return $this->handleException($e);
        }
    }

    private function getTransactionParam(array $param): array
    {
        $transaction = $param['transaction'] ?? null;
        if (!$transaction) {
            throw new LogicException('Missing transaction');
        }
        return $transaction;
    }

    private function getTransactionInvoiceParam(array $param): array
    {
        $transaction = $this->getTransactionParam($param);
        $invoice = ($transaction['invoice'] ?? []);
        if (!$invoice) {
            throw new LogicException('Missing invoice');
        }
        return $invoice;
    }

    private function getTransactionInvoiceReferenceIdParam(array $param): string
    {
        $invoice  = $this->getTransactionInvoiceParam($param);
        $referenceId = ($invoice['referenceId'] ?? '');
        if (!$referenceId) {
            throw new LogicException('Missing reference ID');
        }
        return $referenceId;
    }

    private function getLatestPaymentByOrderId(
        string $orderId
    ): PaymentInterface {
        /** @var PaymentInterface[] $payments */
        $payments = $this->paymentRepository->findBy(
            ['order' => $orderId],
            ['id' => 'desc'],
        );
        if (!$payments) {
            throw new LogicException('Missing Payments');
        }
        $payment = null;
        /** @var PaymentInterface $payment */
        foreach ($payments as $payment) {
            if ($payment->getDetails()) {
                break;
            }
        }
        if (!$payment) {
            throw new LogicException('Missing Payment');
        }
        return $payment;
    }

    private function getPayumHash(PaymentInterface $payment): string
    {
        $paymentDetails = $payment->getDetails();
        $payumHash = $paymentDetails['payum_hash'] ?? null;
        if (!$payumHash) {
            throw new LogicException('Missing payum hash');
        }
        return $payumHash;
    }

    private function getPayumToken($payumHash): PaymentSecurityToken
    {
        $storage = $this->payum->getTokenStorage();
        $token = $storage->find(['hash' => $payumHash]);
        return $token;
    }

    private function handle(Request $request): Response
    {
        $param = $request->request->all();
        $orderId = $this->getTransactionInvoiceReferenceIdParam($param);
        $payment = $this->getLatestPaymentByOrderId($orderId);
        $payumHash = $this->getPayumHash($payment);
        $token = $this->getPayumToken($payumHash);
        $gateway = $this->payum->getGateway($token->getGatewayName());
        $gateway->execute(new Notify($token));
        return new Response('', 200);
    }
}
