<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Order\AddressParser;

class GB extends \M2E\TikTokShop\Model\TikTokShop\Order\BaseAddressParser
{
    public function getCity(): string
    {
        foreach ($this->getDistricts() as $district) {
            if ($district['level'] === 'county') {
                return $district['name'];
            }
        }

        return '';
    }

    public function getState(): string
    {
        foreach ($this->getDistricts() as $district) {
            if ($district['level'] === 'country') {
                return $district['name'];
            }
        }

        return '';
    }
}
