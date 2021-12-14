<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoNewMeridian\scripts\install;

use oat\oatbox\reporting\Report;
use oat\oatbox\extension\InstallAction;
use oat\taoQtiItem\model\qti\metadata\MetadataService;
use oat\taoQtiItem\model\qti\metadata\importer\MetadataImporter;
use oat\taoQtiItem\model\qti\metadata\guardians\ItemMetadataGuardian;

class AddMetadataGuardian extends InstallAction
{
    public function __invoke($params = [])
    {
        $this->configureMetadataGuardian();
        $this->registerMetadataGuardian();

        return Report::createSuccess('New Meridian Metadata Guardian configured and registered.');
    }

    private function configureMetadataGuardian(): void
    {
        $metadataGuardian = $this->getServiceManager()->get(ItemMetadataGuardian::SERVICE_ID);
        $metadataGuardian->setOptions([
            ItemMetadataGuardian::OPTION_EXPECTED_PATH => [
                'http://ltsc.ieee.org/xsd/LOM#lom',
                'http://www.w3.org/2000/01/rdf-schema#label',
            ],
            ItemMetadataGuardian::OPTION_PROPERTY_URI => 'http://www.w3.org/2000/01/rdf-schema#label',
        ]);
        $this->getServiceManager()->register(ItemMetadataGuardian::SERVICE_ID, $metadataGuardian);
    }

    private function registerMetadataGuardian(): void
    {
        $this->getMetadataService()->getImporter()->register(
            MetadataImporter::GUARDIAN_KEY,
            ItemMetadataGuardian::class
        );
    }

    private function getMetadataService(): MetadataService
    {
        return $this->getServiceManager()->get(MetadataService::SERVICE_ID);
    }
}
