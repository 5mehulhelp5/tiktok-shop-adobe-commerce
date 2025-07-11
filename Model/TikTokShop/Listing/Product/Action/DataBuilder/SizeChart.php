<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder;

use M2E\TikTokShop\Model\Category\CategoryAttribute;

class SizeChart extends AbstractDataBuilder
{
    public const NICK = 'SizeChart';

    private \M2E\TikTokShop\Model\Image\Repository $imageRepository;
    private \M2E\TikTokShop\Model\Magento\Product\ImageFactory $magentoProductImageFactory;
    private \M2E\TikTokShop\Model\Category\Attribute\Repository $attributeRepository;
    private ?\M2E\TikTokShop\Model\Image $onlineImage = null;

    public function __construct(
        \M2E\TikTokShop\Model\Category\Attribute\Repository $attributeRepository,
        \M2E\TikTokShop\Model\Image\Repository $imageRepository,
        \M2E\TikTokShop\Model\Magento\Product\ImageFactory $magentoProductImageFactory,
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper
    ) {
        parent::__construct($magentoAttributeHelper);
        $this->imageRepository = $imageRepository;
        $this->magentoProductImageFactory = $magentoProductImageFactory;
        $this->attributeRepository = $attributeRepository;
    }

    public function getBuilderData(): array
    {
        $categoryId = $this->getListingProduct()->getTemplateCategoryId();

        $sizeChartAttributes = $this->attributeRepository->findByDictionaryId(
            $categoryId,
            [
                CategoryAttribute::ATTRIBUTE_TYPE_SIZE_CHART,
            ],
        );

        if (count($sizeChartAttributes) === 0) {
            return [];
        }

        $sizeChartAttribute = reset($sizeChartAttributes);

        if (
            $sizeChartAttribute->isValueModeNone()
            || $sizeChartAttribute->isValueModeRecommended()
        ) {
            return [];
        }

        $sizeChartUrl = $this->getSizeChartUrl($sizeChartAttribute);

        if (empty($sizeChartUrl)) {
            return [];
        }

        if (!\M2E\TikTokShop\Helper\Data::isValidUrl($sizeChartUrl)) {
            $this->addWarningMessage((string)__('An invalid image URL is set for the Product Size Chart'));

            return [];
        }

        $magentoProductImage = $this->magentoProductImageFactory->create();
        $magentoProductImage->setUrl($sizeChartUrl);

        $image = $this->imageRepository->findByHashAndType(
            $magentoProductImage->getHash(),
            \M2E\TikTokShop\Model\Image::IMAGE_TYPE_CERTIFICATE
        );

        $this->onlineImage = $image;

        return [
            'image' => [
                'uri' => $image !== null ? $image->getUri() : null,
                'nick' => $magentoProductImage->getHash(),
                'url' => $magentoProductImage->getUrl(),
            ],
        ];
    }

    public function getMetadata(): array
    {
        return [
            self::NICK => [
                'image' => [
                    'uri' => $this->onlineImage === null ? null : $this->onlineImage->getUri(),
                ],
            ],
        ];
    }

    private function getSizeChartUrl($sizeChartAttribute): string
    {
        if ($sizeChartAttribute->isValueModeCustomValue()) {
            $attributeValue = $sizeChartAttribute->getCustomValue();
            if (empty($attributeValue)) {
                $this->addWarningMessage((string)__('Size Chart is missing a value.'));

                return '';
            }

            return $attributeValue;
        }

        if ($sizeChartAttribute->isValueModeCustomAttribute()) {
            $attributeCode = $sizeChartAttribute->getCustomAttributeValue();
            $magentoProduct = $this->getListingProduct()->getMagentoProduct();

            $this->searchNotFoundAttributes($magentoProduct);
            $attributeValue = $magentoProduct->getAttributeValue($attributeCode);
            $this->processNotFoundAttributes((string)__('Size Chart'), $magentoProduct);

            if (empty($attributeValue)) {
                return '';
            }

            return $attributeValue;
        }

        return '';
    }
}
