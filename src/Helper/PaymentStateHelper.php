<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Helper;

use Payrexx\Models\Response\Transaction;
use Payum\Core\Exception\LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as PaymentInterface;
use Sylius\Component\Core\OrderPaymentStates;

final class PaymentStateHelper
{
    /**
     * Payrexx Payment status state-event machine
     *
     * Structure:
     *  [ state in => [ event => state out, ...], ...]
     * state in and out are Payment states.
     * Events are Payrexx status.
     * Missing keys and unset values both indicate invalid transitions.
     */
    const semPaymentState = [
        PaymentInterface::STATE_NEW => [
            // Payment requested, waiting for result
            Transaction::WAITING => PaymentInterface::STATE_PROCESSING,
        ],
        // Pending
        PaymentInterface::STATE_PROCESSING => [
            // Success
            // Payment authorized
            Transaction::AUTHORIZED => PaymentInterface::STATE_AUTHORIZED,
            // Payment complete; successful
            Transaction::CONFIRMED => PaymentInterface::STATE_COMPLETED,
            // Failure
            // Payment aborted by customer; failed
            Transaction::CANCELLED => PaymentInterface::STATE_CANCELLED,
            // Payment declined; failed
            Transaction::DECLINED => PaymentInterface::STATE_FAILED,
            // Technical error; failed
            Transaction::ERROR => PaymentInterface::STATE_FAILED,
        ],
        PaymentInterface::STATE_AUTHORIZED => [
            // Payment complete; successful
            Transaction::CONFIRMED => PaymentInterface::STATE_COMPLETED,
// TODO: Remove the following transitions unless they are possible:
            // Failure
            // Payment aborted by customer; failed
            Transaction::CANCELLED => PaymentInterface::STATE_CANCELLED,
            // Payment declined; failed
            Transaction::DECLINED => PaymentInterface::STATE_FAILED,
            // Technical error; failed
            Transaction::ERROR => PaymentInterface::STATE_FAILED,
        ],
        // Success
        // No changes to these
        //PaymentInterface::STATE_COMPLETED => [],

        // Failure
        // No changes to these
// TODO: Consider allowing the payment to be repeated (in selected cases)
        //PaymentInterface::STATE_FAILED => [],
        //PaymentInterface::STATE_CANCELLED => [],
        //PaymentInterface::STATE_REFUNDED => [],
        //PaymentInterface::STATE_UNKNOWN => [],

        // Invalid
        // The Payment state has already been flipped from "cart" to "new"
        // before the payment process begins.
        //PaymentInterface::STATE_CART => [],

        // Ignored events:  These are not supposed to occur.
        // (Undocumented, or unsupported for other reasons)
        //Transaction::RESERVED => [], // (Undocumented)
        //Transaction::INITIATED => [], // (Undocumented)
        //Transaction::DISPUTED => [], // (Undocumented)
        //Transaction::EXPIRED => [], // (Undocumented)
        //Transaction::REFUND_PENDING => [], // Refund has been initialized, not confirmed yet
        //Transaction::PARTIALLY_REFUNDED => [], // Payment partially refunded
        //Transaction::REFUNDED => [], // Payment refunded
        //Transaction::INSECURE => [], // (Undocumented)
        //Transaction::UNCAPTURED => [], // (Not documented; only with PSP Clearhaus Acquiring)
        //'chargeback' // (No constant defined) Chargeback request by card holder
    ];

