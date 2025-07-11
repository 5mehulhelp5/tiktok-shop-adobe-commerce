<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\TikTokShop\Listing\Edit;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Warehouse extends \M2E\TikTokShop\Controller\Adminhtml\TikTokShop\AbstractMain implements HttpGetActionInterface
{
    private \M2E\TikTokShop\Model\Listing\Repository $listingRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Listing\Repository $listingRepository,
        \M2E\TikTokShop\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->listingRepository = $listingRepository;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['id'])) {
            return $this->getResponse()->setBody(__('You should provide correct parameters.'));
        }

        $listing = $this->listingRepository->get((int)$params['id']);

        $this->setAjaxContent(
            $this->getLayout()->createBlock(
                \M2E\TikTokShop\Block\Adminhtml\Listing\Edit\Warehouse::class,
                '',
                ['listing' => $listing]
            )
        );

        return $this->getResult();
    }
}
