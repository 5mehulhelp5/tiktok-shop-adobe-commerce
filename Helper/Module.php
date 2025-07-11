<?php

namespace M2E\TikTokShop\Helper;

use Magento\Framework\Component\ComponentRegistrar;

class Module
{
    public const IDENTIFIER = 'M2E_TikTokShop';

    public const MESSAGE_TYPE_NOTICE = 0;
    public const MESSAGE_TYPE_ERROR = 1;
    public const MESSAGE_TYPE_WARNING = 2;
    public const MESSAGE_TYPE_SUCCESS = 3;

    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_DEVELOPMENT = 'development';

    protected \M2E\TikTokShop\Model\Config\Manager $config;
    protected \M2E\TikTokShop\Model\Registry\Manager $registry;
    protected \Magento\Framework\App\ResourceConnection $resourceConnection;
    protected ComponentRegistrar $componentRegistrar;
    protected \Magento\Backend\Model\UrlInterface $urlBuilder;
    protected View\TikTokShop $viewHelper;
    private Data\Cache\Runtime $runtimeCache;
    private Data\Cache\Permanent $permanentCache;
    private Magento $magentoHelper;
    private \M2E\Core\Helper\Client $clientHelper;
    private \M2E\TikTokShop\Model\Module $module;
    private \M2E\TikTokShop\Model\Module\Environment $moduleEnv;

    public function __construct(
        \M2E\TikTokShop\Model\Module $module,
        \M2E\TikTokShop\Model\Module\Environment $moduleEnv,
        \M2E\TikTokShop\Model\Config\Manager $config,
        \M2E\TikTokShop\Model\Registry\Manager $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \M2E\TikTokShop\Helper\View\TikTokShop $viewHelper,
        \M2E\TikTokShop\Helper\Data\Cache\Runtime $runtimeCache,
        \M2E\TikTokShop\Helper\Data\Cache\Permanent $permanentCache,
        \M2E\TikTokShop\Helper\Magento $magentoHelper,
        \M2E\Core\Helper\Client $clientHelper
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->resourceConnection = $resourceConnection;
        $this->componentRegistrar = $componentRegistrar;
        $this->urlBuilder = $urlBuilder;
        $this->viewHelper = $viewHelper;
        $this->runtimeCache = $runtimeCache;
        $this->permanentCache = $permanentCache;
        $this->magentoHelper = $magentoHelper;
        $this->clientHelper = $clientHelper;
        $this->module = $module;
        $this->moduleEnv = $moduleEnv;
    }

    // ----------------------------------------

    /**
     * @return \M2E\TikTokShop\Model\Config\Manager
     * @deprecated use explicitly
     */
    public function getConfig(): \M2E\TikTokShop\Model\Config\Manager
    {
        return $this->config;
    }

    // ----------------------------------------

    public function isDisabled(): bool
    {
        return $this->module->isDisabled();
    }

    public function isReadyToWork(): bool
    {
        return $this->module->isReadyToWork();
    }

    public function areImportantTablesExist(): bool
    {
        return $this->module->areImportantTablesExist();
    }

    public function getEnvironment(): string
    {
        if ($this->moduleEnv->isProductionEnvironment()) {
            return self::ENVIRONMENT_PRODUCTION;
        }

        return self::ENVIRONMENT_DEVELOPMENT;
    }

    public function setEnvironment(string $env): void
    {
        if ($env === self::ENVIRONMENT_PRODUCTION) {
            $this->moduleEnv->enableProductionEnvironment();

            return;
        }

        $this->moduleEnv->enableDevelopmentEnvironment();
    }

    public function isProductionEnvironment(): bool
    {
        return $this->moduleEnv->isProductionEnvironment();
    }

    public function isDevelopmentEnvironment(): bool
    {
        return $this->moduleEnv->isDevelopmentEnvironment();
    }

    /**
     * @return bool|mixed
     */
    public function isStaticContentDeployed()
    {
        $staticContentValidationResult = $this->runtimeCache->getValue(__METHOD__);

        if ($staticContentValidationResult !== null) {
            return $staticContentValidationResult;
        }

        $result = true;

        $moduleDir = \M2E\TikTokShop\Helper\Module::IDENTIFIER . DIRECTORY_SEPARATOR;

        if (
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'css') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'fonts') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'images') ||
            !$this->magentoHelper->isStaticContentExists($moduleDir . 'js')
        ) {
            $result = false;
        }

        $this->runtimeCache->setValue(__METHOD__, $result);

        return $result;
    }

    public function getUpgradeMessages(): array
    {
        $messages = $this->registry->getValueFromJson('/upgrade/messages/');

        $messages = array_filter($messages, [$this, 'getMessagesFilterModuleMessages']);

        foreach ($messages as &$message) {
            preg_match_all('/%[\w\d]+%/', $message['text'], $placeholders);
            $placeholders = array_unique($placeholders[0]);

            foreach ($placeholders as $placeholder) {
                $key = substr(substr($placeholder, 1), 0, -1);
                if (!isset($message[$key])) {
                    continue;
                }

                if (!strripos($placeholder, 'url')) {
                    $message['text'] = str_replace($placeholder, $message[$key], $message['text']);
                    continue;
                }

                $message[$key] = $this->urlBuilder->getUrl(
                    $message[$key],
                    isset($message[$key . '_args']) ? $message[$key . '_args'] : null
                );

                $message['text'] = str_replace($placeholder, $message[$key], $message['text']);
            }
        }
        unset($message);

        return $messages;
    }

    /**
     * @param array $message
     *
     * @return bool
     */
    public function getMessagesFilterModuleMessages($message): bool
    {
        return isset($message['text'], $message['type']);
    }

    /**
     * @return array|mixed|string|string[]|null
     */
    public function getBaseRelativeDirectory()
    {
        return str_replace(
            $this->clientHelper->getBaseDirectory(),
            '',
            $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::IDENTIFIER)
        );
    }

    public function clearCache(): void
    {
        $this->permanentCache->removeAllValues();
    }

    public static function getExtensionTitle(): string
    {
        return 'M2E TikTok Shop Connect';
    }

    public static function getChannelTitle(): string
    {
        return 'TikTok Shop';
    }

    public static function getMenuRootNodeLabel(): string
    {
        return (string)__('TikTok Shop');
    }
}
