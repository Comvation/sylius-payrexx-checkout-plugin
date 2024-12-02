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
        private LoggerInterface $logger,
        private PaymentRepositoryInterface $paymentRepository,
        private HttpClientInterface $client,
        private Payum $payum,
    ) {}

    private function handleException(\Exception $e)
    {
        $error = sprintf(
            'Exception class %1$s code %2$s: message %3$s',
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
        );
        $this->logger->error($error);
        die($error . PHP_EOL);
    }

    public function __invoke(
        Request $request,
    ): Response {
        try {
            return $this->handle($request);
        } catch (InvalidArgumentException $e) {
            $this->handleException($e);
        } catch (HttpException $e) {
            $this->handleException($e);
        } catch (LogicException $e) {
            $this->handleException($e);
        }
    }

    private function getTransactionParam(array $param): array
    {
        $transaction = $param['transaction'] ?? null;
        if (!$transaction) {
            throw new LogicException('Missing transaction');
        }
        $this->logger->warning(__METHOD__, ['transaction' => $transaction]);
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
        $this->logger->warning(__METHOD__, ['$referenceId' => $referenceId]);
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
        $this->logger->warning(__METHOD__, ['payment class' => $payment::class]);
        $this->logger->warning(__METHOD__, ['payment ID' => $payment->getId()]);
        return $payment;
    }

    private function getPayumHash(PaymentInterface $payment): string
    {
        $paymentDetails = $payment->getDetails();
        $this->logger->warning(__METHOD__, ['$paymentDetails' => $paymentDetails]);
        $payumHash = $paymentDetails['payum_hash'] ?? null;
        if (!$payumHash) {
            throw new LogicException('Missing payum hash');
        }
        $this->logger->warning(__METHOD__, ['$payumHash' => $payumHash]);
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
        $this->logger->warning(__METHOD__, ['request class' => $request::class]);
        $param = $request->request->all();
        $this->logger->warning(__METHOD__, ['param' => $param]);
        $orderId = $this->getTransactionInvoiceReferenceIdParam($param);
        $payment = $this->getLatestPaymentByOrderId($orderId);
        $payumHash = $this->getPayumHash($payment);
        $token = $this->getPayumToken($payumHash);
        $gateway = $this->payum->getGateway($token->getGatewayName());
        $gateway->execute(new Notify($token));

        // Respond as Payrexx expects
        return new Response('', 100);
    }
}
