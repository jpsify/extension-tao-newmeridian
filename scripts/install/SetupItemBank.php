<?php
/** Copyright (c) 2020 (original work) Open Assessment Technologies SA; */

declare(strict_types=1);

namespace oat\taoNewMeridian\scripts\install;

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyRdf;
use oat\generis\model\WidgetRdf;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\TaoOntology;
use oat\taoBackOffice\model\tree\TreeService;
use tao_models_classes_ListService;

class SetupItemBank extends InstallAction
{
    public const PROPERTY_NODE_ORIGIN_ID = 'http://www.tao.lu/Ontologies/TAO.rdf#nodeOriginId';
    public const PROPERTY_GENERATED_BY = 'http://www.tao.lu/Ontologies/TAO.rdf#generatedBy';

    public const SOURCE_FILE_PATH = 'item_bank_structure';
    public const ELA_TREE_SOURCE = 'core_standard_ela.json';
    public const MATH_TREE_SOURCE = 'core_standard_math.json';
    public const IMS_TREE_MAP_SOURCE = 'ims_tree_map.json';
    public const EVIDENCE_STATEMENT_SOURCE = 'evidence_statements.json';
    public const EVIDENCE_STATEMENT_MAP_SOURCE = 'evidence_statement_map.json';
    public const TASK_MODEL_SOURCE = 'task_models.json';
    public const TASK_MODEL_MAP_SOURCE = 'task_model_map.json';
    public const ITEM_BANK_MAP_SOURCE = 'item_bank_map.json';

    /** @var array */
    private $imsTreeMap;

    /** @var array */
    private $evidenceStatementMap;

    /** @var array */
    private $taskModelMap;

    public function __invoke($params)
    {
        $this->up();
    }

    public function up(): void
    {
        $this->importStandardTrees();
        $this->importEvidenceStatements();
        $this->importTaskModels();
        $this->registerItemBankStructure();
    }

    public function down(): void
    {
        $this->deleteInstancesAndSubclasses(OntologyRdf::RDF_PROPERTY);
        $this->deleteInstancesAndSubclasses(TreeService::CLASS_URI);
        $this->deleteInstancesAndSubclasses(TaoOntology::CLASS_URI_ITEM);
        $this->deleteInstancesAndSubclasses(TaoOntology::CLASS_URI_LIST);
    }

    private function importStandardTrees(): void
    {
        foreach ($this->getImsTreeMap() as $treeKey => $treeDetails) {
            if ($treeDetails['tree'] === 'ELA') {
                $this->imsTreeMap[$treeKey]['uri'] = $this->importSubtree(
                    self::ELA_TREE_SOURCE,
                    $treeDetails['label'],
                    $treeDetails['subtrees']
                );
            }
            if ($treeDetails['tree'] === 'MATH') {
                $this->imsTreeMap[$treeKey]['uri'] = $this->importSubtree(
                    self::MATH_TREE_SOURCE,
                    $treeDetails['label'],
                    $treeDetails['subtrees']
                );
            }
        }
    }

    private function importSubtree(string $source, string $label, array $subtrees): string
    {
        $subClass = $this->getTreeService()->getRootClass()->createSubClass($label);
        $this->setGeneratedBy($subClass);

        $this->walkTree($this->getImsData($source), $subtrees, 0, $subClass);

        return $subClass->getUri();
    }

