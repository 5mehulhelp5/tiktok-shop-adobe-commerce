<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\DataBuilder;

class Factory
{
    private const ALLOWED_BUILDERS = [
        Brand::NICK => Brand::class,
        Categories::NICK => Categories::class,
        CertificateImage::NICK => CertificateImage::class,
        Description::NICK => Description::class,
        Images::NICK => Images::class,
        ProductPackage::NICK => ProductPackage::class,
        VariantSku::NICK => VariantSku::class,
        SizeChart::NICK => SizeChart::class,
        Title::NICK => Title::class,
        Compliance::NICK => Compliance::class,
        GlobalProduct::NICK => GlobalProduct::class,
        NonSalableFlag::NICK => NonSalableFlag::class,
    ];

    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(
        string $nick,
        \M2E\TikTokShop\Model\Product $product,
        int $action,
        ?\M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Configurator $configurator = null,
        ?\M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\VariantSettings $variantSettings = null,
        array $params = [],
        array $cacheData = []
    ): AbstractDataBuilder {
        if (!isset(self::ALLOWED_BUILDERS[$nick])) {
            throw new \M2E\TikTokShop\Model\Exception\Logic(sprintf('Unknown builder - %s', $nick));
        }

        /** @var AbstractDataBuilder $builder */
        $builder = $this->objectManager->create(self::ALLOWED_BUILDERS[$nick]);

        if ($configurator === null) {
            $configurator = new \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\Configurator();
        }

        if ($variantSettings === null) {
            $variantSettings = \M2E\TikTokShop\Model\TikTokShop\Listing\Product\Action\VariantSettings::createAddActionStubForSimpleProduct($product);
        }

        $builder->init($product, $configurator, $variantSettings, $action, $params, $cacheData);

        return $builder;
    }
}
