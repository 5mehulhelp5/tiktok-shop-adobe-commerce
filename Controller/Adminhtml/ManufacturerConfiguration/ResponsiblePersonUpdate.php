<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Controller\Adminhtml\ManufacturerConfiguration;

class ResponsiblePersonUpdate extends AbstractManufacturerConfiguration
{
    private \M2E\TikTokShop\Model\Template\Compliance\ComplianceService $complianceService;
    private \M2E\TikTokShop\Model\Account\Repository $accountRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Template\Compliance\ComplianceService $complianceService,
        \M2E\TikTokShop\Model\Account\Repository $accountRepository,
        \M2E\TikTokShop\Model\TikTokShop\Template\Manager $templateManager
    ) {
        parent::__construct($templateManager);

        $this->accountRepository = $accountRepository;
        $this->complianceService = $complianceService;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost('responsible_person');
        $accountId = (int)$post['account_id'];
        $account = $this->accountRepository->find($accountId);

        if ($account === null) {
            $this->setJsonContent([
                'result' => false,
                'messages' => ['Account Id is required'],
            ]);

            return $this->getResult();
        }

        $person = new \M2E\TikTokShop\Model\Channel\ResponsiblePerson(
            empty($post['responsible_person_id']) ? null : $post['responsible_person_id'],
            $post['name'],
            $post['email'],
            $post['country_code'],
            $post['local_number'],
            $post['address_1'],
            $post['postal_code'],
            $post['country'],
        );

        $errors = [];
        try {
            if ($person->id !== null) {
                $this->complianceService->updateResponsiblePerson($account, $person);
            } else {
                $this->complianceService->createResponsiblePerson($account, $person);
            }
        } catch (\M2E\TikTokShop\Model\Exception\Connection\UnableUpdateData $e) {
            $errors = [];
            foreach ($e->getMessageCollection()->getErrors() as $error) {
                $errors[] = $error->getText();
            }
        } catch (\M2E\Core\Model\Exception $e) {
            $errors = [$e->getMessage()];
        }

        $this->setJsonContent([
            'result' => empty($errors),
            'messages' => $errors,
        ]);

        return $this->getResult();
    }
}
