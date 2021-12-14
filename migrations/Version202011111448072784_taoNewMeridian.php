<?php
/** Copyright (c) 2020 (original work) Open Assessment Technologies SA; */

declare(strict_types=1);

namespace oat\taoNewMeridian\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoNewMeridian\scripts\install\SetupItemBank;

final class Version202011111448072784_taoNewMeridian extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Several changes were made to item bank setup source files, this migration reloads them.';
    }

    public function up(Schema $schema): void
    {
        $this->getSetupItemBankScript()->down();
        $this->getSetupItemBankScript()->up();
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'Any modifications to the tree/list records are discarded without a way to revert.'
        );
    }

    private function getSetupItemBankScript(): SetupItemBank
    {
        $script = new SetupItemBank();
        $script->setServiceLocator($this->getServiceLocator());

        return $script;
    }
}
