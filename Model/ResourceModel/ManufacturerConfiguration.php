<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\ResourceModel;

class ManufacturerConfiguration extends \M2E\TikTokShop\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_MANUFACTURER_ID = 'manufacturer_id';
    public const COLUMN_RESPONSIBLE_PERSON_IDS = 'responsible_person_ids';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct(): void
    {
        $this->_init(
            \M2E\TikTokShop\Helper\Module\Database\Tables::TABLE_NAME_MANUFACTURER_CONFIGURATION,
            self::COLUMN_ID
        );
    }
}
