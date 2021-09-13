<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Helper;

use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class GatewayHelper
{
    /**
     * Return data for Payrexx Gateway creation
     * @return array
     */
    public static function getParameters(PaymentInterface $payment) {
        $order = $payment->getOrder();
        $customer = $order->getCustomer();
        return array_merge_recursive(
            static::getParametersOrder($order),
            static::getParametersCustomer($customer),
            static::getParametersAddress($order->getBillingAddress())
        );
    }

    /**
     * Return the required data from the Order
     * @return array
     */
    public static function getParametersOrder(OrderInterface $order) {
        return [
            'referenceId' => $order->getId(),
            'amount' => $order->getTotal(), // This is in cents already
            'currency' => $order->getCurrencyCode(), //'CHF',
// TODO: Add purpose if requested, and if it's possible to localize it:
            //'purpose' => 'Your order # on the YMD@HMS',
        ];
    }

    /**
     * Return the required and optional data from the Customer
     * @return array
     */
    public static function getParametersCustomer(CustomerInterface $customer) {
        $params['fields']['email'] = ['value' => $customer->getEmail()];
        $title = static::getParameterTitle($customer->getGender());
        if ($title) {
            $params['fields']['title'] = ['value' => $title];
        }
        return $params;
    }

    /**
     * Return the required and optional data from the (billing) Address
     * @return array
     */
    public static function getParametersAddress(AddressInterface $address) {
        $fields = [];
        if ($address->getFirstName()) {
            $fields['forename'] = ['value' => $address->getFirstName()];
        }
        if ($address->getLastName()) {
            $fields['surname'] = ['value' => $address->getLastName()];
        }
        if ($address->getCompany()) {
            $fields['company'] = ['value' => $address->getCompany()];
        }
        if ($address->getStreet()) {
            $fields['street'] = ['value' => $address->getStreet()];
        }
        if ($address->getPostcode()) {
            $fields['postcode'] = ['value' => $address->getPostcode()];
        }
        if ($address->getCity()) {
            $fields['place'] = ['value' => $address->getCity()];
        }
        if ($address->getCountryCode()) {
            $fields['country'] = ['value' => $address->getCountryCode()];
        }
        if ($address->getPhoneNumber()) {
            $fields['phone'] = ['value' => $address->getPhoneNumber()];
        }
        if (!$fields) {
            return [];
        }
        return ['fields' => $fields];
    }

    /**
     * Return the optional Customer title, or the empty string
     * @return string
     */
    public static function getParameterTitle(string $gender) {
        // Gender is one of: 'f', 'm', 'u'
        $title = '';
        switch ($gender) {
            case 'f':
                $title = 'misses'; // Not documented
                break;
            case 'm':
                $title = 'mister';
                break;
        }
        return $title;
    }
}
