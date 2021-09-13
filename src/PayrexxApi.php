<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin;

final class PayrexxApi
{
    /** @var string */
    private $instance;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $domain;

    public function __construct(
        string $instance,
        string $apiKey,
        string $domain
    ) {
        $this->instance = $instance;
        $this->apiKey = $apiKey;
        $this->domain = $domain;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
