<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Helper;

use Comvation\SyliusPayrexxCheckoutPlugin\PayrexxApi;

final class ApiHelper
{
    const API_BASE_URL_FORMAT = 'https://api.%1$s/%2$s/%3$s/%4$d?%5$s';

    /**
     * Return the parameter array with added signature
     *
     * Note that the incoming parameters must contain neither "instance"
     * nor "ApiSignature" elements.
     * @return array
     */
    public static function sign(PayrexxApi $api, array $params)
    {
        $params['ApiSignature'] = base64_encode(
            hash_hmac(
                'sha256',
                http_build_query($params),
                $api->getApiKey(),
                true
            )
        );
        return $params;
    }

    /**
     * Return the Payrexx API URL
     * @return string
     */
    public static function getUrl(PayrexxApi $api, string $model, int $id = 0)
    {
        return sprintf(static::API_BASE_URL_FORMAT,
            $api->getDomain(),
            'v1',
            $model,
            $id,
            'instance=' . $api->getInstance()
        );
    }
}
