<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Order\AddressParser;

class US extends \M2E\TikTokShop\Model\TikTokShop\Order\BaseAddressParser
{
    public function getCity(): string
    {
        foreach ($this->getDistricts() as $district) {
            if ($district['level'] === 'City') {
                return $district['name'];
            }
        }

        return '';
    }

    public function getState(): string
    {
        foreach ($this->getDistricts() as $district) {
            if ($district['level'] === 'State') {
                return $district['name'];
            }
        }

        return '';
    }

    public function getStreetLines(): array
    {
        $streetLines = parent::getStreetLines();
        $streetLines[1] = !empty($streetLines[1])
            ? $streetLines[1] .  ', ' . $this->getCounty()
            : $this->getCounty();

        return $streetLines;
    }

    public function getCounty(): string
    {
        foreach ($this->getDistricts() as $district) {
            if ($district['level'] === 'County') {
                return $district['name'];
            }
        }

        return '';
    }
}
