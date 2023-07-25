<?php

namespace Comvation\SyliusPayrexxCheckoutPlugin\Api;

use Payrexx\Models\Response\Transaction;
use Sylius\Component\Core\Model\PaymentInterface;

/**
 * Payrexx Payment to Payum status mapper
 */
class PayrexxPayumPaymentStatusMapper
{
    /**
     * Payrexx Payment status state-event machine
     *
     * Structure:
     *  [ state in => [ event => state out, ...], ...]
     * State in and out are Payment states.
     * Events are Payrexx Transaction status.
     * Missing keys and unset values both indicate invalid transitions.
     */
    const semPaymentState = [
        PaymentInterface::STATE_NEW => [
            // Pending
            // Payment requested, waiting for result
            Transaction::WAITING => PaymentInterface::STATE_PROCESSING,
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
        PaymentInterface::STATE_PROCESSING => [
            // Success
            Transaction::AUTHORIZED => PaymentInterface::STATE_AUTHORIZED,
            Transaction::CONFIRMED => PaymentInterface::STATE_COMPLETED,
            // Failure
            Transaction::CANCELLED => PaymentInterface::STATE_CANCELLED,
            Transaction::DECLINED => PaymentInterface::STATE_FAILED,
            Transaction::ERROR => PaymentInterface::STATE_FAILED,
        ],
        PaymentInterface::STATE_AUTHORIZED => [
            // Success
            Transaction::CONFIRMED => PaymentInterface::STATE_COMPLETED,
            // Failure
            Transaction::CANCELLED => PaymentInterface::STATE_CANCELLED,
            Transaction::DECLINED => PaymentInterface::STATE_FAILED,
            Transaction::ERROR => PaymentInterface::STATE_FAILED,
        ],

        // No changes must be made to the following.
        // Restart the payment process with a new gateway instead.
        // Success
        //PaymentInterface::STATE_COMPLETED => [],
        // Failure
        //PaymentInterface::STATE_FAILED => [],
        //PaymentInterface::STATE_CANCELLED => [],
        // Unsupported
        //PaymentInterface::STATE_REFUNDED => [],
        //PaymentInterface::STATE_UNKNOWN => [],

        // Invalid
        // The Payment state has already been flipped from "cart" to "new"
        // before the payment process begins.
        //PaymentInterface::STATE_CART => [],

        // Ignored events:  These are not supposed to occur.
        // (Undocumented, or unsupported for other reasons)
        //Transaction::RESERVED => null, // (Undocumented)
        //Transaction::INITIATED => null, // (Undocumented)
        //Transaction::DISPUTED => null, // (Undocumented)
        //Transaction::EXPIRED => null, // (Undocumented)
        //Transaction::REFUND_PENDING => null, // Refund has been initialized, not confirmed yet
        //Transaction::PARTIALLY_REFUNDED => null, // Payment partially refunded
        //Transaction::REFUNDED => null, // Payment refunded
        //Transaction::INSECURE => null, // (Undocumented)
        //Transaction::UNCAPTURED => null, // (Not documented; only with PSP Clearhaus Acquiring)
        //'chargeback' => null // (No constant defined) Chargeback request by card holder
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
        return $paymentStateUpdated ?? $paymentStateCurrent;
    }
}
