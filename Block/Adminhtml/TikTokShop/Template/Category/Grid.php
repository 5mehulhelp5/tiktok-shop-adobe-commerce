<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Block\Adminhtml\TikTokShop\Template\Category;

use M2E\TikTokShop\Model\Category\Dictionary;
use M2E\TikTokShop\Model\ResourceModel\Category\Dictionary\CollectionFactory as DictionaryCollectionFactory;

class Grid extends \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private DictionaryCollectionFactory $categoryDictionaryCollectionFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory;

    public function __construct(
        DictionaryCollectionFactory $categoryDictionaryCollectionFactory,
        \M2E\TikTokShop\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory,
        \M2E\TikTokShop\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->categoryDictionaryCollectionFactory = $categoryDictionaryCollectionFactory;
        $this->shopCollectionFactory = $shopCollectionFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('tikTokShopTemplateCategoryGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
        $this->setDefaultDir('asc');
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _prepareCollection()
    {
        $collection = $this->categoryDictionaryCollectionFactory->create();

        $collection->addFieldToFilter(
            \M2E\TikTokShop\Model\ResourceModel\Category\Dictionary::COLUMN_STATE,
            ['neq' => Dictionary::DRAFT_STATE]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'category_id',
            [
                'header' => __('Category ID'),
                'align' => 'center',
                'type' => 'text',
                'index' => 'category_id',
            ]
        );

        $this->addColumn(
            'path',
            [
                'header' => __('Title'),
                'align' => 'left',
                'type' => 'text',
                'escape' => true,
                'index' => 'path',
                'filter_condition_callback' => [$this, 'callbackFilterPath'],
                'frame_callback' => [$this, 'callbackColumnFilterPath'],
            ]
        );

        $this->addColumn(
            'shop_name',
            [
                'header' => __('Shop'),
                'align' => 'left',
                'type' => 'options',
                'width' => '100px',
                'index' => 'shop_id',
                'filter_condition_callback' => [$this, 'callbackFilterShop'],
                'frame_callback' => [$this, 'callbackColumnShop'],
                'options' => $this->getShopIdOptions(),
            ]
        );

        $this->addColumn(
            'total_attributes',
            [
                'header' => __('Attributes: Total'),
                'align' => 'left',
                'type' => 'text',
                'width' => '100px',
                'index' => 'total_product_attributes',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'used_attributes',
            [
                'header' => __('Attributes: Used'),
                'align' => 'left',
                'type' => 'text',
                'width' => '100px',
                'index' => 'used_product_attributes',
                'filter' => false,
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header' => __('Actions'),
                'align' => 'left',
                'width' => '70px',
                'type' => 'action',
                'index' => 'actions',
                'filter' => false,
                'sortable' => false,
                'renderer' => \M2E\TikTokShop\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
                //'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => '*/tiktokshop_category/view',
                            'params' => [
                                'dictionary_id' => '$id',
                            ],
                        ],
                        'field' => 'id',
                    ],
                ],
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Remove'),
                'url' => $this->getUrl('*/tiktokshop_category/delete'),
                'confirm' => __('Are you sure?'),
            ]
        );

        return parent::_prepareMassaction();
    }

    public function callbackColumnFilterPath($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            return '';
        }

        if (!$row->isCategoryValid()) {
            return sprintf(
                '%s <span style="color: #f00;">%s</span>',
                $row->getPath(),
                __('Invalid')
            );
        }

        return $row->getPath();
    }

    protected function callbackFilterPath($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.path LIKE ?', '%' . $value . '%');
    }

    /**
     * @param \M2E\TikTokShop\Model\Category\Dictionary $row
     */
    public function callbackColumnShop($value, $row, $column, $isExport)
    {
        return $row->getShop()->getShopNameWithRegion();
    }

    protected function callbackFilterShop($collection, $column): void
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.shop_id = ?', $value);
    }

    private function getShopIdOptions(): array
    {
        $collection = $this->shopCollectionFactory->create();
        $options = [];
        /** @var \M2E\TikTokShop\Model\Shop $item */
        foreach ($collection as $item) {
            $options[$item->getId()] = $item->getShopNameWithRegion();
        }

        return $options;
    }
}
