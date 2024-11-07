<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Connector\Item;

class ListCommand implements \M2E\TikTokShop\Model\Connector\CommandInterface
{
    private string $accountHash;
    private array $requestData;

    public function __construct(string $accountHash, array $requestData)
    {
        $this->accountHash = $accountHash;
        $this->requestData = $requestData;
    }

    public function getCommand(): array
    {
        return ['product', 'create', 'entity'];
    }

    public function getRequestData(): array
    {
        return $this->requestData + ['account' => $this->accountHash];
    }

    public function parseResponse(
        \M2E\TikTokShop\Model\Connector\Response $response
    ): \M2E\TikTokShop\Model\Connector\Response {
        return $response;
    }
}
