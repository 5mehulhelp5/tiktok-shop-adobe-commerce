<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Type\Stop;

use M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action;

class Validator implements \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Type\ValidatorInterface
{
    use Action\Type\ValidatorTrait;

    public function validate(
        \M2E\TikTokShop\Model\Product $product,
        \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Configurator $actionConfigurator,
        \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\VariantSettings $variantSettings
    ): bool {
        if (!$product->isStoppable()) {
            $this->addErrorMessage(
                new Action\Validator\ValidatorMessage(
                    (string)__('Item is not Listed or not available'),
                    \M2E\TikTokShop\Model\Tag\ValidatorIssues::NOT_USER_ERROR
                )
            );

            return false;
        }

        return true;
    }
}
