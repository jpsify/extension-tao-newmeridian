<?php
/** Copyright (c) 2020 (original work) Open Assessment Technologies SA; */

declare(strict_types=1);

namespace oat\taoNewMeridian\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoNewMeridian\scripts\install\SetupItemBank;

final class Version202010161304592784_taoNewMeridian extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration for IMS Common Core State Standard trees, Evidence Statement lists, Task Model lists. Item Bank structure registered and properties configured.';
    }

    public function up(Schema $schema): void
    {
        $this->getSetupItemBankScript()->up();
    }

    public function down(Schema $schema): void
    {
        $this->getSetupItemBankScript()->down();
    }

    private function getSetupItemBankScript(): SetupItemBank
    {
        $script = new SetupItemBank();
        $script->setServiceLocator($this->getServiceLocator());

        return $script;
    }
}
