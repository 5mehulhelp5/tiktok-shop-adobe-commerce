<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Cron;

abstract class AbstractRunner
{
    public const MAX_MEMORY_LIMIT = 2048;

    private $previousStoreId = null;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \M2E\TikTokShop\Model\Cron\OperationHistory $operationHistory;
    private \M2E\TikTokShop\Model\Lock\Transactional\ManagerFactory $lockTransactionManagerFactory;
    private \M2E\TikTokShop\Helper\Module\Exception $exceptionHelper;
    private \M2E\TikTokShop\Helper\Magento $magentoHelper;
    private \M2E\TikTokShop\Helper\Module $moduleHelper;
    private \M2E\TikTokShop\Helper\Module\Maintenance $maintenanceHelper;
    private \M2E\TikTokShop\Model\Cron\OperationHistoryFactory $operationHistoryFactory;
    private \M2E\TikTokShop\Model\Cron\Strategy $strategy;
    private \M2E\Core\Helper\Client\MemoryLimit $memoryLimit;
    /** @var \M2E\TikTokShop\Model\Cron\Config */
    private Config $cronConfig;
    /** @var \M2E\TikTokShop\Model\Cron\Manager */
    private Manager $cronManager;

    public function __construct(
        \M2E\TikTokShop\Model\Cron\Config $cronConfig,
        \M2E\TikTokShop\Model\Cron\Manager $cronManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \M2E\TikTokShop\Model\Lock\Transactional\ManagerFactory $lockTransactionManagerFactory,
        \M2E\TikTokShop\Helper\Module\Exception $exceptionHelper,
        \M2E\TikTokShop\Helper\Magento $magentoHelper,
        \M2E\TikTokShop\Helper\Module $moduleHelper,
        \M2E\TikTokShop\Helper\Module\Maintenance $maintenanceHelper,
        \M2E\TikTokShop\Model\Cron\OperationHistoryFactory $operationHistoryFactory,
        \M2E\Core\Helper\Client\MemoryLimit $memoryLimit,
        \M2E\TikTokShop\Model\Cron\Strategy $strategy
    ) {
        $this->storeManager = $storeManager;
        $this->lockTransactionManagerFactory = $lockTransactionManagerFactory;
        $this->exceptionHelper = $exceptionHelper;
        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
        $this->maintenanceHelper = $maintenanceHelper;
        $this->operationHistoryFactory = $operationHistoryFactory;
        $this->strategy = $strategy;
        $this->memoryLimit = $memoryLimit;
        $this->cronConfig = $cronConfig;
        $this->cronManager = $cronManager;
    }

    abstract public function getNick(): string;

    abstract public function getInitiator(): int;

    public function process(): void
    {
        if (!$this->canProcess()) {
            return;
        }

        $transactionalManager = $this->lockTransactionManagerFactory->create('cron_runner');

        $transactionalManager->lock();

        if (!$this->canProcessRunner()) {
            return;
        }

        $this->initialize();
        $this->setLastAccess();

        if (!$this->isPossibleToRun()) {
            $this->deInitialize();
            $transactionalManager->unlock();

            return;
        }

        $this->setLastRun();
        $this->beforeStart();

        $transactionalManager->unlock();

        try {
            $strategy = $this->getStrategy();

            $strategy->initialize($this->getInitiator(), $this->getOperationHistory());

            $strategy->process();
        } catch (\Throwable $exception) {
            $this->getOperationHistory()->addContentData(
                'exceptions',
                [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            $this->exceptionHelper->process($exception);
        }

        $this->afterEnd();
        $this->deInitialize();
    }

    protected function getStrategy(): \M2E\TikTokShop\Model\Cron\Strategy
    {
        return $this->strategy;
    }

    protected function canProcess(): bool
    {
        if (!$this->magentoHelper->isInstalled()) {
            return false;
        }

        if ($this->maintenanceHelper->isEnabled()) {
            return false;
        }

        if ($this->moduleHelper->isDisabled()) {
            return false;
        }

        if ($this->cronConfig->isRunnerDisabled($this->getNick())) {
            return false;
        }

        return true;
    }

    protected function canProcessRunner(): bool
    {
        return $this->getNick() === $this->cronConfig->getActiveRunner();
    }

    private function initialize(): void
    {
        $this->previousStoreId = $this->storeManager->getStore()->getId();

        $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        $this->memoryLimit->set(self::MAX_MEMORY_LIMIT);
        $this->exceptionHelper->setFatalErrorHandler();
    }

    private function deInitialize(): void
    {
        if ($this->previousStoreId !== null) {
            $this->storeManager->setCurrentStore($this->previousStoreId);
            $this->previousStoreId = null;
        }
    }

    protected function setLastAccess(): void
    {
        $this->cronManager->setCronLastAccess();
    }

    protected function isPossibleToRun(): bool
    {
        if (!$this->moduleHelper->isReadyToWork()) {
            return false;
        }

        if (!$this->cronConfig->isEnabled()) {
            return false;
        }

        return true;
    }

    protected function setLastRun(): void
    {
        $this->cronManager->setCronLastRun();
    }

    // ---------------------------------------

    protected function beforeStart(): void
    {
        $this->getOperationHistory()->start(
            'cron_runner',
            null,
            $this->getInitiator(),
            $this->getOperationHistoryData()
        );
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd(): void
    {
        $this->getOperationHistory()->stop();
    }

    // ---------------------------------------

    protected function getOperationHistoryData(): array
    {
        return ['runner' => $this->getNick()];
    }

    public function getOperationHistory(): \M2E\TikTokShop\Model\Cron\OperationHistory
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->operationHistory ?? ($this->operationHistory = $this->operationHistoryFactory->create());
    }
}
