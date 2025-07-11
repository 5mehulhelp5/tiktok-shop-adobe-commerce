<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Order\Change;

class CancelProcessor
{
    private const MAX_CHANGE_FOR_PROCESS = 50;

    /** @var \M2E\TikTokShop\Model\Order\Change\Repository */
    private Repository $changeRepository;
    private \M2E\TikTokShop\Model\Order\Repository $orderRepository;
    private \M2E\TikTokShop\Model\TikTokShop\Connector\Order\Cancel\Processor $connectorProcessor;
    private \M2E\TikTokShop\Model\Order\CancellationRequest\Accept $buyerRequestAccept;

    public function __construct(
        Repository $changeRepository,
        \M2E\TikTokShop\Model\Order\Repository $orderRepository,
        \M2E\TikTokShop\Model\TikTokShop\Connector\Order\Cancel\Processor $connectorProcessor,
        \M2E\TikTokShop\Model\Order\CancellationRequest\Accept $buyerRequestAccept
    ) {
        $this->changeRepository = $changeRepository;
        $this->orderRepository = $orderRepository;
        $this->connectorProcessor = $connectorProcessor;
        $this->buyerRequestAccept = $buyerRequestAccept;
    }

    public function process(\M2E\TikTokShop\Model\Account $account): void
    {
        $changes = $this->changeRepository->findCanceledReadyForProcess(
            $account,
            self::MAX_CHANGE_FOR_PROCESS,
        );

        $reason = $account->getOrdersSettings()->getOrderCancelOnChannelReason();

        $processedOrders = [];
        foreach ($changes as $change) {
            if (!$account->getOrdersSettings()->isOrderCancelOrRefundOnChannelEnabled()) {
                $this->removeChange($change);

                continue;
            }

            if (isset($processedOrders[$change->getOrderId()])) {
                $this->removeChange($change);

                continue;
            }

            $processedOrders[$change->getOrderId()] = true;

            $order = $this->orderRepository->find($change->getOrderId());
            if ($order === null) {
                $this->removeChange($change);

                continue;
            }

            if ($order->isBuyerCancellationRequest()) {
                try {
                    $this->buyerRequestAccept->process($order, \M2E\Core\Helper\Data::INITIATOR_EXTENSION);
                } catch (\Throwable $exception) {
                    $this->removeChange($change);

                    continue;
                }

                $this->removeChange($change);
            }

            if (!$order->canCancel()) {
                $this->removeChange($change);

                continue;
            }

            $this->changeRepository->incrementAttemptCount([$change->getId()]);

            try {
                $notSuccessMessages = $this->connectorProcessor->process($order, $reason);
            } catch (\M2E\TikTokShop\Model\Order\Exception\UnableCancel $e) {
                $this->removeChange($change);

                continue;
            }

            $this->removeChange($change);

            if (empty($notSuccessMessages)) {
                $order->addSuccessLog('Order is canceled. Status is updated on TikTokShop.');

                continue;
            }

            foreach ($notSuccessMessages as $message) {
                if ($message->isError()) {
                    $order->addErrorLog(
                        'TikTokShop order was not cancelled. Reason: %msg%',
                        ['msg' => $message->getText()],
                    );
                } else {
                    $order->addWarningLog($message->getText());
                }
            }
        }
    }

    private function removeChange(\M2E\TikTokShop\Model\Order\Change $change): void
    {
        $this->changeRepository->delete($change);
    }
}
