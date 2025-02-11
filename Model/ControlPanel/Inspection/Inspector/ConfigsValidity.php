<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\ControlPanel\Inspection\Inspector;

use M2E\TikTokShop\Helper\Module\Database\Structure as DatabaseStructure;
use M2E\TikTokShop\Model\ControlPanel\Inspection\InspectorInterface;
use M2E\TikTokShop\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;
use Magento\Backend\Model\UrlInterface;

class ConfigsValidity implements InspectorInterface
{
    private DatabaseStructure $databaseStructure;
    private UrlInterface $urlBuilder;
    private IssueFactory $issueFactory;
    private \M2E\TikTokShop\Model\ResourceModel\Config $configResource;
    private \M2E\TikTokShop\Model\Connector\Client\Single $serverClient;

    public function __construct(
        \M2E\TikTokShop\Model\ResourceModel\Config $configResource,
        DatabaseStructure $databaseStructure,
        UrlInterface $urlBuilder,
        IssueFactory $issueFactory,
        \M2E\TikTokShop\Model\Connector\Client\Single $serverClient
    ) {
        $this->serverClient = $serverClient;
        $this->configResource = $configResource;
        $this->databaseStructure = $databaseStructure;
        $this->urlBuilder = $urlBuilder;
        $this->issueFactory = $issueFactory;
    }

    public function process(): array
    {
        $issues = [];

        try {
            $responseData = $this->getDiff();
        } catch (\Exception $exception) {
            $issues[] = $this->issueFactory->create($exception->getMessage());

            return $issues;
        }

        $configTableName = $this->databaseStructure->getTableNameWithoutPrefix(
            $this->configResource->getMainTable()
        );
        if (!isset($responseData['configs_info']) || !isset($responseData['configs_info'][$configTableName])) {
            $issues[] = $this->issueFactory->create('No info for this TikTokShop version');

            return $issues;
        }
        $difference = $this->getSnapshot($responseData['configs_info']);

        if (!empty($difference)) {
            $issues[] = $this->issueFactory->create(
                'Wrong configs structure validity',
                $this->renderMetadata($difference)
            );
        }

        return $issues;
    }

    private function getDiff(): array
    {
        $command = new \M2E\TikTokShop\Model\TikTokShop\Connector\System\Config\GetInfoCommand();
        /** @var \M2E\TikTokShop\Model\Connector\Response $response */
        $response = $this->serverClient->process($command);

        return $response->getResponseData();
    }

    private function getSnapshot($data): array
    {
        $currentData = [];

        foreach ($data as $tableName => $configInfo) {
            $currentData[$tableName] = $this->databaseStructure->getConfigSnapshot($tableName);
        }

        $differences = [];

        foreach ($data as $tableName => $configInfo) {
            foreach ($configInfo as $codeHash => $item) {
                if (array_key_exists($codeHash, $currentData[$tableName])) {
                    continue;
                }

                $differences[] = [
                    'table' => $tableName,
                    'item' => $item,
                    'solution' => 'insert',
                ];
            }
        }

        return $differences;
    }

    private function renderMetadata($data): string
    {
        $html = <<<HTML
<table style="width: 100%;">
    <tr>
        <th style="width: 200px">Group</th>
        <th style="width: 200px">Key</th>
        <th style="width: 150px">Value</th>
        <th style="width: 50px">Action</th>
    </tr>
HTML;

        foreach ($data as $index => $row) {
            $url = $this->urlBuilder->getUrl(
                '*/controlPanel_database/addTableRow',
                [
                    'table' => $row['table'],
                ]
            );

            $actionWord = 'Insert';
            $styles = '';
            $onclickAction = <<<JS
var elem = jQuery(this);

new jQuery.ajax({
    url: '{$url}',
    method: 'get',
    data: elem.parents('tr').find('form').serialize(),
    success: function(transport) {
        elem.parents('tr').remove();
    }
});
JS;
            $group = $row['item']['group'] === null ? 'null' : $row['item']['group'];
            $key = $row['item']['key'] === null ? 'null' : $row['item']['key'];
            $value = $row['item']['value'] === null ? 'null' : $row['item']['value'];

            $html .= <<<HTML
<tr>
    <td>{$row['item']['group']}</td>
    <td>{$row['item']['key']}</td>
    <td>
        <form style="margin-bottom: 0; display: block; height: 20px">
            <input type="text"   name="value_value" value="{$value}">
            <input type="checkbox" name="cells[]" value="group" style="display: none;" checked="checked">
            <input type="checkbox" name="cells[]" value="key" style="display: none;" checked="checked">
            <input type="checkbox" name="cells[]" value="value" style="display: none;" checked="checked">
            <input type="hidden" name="value_group" value="{$group}">
            <input type="hidden" name="value_key" value="{$key}">
        </form>
    </td>
    <td>
        <a id="insert_id_{$index}" style= "{$styles}"
           onclick="{$onclickAction}" href="javascript:void(0);">{$actionWord}</a>
    </td>
</tr>
HTML;
        }

        $html .= '</table>';

        return $html;
    }
}
