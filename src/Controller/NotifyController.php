<?php

namespace Comvation\SyliusPayrexxCheckoutPlugin\Controller;

use Comvation\SyliusPayrexxCheckoutPlugin\Helper\PaymentStateHelper;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Payrexx payment status notification controller.
 */
class NotifyController extends AbstractController
{
    private LoggerInterface $logger;
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(LoggerInterface $logger, PaymentRepositoryInterface $paymentRepository)
    {
        $this->logger = $logger;
        $this->paymentRepository = $paymentRepository;
    }

    public function doAction(Request $request): Response {
$this->logger->info(__METHOD__, ['request' => $request->request]);
        $param = $request->request->all();
$this->logger->info(__METHOD__, ['param' => $param]);
        $transaction = $param['transaction'] ?? null;
        if (!$transaction) {
            throw new LogicException('Missing transaction');
        }
$this->logger->info(__METHOD__, ['transaction' => $transaction]);
        $status = $transaction['status'] ?? null;
        if (!$status) {
            throw new LogicException('Missing status');
        }
$this->logger->info(__METHOD__, ['status' => $status]);
        $orderId = (int) ($param['transaction']['referenceId'] ?? 0);
        if (!$orderId) {
            throw new LogicException('Missing Order ID');
        }
$this->logger->info(__METHOD__, ['orderId' => $orderId]);
        // Mind that the Order ID is referred to as 'order'
        /** @var PaymentInterface */ // Payment
        $payment = $this->paymentRepository->findOneBy(['order' => $orderId]);
        if (!$payment) {
            throw new LogicException('Missing Payment');
        }
$this->logger->info(__METHOD__, ['payment' => $payment::class]);
$this->logger->info(__METHOD__, ['payment ID' => $payment->getId()]);
$this->logger->info(__METHOD__, ['order ID from payment' => $payment->getOrder()->getId()]);
        /** @var OrderInterface $order */ // \Comvation\SyliusPayrexxCheckoutPlugin\Entity\Order\Order
        $order = $payment->getOrder();
        if (!$order) {
            throw new LogicException('Missing Order');
        }
$this->logger->info(__METHOD__, ['order' => $order::class]);
        $amount = (int) $transaction['amount'];
        if ($amount !== $order->getTotal()) {
            throw new InvalidArgumentException('Mismatch');
        }
        PaymentStateHelper::updateStates($payment, $status, $this->logger);
        // Must flush here, as the updated $payment doesn't pass thru Payum
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();
        return new Response('', 100);
    }
}
