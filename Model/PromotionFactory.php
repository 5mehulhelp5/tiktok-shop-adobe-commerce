<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model;

class PromotionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Promotion
    {
        return $this->objectManager->create(Promotion::class);
    }
}
