<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Connector\Order\Receive;

class ItemsByUpdateDateCommand implements \M2E\TikTokShop\Model\Connector\CommandInterface
{
    private string $shopId;
    private \DateTimeInterface $updateFrom;
    private \DateTimeInterface $updateTo;
    private string $accountHash;

    public function __construct(
        string $accountHash,
        string $shopId,
        \DateTimeInterface $updateFrom,
        \DateTimeInterface $updateTo
    ) {
        $this->accountHash = $accountHash;
        $this->shopId = $shopId;
        $this->updateFrom = $updateFrom;
        $this->updateTo = $updateTo;
    }

    public function getCommand(): array
    {
        return ['orders', 'get', 'items'];
    }

    public function getRequestData(): array
    {
        return [
            'account' => $this->accountHash,
            'shop_id' => $this->shopId,
            'from_update_date' => $this->updateFrom->format('Y-m-d H:i:s'),
            'to_update_date' => $this->updateTo->format('Y-m-d H:i:s'),
        ];
    }

    public function parseResponse(
        \M2E\TikTokShop\Model\Connector\Response $response
    ): \M2E\TikTokShop\Model\TikTokShop\Connector\Order\Receive\Response {
        $responseData = $response->getResponseData();

        if (!array_key_exists('orders', $responseData)) {
            throw new \M2E\TikTokShop\Model\Exception('Server don`t return "orders" array');
        }

        if (!array_key_exists('to_update_date', $responseData)) {
            throw new \M2E\TikTokShop\Model\Exception('Server don`t return "to_update_date" date');
        }

        if (!array_key_exists('has_more', $responseData)) {
            throw new \M2E\TikTokShop\Model\Exception('Server don`t return "has_more" date');
        }

        return new Response(
            $responseData['orders'],
            $responseData['to_update_date'],
            $responseData['has_more'],
            $response->getMessageCollection()
        );
    }
}
