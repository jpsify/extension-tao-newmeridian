<?php
/** Copyright (c) 2020 (original work) Open Assessment Technologies SA; */

declare(strict_types=1);

namespace oat\taoNewMeridian\scripts\update;

use common_ext_ExtensionUpdater;

class Updater extends common_ext_ExtensionUpdater
{
    /**
     * Update extension version by version
     * @param string $initialVersion
     */
    public function update($initialVersion)
    {
        //Updater files are deprecated. Please use migrations.
        //See: https://github.com/oat-sa/generis/wiki/Tao-Update-Process

        $this->setVersion($this->getExtension()->getManifest()->getVersion());
    }
}
