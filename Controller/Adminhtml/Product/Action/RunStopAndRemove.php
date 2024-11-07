<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\Product\Action;

class RunStopAndRemove extends \M2E\TikTokShop\Controller\Adminhtml\TikTokShop\Listing\AbstractAction
{
    use ActionTrait;

    private \M2E\TikTokShop\Model\Product\Repository $productRepository;
    private \M2E\TikTokShop\Model\ResourceModel\Product\Grid\AllItems\ActionFilter $massActionFilter;
    /** @var \M2E\TikTokShop\Controller\Adminhtml\Product\Action\ActionService */
    private ActionService $actionService;

    public function __construct(
        \M2E\TikTokShop\Controller\Adminhtml\Product\Action\ActionService $actionService,
        \M2E\TikTokShop\Model\ResourceModel\Product\Grid\AllItems\ActionFilter $massActionFilter,
        \M2E\TikTokShop\Model\Product\Repository $productRepository
    ) {
        parent::__construct($productRepository);

        $this->productRepository = $productRepository;
        $this->massActionFilter = $massActionFilter;
        $this->actionService = $actionService;
    }

    public function execute()
    {
        $products = $this->productRepository->massActionSelectedProducts($this->massActionFilter);

        if ($this->isRealtimeAction($products)) {
            ['result' => $result] = $this->actionService->runStopAndRemove($products);
            if ($result === 'success') {
                $this->getMessageManager()->addSuccessMessage(
                    __('"Removing from TikTok Shop And Removing From Listing Selected Items" task has completed.'),
                );
            } else {
                $this->getMessageManager()->addErrorMessage(
                    __('"Removing from TikTok Shop And Removing From Listing Selected Items"as completed with errors.'),
                );
            }

            return $this->redirectToGrid();
        }

        $this->actionService->scheduleStopAndRemove($products);

        $this->getMessageManager()->addSuccessMessage(
            __('"Removing from TikTok Shop And Removing From Listing Selected Items" task has completed.'),
        );

        return $this->redirectToGrid();
    }
}
