<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Api;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class PayrexxApi
{
    const API_BASE_URL_FORMAT = 'https://api.%1$s/%2$s/%3$s/%4$d?%5$s';

    private ArrayObject $options;

    /**
     * @throws \Payum\Core\Exception\InvalidArgumentException on invalid option
     */
    public function __construct(
        ArrayObject $options,
        HttpClientInterface $client,
        MessageFactory $messageFactory
    ) {
        $options = ArrayObject::ensureArrayObject($options);
        $options->validateNotEmpty([
            'instance',
            'api_key',
            'domain',
        ]);
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    public function getInstance(): string
    {
        return $this->options['instance'];
    }

    public function getApiKey(): string
    {
        return $this->options['api_key'];
    }

    public function getDomain(): string
    {
        return $this->options['domain'];
    }

    /**
     * Request a Payrexx Gateway and return essential details
     *
     * Returns Gateway data (gatewayId, link) on success,
     * or information on the error otherwise.
     * You may want to store either in the Payment details.
     * @return array
     */
    public function createGateway($request)
    {
        $token = $request->getToken();
        $targetUrl = $token->getTargetUrl();
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        $params = static::getParameters($payment);
        $params += [
            // Charge on authorization operation requires parameter
            // "preAuthorization" to be true
            'chargeOnAuthorization' => 1,
            'preAuthorization' => 1,
            'successRedirectUrl' => $targetUrl,
            'failedRedirectUrl' => $targetUrl,
            'cancelRedirectUrl' => $targetUrl,
        ];
        try {
            $dataGateway = $this->doRequest(
                'POST', 'Gateway', 0, $params
            );
            $details = [
                'gatewayId' => $dataGateway->id,
                'link' => $dataGateway->link,
            ];
        } catch (\Exception $exception) {
            $details['message'] =
                $exception->getMessage()
                . ': ' . $exception->getPrevious()->getMessage();
        }
        return $details;
    }

    /**
     * Return data for Payrexx Gateway creation
     * @return array
     */
    public static function getParameters(PaymentInterface $payment)
    {
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
    public static function getParametersOrder(OrderInterface $order)
    {
        return [
            'referenceId' => $order->getId(),
            'amount' => $order->getTotal(), // This is in cents already
            'currency' => $order->getCurrencyCode(), //'CHF',
            // NTH: Add purpose (only if it can be easily localized):
            //'purpose' => 'Your order # on the YMD@HMS',
        ];
    }

    /**
     * Return the required and optional data from the Customer
     * @return array
     */
    public static function getParametersCustomer(CustomerInterface $customer)
    {
        $params['fields']['email'] = ['value' => $customer->getEmail()];
        $title = static::getParameterTitle($customer->getGender());
        if ($title) {
            $params['fields']['title'] = ['value' => $title];
        }
        return $params;
    }

    /**
     * Return the optional Customer title, or the empty string
     * @return string
     */
    public static function getParameterTitle(string $gender)
    {
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

    /**
     * Return the required and optional data from the (billing) Address
     * @return array
     */
    public static function getParametersAddress(AddressInterface $address)
    {
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
     * Return the first data element of the response to an API request
     *
     * Adds the signature to the parameters, and builds the proper URL.
     * @param string $method HTTP method
     * @param string $model Payrexx model class name
     * @param int $id optional ID
     * @param array $parameters optional parameters, unsigned
     */
    public function doRequest(
        string $method,
        string $model,
        int $id = 0,
        array $parameters = []
    ) {
        $parametersSigned = $this->sign($parameters);
        $apiUrl = $this->getUrl($model, $id);
        $request = $this->messageFactory->createRequest(
            $method,
            $apiUrl,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query($parametersSigned, '', '&', PHP_QUERY_RFC3986),
        );
        $response = $this->client->send($request);
        if ($response->getStatusCode() < 200
            || $response->getStatusCode() > 300
        ) {
            throw HttpException::factory($request, $response);
        }
        $contents = $response->getBody()->getContents();
        $result = json_decode($contents);
        if (!$result) {
            throw new LogicException('Empty JSON: ' . $contents);
        }
        if (!$result->data) {
            throw new LogicException('Empty JSON data: ' . $contents);
        }
        return current($result->data);
    }

    /**
     * Return the given parameter array with added signature
     *
     * Note that the incoming parameters must contain neither "instance"
     * nor "ApiSignature" elements.
     * @return array
     */
    private function sign(array $parameters)
    {
        $parameters['ApiSignature'] = base64_encode(
            hash_hmac(
                'sha256',
                http_build_query($parameters),
                $this->options['api_key'],
                true
            )
        );
        return $parameters;
    }

    /**
     * Return the Payrexx API URL
     * @return string
     */
    private function getUrl(string $model, int $id = 0)
    {
        return sprintf(static::API_BASE_URL_FORMAT,
            $this->options['domain'],
            'v1',
            $model,
            $id,
            'instance=' . $this->options['instance'],
        );
    }

    /**
     * Request and return the current payment status from the Payrexx Gateway
     * @return string
     */
    public function requestPaymentStatus(
        int $gatewayId
    ): string {
        $dataGateway = $this->doRequest('GET', 'Gateway', $gatewayId);
        return $dataGateway->status;
    }
}
