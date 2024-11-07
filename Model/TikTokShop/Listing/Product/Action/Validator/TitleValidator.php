<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Validator;

class TitleValidator implements ValidatorInterface
{
    public function validate(
        \M2E\TikTokShop\Model\Product $product,
        \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Configurator $configurator
    ): ?string {
        if (!$configurator->isTitleAllowed()) {
            return null;
        }

        $title = $product->getDescriptionTemplateSource()->getTitle();

        $titleLength = mb_strlen($title);

        if ($titleLength < 1 || $titleLength > 255) {
            return 'The product name must contain between 1 and 255 characters.';
        }

        return null;
    }
}