    private function walkTree(
        array $node,
        array $subtrees,
        int $level,
        core_kernel_classes_Class $root,
        string $parentId = null
    ): void {
        // matching labels in IMS standard trees are in 2nd or 3rd level
        if ((in_array($level, [1, 2]) && in_array($node['name'], $subtrees, true)) || !empty($parentId)) {
            $resource = $root->createInstance($node['name']);
            if (!empty($parentId)) {
                $resource->setPropertyValue(
                    new core_kernel_classes_Property(TreeService::PROPERTY_CHILD_OF),
                    $parentId
                );
            }
            $this->setNodeOriginId($resource, $node['identifier']);
            $this->setGeneratedBy($resource);
            $parentId = $resource->getUri();
        }

        $level++;
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->walkTree($child, $subtrees, $level, $root, $parentId);
            }
        }
    }

    private function importEvidenceStatements(): void
    {
        foreach ($this->getEvidenceStatementMap() as $listKey => $listDetails) {
            $this->evidenceStatementMap[$listKey]['uri'] = $this->createEvidenceStatementList($listDetails);
        }
    }

    private function createEvidenceStatementList(array $details): string
    {
        $list = $this->getListService()->createList(
            'Evidence Statements ' . $details['subject'] . ' ' . $details['grade']
        );
        $this->setGeneratedBy($list);

        foreach ($this->getEvidenceStatements() as $item) {
            if ($item['Grade'] === $details['grade'] && $item['Subject'] === $details['subject']) {
                $elementLabel = $item['Evidence Statement_Text'];
                if (strpos($elementLabel, $item['Evidence Statement']) !== 0) {
                    $elementLabel = $item['Evidence Statement'] . ' ' . $elementLabel;
                }
                $element = $this->getListService()->createListElement($list, $elementLabel);
                $this->setGeneratedBy($element);
            }
        }

        return $list->getUri();
    }

    private function importTaskModels(): void
    {
        foreach ($this->getTaskModelMap() as $listKey => $listDetails) {
            $this->taskModelMap[$listKey]['uri'] = $this->createTaskModelList($listDetails);
        }
    }

    private function createTaskModelList(array $details): string
    {
        $list = $this->getListService()->createList('Task Models ' . $details['subject'] . ' ' . $details['grade']);
        $this->setGeneratedBy($list);

        foreach ($this->getTaskModels() as $item) {
            if ($item['Grade'] == $details['grade'] && $item['Subject'] == $details['subject']) {
                $element = $this->getListService()->createListElement($list, $item['Task Model_Text']);
                $this->setGeneratedBy($element);
            }
        }

        return $list->getUri();
    }

    private function registerItemBankStructure(): void
    {
        $structure = $this->getItemBankStructureMap();
        foreach ($structure as $subclass) {
            $this->walkItemBank($subclass, 0, TaoOntology::CLASS_URI_ITEM);
        }
    }

    private function walkItemBank(array $subclass, int $level, string $parent): void
    {
        $itemSubClass = $this->findOrCreateItemBankSubclass($parent, $subclass['id']);

        if (isset($subclass['evidence_statement_list_key'])) {
            if (isset($this->evidenceStatementMap[$subclass['evidence_statement_list_key']]['uri'])) {
                $this->createListProperty(
                    'Evidence Statement',
                    $itemSubClass,
                    $this->evidenceStatementMap[$subclass['evidence_statement_list_key']]['uri'],
                    'ELA' === $this->evidenceStatementMap[$subclass['evidence_statement_list_key']]['subject']
                );
            }
        }

        if (isset($subclass['task_model_list_key'])) {
            if (isset($this->taskModelMap[$subclass['task_model_list_key']]['uri'])) {
                $this->createListProperty(
                    'Task Model',
                    $itemSubClass,
                    $this->taskModelMap[$subclass['task_model_list_key']]['uri'],
                    false
                );
            }
        }

        if (isset($subclass['ims_tree_key'])) {
            if (isset($this->imsTreeMap[$subclass['ims_tree_key']]['uri'])) {
                $this->createTreeProperty(
                    $itemSubClass,
                    $this->imsTreeMap[$subclass['ims_tree_key']]['uri']
                );
            }
        }

        $level++;
        if (isset($subclass['children'])) {
            foreach ($subclass['children'] as $child) {
                $this->walkItemBank($child, $level, $itemSubClass->getUri());
            }
        }
    }

    private function createTreeProperty(core_kernel_classes_Class $itemClass, string $treeUri): void
    {
        $propertyResource = $itemClass->createProperty('Common Core State Standard ID');
        $propertyResource->setRange(new core_kernel_classes_Class($treeUri));
        $propertyResource->setPropertyValue(
            new core_kernel_classes_Property(WidgetRdf::PROPERTY_WIDGET),
            \tao_helpers_form_elements_Treebox::WIDGET_ID
        );
        $propertyResource->setMultiple(true);
        $this->setGeneratedBy($propertyResource);
    }

    private function createListProperty(
        string $label,
        core_kernel_classes_Class $itemClass,
        string $listUri,
        bool $isMultiple
    ): void {
        $propertyResource = $itemClass->createProperty($label);
        $propertyResource->setRange(new core_kernel_classes_Class($listUri));
        $propertyResource->setMultiple($isMultiple);
        if ($isMultiple) {
            $propertyResource->setPropertyValue(
                new core_kernel_classes_Property(WidgetRdf::PROPERTY_WIDGET),
                \tao_helpers_form_elements_Checkbox::WIDGET_ID
            );
        } else {
            $propertyResource->setPropertyValue(
                new core_kernel_classes_Property(WidgetRdf::PROPERTY_WIDGET),
                \tao_helpers_form_elements_Radiobox::WIDGET_ID
            );
        }
        $this->setGeneratedBy($propertyResource);
    }

    private function findOrCreateItemBankSubclass(string $parent, string $label): core_kernel_classes_Class
    {
        $parentClass = new core_kernel_classes_Class($parent);
        $foundSubclass = null;
        foreach ($parentClass->getSubClasses() as $uri => $subclass) {
            if ($subclass->getLabel() == $label) {
                $foundSubclass = $subclass;
                break;
            }
        }
        if (!$foundSubclass) {
            $foundSubclass = $parentClass->createSubClass($label);
            $this->setGeneratedBy($foundSubclass);
        }

        return $foundSubclass;
    }

    private function deleteInstancesAndSubclasses(string $uri): void
    {
        $root = new core_kernel_classes_Class($uri);
        $resources = $root->searchInstances(
            [self::PROPERTY_GENERATED_BY => addslashes(__CLASS__)],
            ['recursive' => true]
        );
        if (!empty($resources)) {
            $root->deleteInstances($resources);
        }
        foreach ($root->getSubClasses(true) as $subClass) {
            $migrationId = $subClass->getOnePropertyValue(
                new core_kernel_classes_Property(self::PROPERTY_GENERATED_BY)
            );
            if ($migrationId == __CLASS__) {
                $subClass->delete();
            }
        }
    }

    private function setGeneratedBy(core_kernel_classes_Resource $resource): void
    {
        $resource->setPropertyValue(
            new core_kernel_classes_Property(self::PROPERTY_GENERATED_BY),
            __CLASS__
        );
    }

    private function setNodeOriginId(core_kernel_classes_Resource $resource, string $identifier): void
    {
        $resource->setPropertyValue(
            new core_kernel_classes_Property(self::PROPERTY_NODE_ORIGIN_ID),
            $identifier
        );
    }

    private function getListService(): tao_models_classes_ListService
    {
        return tao_models_classes_ListService::singleton();
    }

    private function getTreeService(): TreeService
    {
        return TreeService::singleton();
    }

    private function getItemBankStructureMap(): array
    {
        return json_decode(
            file_get_contents(__DIR__
                . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                . DIRECTORY_SEPARATOR . self::ITEM_BANK_MAP_SOURCE
            ),
            true
        );
    }

    private function getImsTreeMap(): array
    {
        if (!isset($this->imsTreeMap)) {
            $this->imsTreeMap = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . self::IMS_TREE_MAP_SOURCE
                ),
                true
            );
        }

        return $this->imsTreeMap;
    }

    private function getEvidenceStatementMap(): array
    {
        if (!isset($this->evidenceStatementMap)) {
            $this->evidenceStatementMap = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . self::EVIDENCE_STATEMENT_MAP_SOURCE
                ),
                true
            );
        }

        return $this->evidenceStatementMap;
    }

    private function getTaskModelMap(): array
    {
        if (!isset($this->taskModelMap)) {
            $this->taskModelMap = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . self::TASK_MODEL_MAP_SOURCE
                ),
                true
            );
        }

        return $this->taskModelMap;
    }

    private function getEvidenceStatements(): array
    {
        if (!isset($this->evidenceStatementFile)) {
            $this->evidenceStatementFile = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . self::EVIDENCE_STATEMENT_SOURCE
                ),
                true
            );
        }

        return $this->evidenceStatementFile;
    }

    private function getImsData(string $source): array
    {
        if (!isset($this->imsFile[$source])) {
            $this->imsFile[$source] = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . $source
                ),
                true
            );
        }

        return $this->imsFile[$source];
    }

    private function getTaskModels(): array
    {
        if (!isset($this->taskModelFile)) {
            $this->taskModelFile = json_decode(
                file_get_contents(__DIR__
                    . DIRECTORY_SEPARATOR . self::SOURCE_FILE_PATH
                    . DIRECTORY_SEPARATOR . self::TASK_MODEL_SOURCE
                ),
                true
            );
        }

        return $this->taskModelFile;
    }
}