    /**
     * Payrexx Order payment status state-event machine
     *
     * Structure:
     *  [ state in => [ event => state out, ...], ...]
     * state in and out are Order payment states.
     * Events are Payrexx status.
     * Missing keys and unset values both indicate invalid transitions.
     */
    const semOrderPaymentState = [
        // New
        OrderPaymentStates::STATE_CART => [
            // Payment requested, waiting for result
            Transaction::WAITING => OrderPaymentStates::STATE_AWAITING_PAYMENT,
        ],
        // Pending
        OrderPaymentStates::STATE_AWAITING_PAYMENT => [
            // Success
            // Payment authorized
            Transaction::AUTHORIZED => OrderPaymentStates::STATE_AUTHORIZED,
            // Payment complete; successful
            Transaction::CONFIRMED => OrderPaymentStates::STATE_PAID,
            // Failure
            // Payment aborted by customer; failed
            Transaction::CANCELLED => OrderPaymentStates::STATE_CANCELLED,
            // Payment declined; failed
            Transaction::DECLINED => OrderPaymentStates::STATE_CANCELLED,
            // Technical error; failed
            Transaction::ERROR => OrderPaymentStates::STATE_CANCELLED,
        ],
        // Success
        OrderPaymentStates::STATE_AUTHORIZED => [
            // Payment complete; successful
            Transaction::CONFIRMED => OrderPaymentStates::STATE_PAID,
// TODO: Remove the following transitions unless they are possible:
            // Payment aborted by customer; failed
            Transaction::CANCELLED => OrderPaymentStates::STATE_CANCELLED,
            // Payment declined; failed
            Transaction::DECLINED => OrderPaymentStates::STATE_CANCELLED,
            // Technical error; failed
            Transaction::ERROR => OrderPaymentStates::STATE_CANCELLED,
        ],
        // No change
        //OrderPaymentStates::STATE_PAID => [],

        // Failure
        // No change
        //OrderPaymentStates::STATE_CANCELLED => [],

        // Unsupported
        //OrderPaymentStates::STATE_PARTIALLY_AUTHORIZED => [],
        //OrderPaymentStates::STATE_PARTIALLY_PAID => [],
        //OrderPaymentStates::STATE_PARTIALLY_REFUNDED => [],
        //OrderPaymentStates::STATE_REFUNDED => [],
    ];

    /**
     * Transition the Payment state, if possible
     *
     * Sets the updated state, if any.
     * Returns the updated (or current) state.
     */
    public static function transitionPaymentState(
        PaymentInterface $payment, string $event
    ): string {
        $paymentStateCurrent = $payment->getState();
        $paymentStateUpdated =
            static::semPaymentState[$paymentStateCurrent][$event] ?? null;
        if ($paymentStateUpdated) {
            $payment->setState($paymentStateUpdated);
            return $paymentStateUpdated;
        }
        return $paymentStateCurrent;
    }

    /**
     * Transition the Order Payment state, if possible
     *
     * Sets the updated state, if any.
     * Returns the updated (or current) state.
     */
    public static function transitionOrderPaymentState(
        OrderInterface $order, string $event
    ): string {
        $orderPaymentStateCurrent = $order->getPaymentState();
        $orderPaymentStateUpdated =
            static::semOrderPaymentState[$orderPaymentStateCurrent][$event] ?? null;
        if ($orderPaymentStateUpdated) {
            $order->setPaymentState($orderPaymentStateUpdated);
            return $orderPaymentStateUpdated;
        }
        return $orderPaymentStateCurrent;
    }

    /**
     * Update both Payment and Order states
     *
     * Expects any valid, and supported, Payrexx status.
     * Mind that you must make sure that the Payment is stored,
     * lest this method is called from CaptureAction,
     * where Payum will handle this.
     * @throws LogicException on missing Order
     */
    public static function updateStates(
        PaymentInterface $payment,
        string $status,
// TODO: Remove Logger when done debugging
        LoggerInterface $logger
    ) {
        /** @var OrderInterface $order */ // \Comvation\SyliusPayrexxCheckoutPlugin\Entity\Order\Order
        $order = $payment->getOrder();
        if (!$order) {
            throw new LogicException('Missing Order');
        }
$logger->info(__METHOD__ . ' Incoming Payment', ['status' => $payment->getState()]);
$logger->info(__METHOD__ . ' Incoming Order', ['status' => $order->getPaymentState()]);
        $paymentStateUpdated =
            PaymentStateHelper::transitionPaymentState(
                $payment, $status
            );
        $orderPaymentStateUpdated =
            PaymentStateHelper::transitionOrderPaymentState(
                $order, $status
            );
$logger->info(__METHOD__ . ' Updated Payment', ['status' => $payment->getState()]);
$logger->info(__METHOD__ . ' Updated Order', ['status' => $order->getPaymentState()]);
    }
}
