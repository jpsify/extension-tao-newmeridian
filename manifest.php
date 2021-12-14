<?php
/** Copyright (c) 2020 (original work) Open Assessment Technologies SA; */

use oat\tao\model\accessControl\func\AccessRule;
use oat\taoNewMeridian\scripts\install\SetupItemBank;
use oat\taoNewMeridian\scripts\update\Updater;
use oat\taoNewMeridian\scripts\install\AddMetadataGuardian;

return [
    'name' => 'taoNewMeridian',
    'label' => 'New Meridian',
    'description' => 'Custom code for New Meridian',
    'license' => 'proprietary',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoNewMeridianManager',
    'acl' => [
        [AccessRule::GRANT, 'http://www.tao.lu/Ontologies/generis.rdf#taoNewMeridianManager', ['ext'=>'taoNewMeridian']],
    ],
    'install' => [
        'php' => [
            SetupItemBank::class,
            AddMetadataGuardian::class,
        ],
    ],
    'uninstall' => [],
    'update' => Updater::class,
    'routes' => [
        '/taoNewMeridian' => 'oat\\taoNewMeridian\\controller'
    ],
    'constants' => [
        'DIR_VIEWS' => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
        'BASE_URL' => ROOT_URL . 'taoNewMeridian/',
    ]
];
