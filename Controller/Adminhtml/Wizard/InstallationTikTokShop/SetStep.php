<?php

namespace M2E\TikTokShop\Controller\Adminhtml\Wizard\InstallationTikTokShop;

use M2E\TikTokShop\Controller\Adminhtml\Wizard\InstallationTikTokShop;

class SetStep extends Installation
{
    public function execute()
    {
        return $this->setStepAction();
    }
}
