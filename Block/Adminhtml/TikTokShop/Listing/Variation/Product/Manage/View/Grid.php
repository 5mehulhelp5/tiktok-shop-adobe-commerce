<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\TikTokShop\Listing\Variation\Product\Manage\View;

use M2E\TikTokShop\Model\Product;

class Grid extends \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private \Magento\Framework\Locale\CurrencyInterface $localeCurrency;
    private Product $listingProduct;
    private \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku $variantSkuResource;
    private \M2E\TikTokShop\Model\Magento\ProductFactory $magentoProductFactory;
    private array $filterByIds;

    public function __construct(
        Product $listingProduct,
        \M2E\TikTokShop\Model\Magento\ProductFactory $magentoProductFactory,
        \M2E\TikTokShop\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku $variantSkuResource,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $filterByIds = [],
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->localeCurrency = $localeCurrency;
        $this->listingProduct = $listingProduct;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->variantSkuResource = $variantSkuResource;
        $this->magentoProductFactory = $magentoProductFactory;
        $this->filterByIds = $filterByIds;
    }

    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();

        $this->setId('ttsVariationProductManageGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn(
            'variant_sku',
            \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_ID
        );

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            ['variant_sku' => $this->variantSkuResource->getMainTable()],
            sprintf(
                '%s = entity_id',
                \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_MAGENTO_PRODUCT_ID
            ),
            [
                'id' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_ID,
                'variant_status' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_STATUS,
                'product_id' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_PRODUCT_ID,
                'magento_product_id' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_MAGENTO_PRODUCT_ID,
                'sku_id' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_SKU_ID,
                'online_sku' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_ONLINE_SKU,
                'online_price' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_ONLINE_PRICE,
                'online_qty' => \M2E\TikTokShop\Model\ResourceModel\Product\VariantSku::COLUMN_ONLINE_QTY,
            ]
        );

        $collection->addFieldToFilter('product_id', ['eq' => $this->listingProduct->getId()]);

        if ($this->filterByIds !== []) {
            $collection->addFieldToFilter('id', ['in' => $this->filterByIds]);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('variation', [
            'header' => $this->__('Variation'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'name',
            'filter' => false,
            'frame_callback' => [$this, 'callbackColumnVariation'],
        ]);

        $this->addColumn('sku', [
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'sku',
            'filter_index' => 'sku',
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('Available QTY'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnQty'],
        ]);

        $this->addColumn('price', [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
        ]);

        $this->addColumn('variant_status', [
            'header' => $this->__('Status'),
            'align' => 'right',
            'width' => '40px',
            'type' => 'options',
            'index' => 'variant_status',
            'filter_index' => 'variant_status',
            'options' => [
                Product::STATUS_NOT_LISTED => Product::getStatusTitle(Product::STATUS_NOT_LISTED),
                Product::STATUS_LISTED => Product::getStatusTitle(Product::STATUS_LISTED),
                Product::STATUS_INACTIVE => Product::getStatusTitle(Product::STATUS_INACTIVE),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnVariation($value, $row, $column, $isExport)
    {
        $magentoProductId = $row->getData('magento_product_id');

        $childProduct = $this->magentoProductFactory->createByProductId((int)$magentoProductId);

        $attributesHtml = '';
        foreach ($this->getParentAttributes() as $attribute) {
            $attributesHtml .= sprintf(
                '<p><strong>%s</strong>: %s</p>',
                $attribute->getDefaultFrontendLabel(),
                $childProduct->getAttributeValue($attribute->getAttributeCode())
            );
        }

        $magentoProductUrl = $this->getUrl(
            'catalog/product/edit',
            [
                'id' => $magentoProductId,
                'store' => $this->listingProduct->getListing()->getStoreId(),
            ]
        );

        return sprintf(
            '<div class="m2e-variation-attributes"><a href="%s" target="_blank">%s</a></div>',
            $magentoProductUrl,
            $attributesHtml
        );
    }

    public function callbackColumnQty($value, $row, $column, $isExport)
    {
        if (
            $row->getData('status') == Product::STATUS_NOT_LISTED &&
            ($value === null || $value === '')
        ) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if (
            $row->getData('status') == Product::STATUS_NOT_LISTED &&
            ($value === null || $value === '')
        ) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>';
        }

        $currency = $this->listingProduct->getShop()->getCurrencyCode();

        $priceStr = $this->localeCurrency->getCurrency($currency)->toCurrency($value);

        return $priceStr;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $variationStatusText = $value;
        $variationStatusCode = $row->getData('variant_status');

        $colors = [
            Product::STATUS_NOT_LISTED => 'gray',
            Product::STATUS_LISTED => 'green',
            Product::STATUS_INACTIVE => 'red',
        ];

        return sprintf(
            '<span style="color: %s">%s</span>',
            $colors[$variationStatusCode] ?? 'gray',
            $variationStatusText
        );
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/tiktokshop_listing_variation_product_manage/getGridHtml', [
            'product_id' => $this->listingProduct->getId(),
            '_current' => true,
        ]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    /**
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute[]
     * @throws \M2E\TikTokShop\Model\Exception
     */
    private function getParentAttributes(): array
    {
        return $this
            ->listingProduct
            ->getMagentoProduct()
            ->getConfigurableAttributes();
    }
}
