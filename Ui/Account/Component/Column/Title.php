<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Ui\Account\Component\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Title extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$row) {
            $accountTitle = $row['title'];
            $sellerName = $row['seller_name'];
            $regionNames = $row['shop_region_codes'] ? $this->getRegionNames($row['shop_region_codes']) : '';

            $html = sprintf('<p>%s</p>', \M2E\TikTokShop\Helper\Data::escapeHtml($accountTitle));
            $html .= $this->renderLine((string)\__('Seller Name'), $sellerName);
            $html .= $this->renderLine((string)\__('Region(-s)'), $regionNames);

            $row['title'] = $html;
        }

        return $dataSource;
    }

    private function renderLine(string $label, string $value): string
    {
        return sprintf(
            '<p style="margin: 0"><b>%s</b>: %s</p>',
            $label,
            \M2E\TikTokShop\Helper\Data::escapeHtml($value)
        );
    }

    private function getRegionNames(string $regionCodes): string
    {
        $result = [];
        $regions = explode(';', $regionCodes);
        foreach ($regions as $regionCode) {
            $result[] = \M2E\TikTokShop\Model\Shop::getRegionNameByCode($regionCode);
        }

        return implode(', ', $result);
    }
}
