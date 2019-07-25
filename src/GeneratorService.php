<?php

declare(strict_types=1);

namespace Del\Generator;

use Del\Generator\EntityModule\ApiControllerGenerator;
use Del\Generator\EntityModule\CollectionGenerator;
use Del\Generator\EntityModule\ControllerGenerator;
use Del\Generator\EntityModule\EntityGenerator;
use Del\Generator\EntityModule\FormGenerator;
use Del\Generator\EntityModule\PackageGenerator;
use Del\Generator\EntityModule\RepositoryGenerator;
use Del\Generator\EntityModule\ServiceGenerator;
use Del\Generator\EntityModule\ViewGenerator;
use Exception;

class GeneratorService
{
    /** @var string $buildId */
    private $buildId;

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return string
     * @throws Exception
     */
    public function createEntityModule(string $nameSpace, string $entityName, array $fields): string
    {
        $this->createBuildFolders($entityName);
        $this->createEntity($nameSpace, $entityName, $fields);
        $this->createRepository($nameSpace, $entityName);
        $this->createCollection($nameSpace, $entityName);
        $this->createService($nameSpace, $entityName, $fields);
        $this->createForm($nameSpace, $entityName, $fields);
        $this->createApiController($nameSpace, $entityName, $fields);
        $this->createController($nameSpace, $entityName, $fields);
        $this->createView($nameSpace, $entityName, $fields);
        $this->createPackage($nameSpace, $entityName);

        return $this->buildId;
    }

    /**
     * @param string $entityName
     * @return bool
     * @throws Exception
     */
    private function createBuildFolders(string $entityName): bool
    {
        $unique = $this->buildId = uniqid('m', false);
        $folders = [
            'build/' . $unique,
            'build/' . $unique . '/' . $entityName,
            'build/' . $unique . '/' . $entityName . '/Collection',
            'build/' . $unique . '/' . $entityName . '/Controller',
            'build/' . $unique . '/' . $entityName . '/Entity',
            'build/' . $unique . '/' . $entityName . '/Form',
            'build/' . $unique . '/' . $entityName . '/Repository',
            'build/' . $unique . '/' . $entityName . '/Service',
            'build/' . $unique . '/' . $entityName . '/View',
            'build/' . $unique . '/' . $entityName . '/View/' . $entityName,
        ];

        foreach ($folders as $folder) {
            if (!mkdir($folder) && !is_dir($folder)) {
                throw new Exception('could not create ' . $folder);
            }
        }

        if (!file_exists('migrations')) {
            if (!mkdir('migrations') && !is_dir('migrations')) {
                throw new Exception('could not create migrations folder.');
            }
        }

        return true;
    }


    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createApiController(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new ApiControllerGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }


    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createView(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new ViewGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createEntity(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new EntityGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createRepository(string $nameSpace, string $entityName): bool
    {
        return (new RepositoryGenerator($this->buildId))->generateFile($nameSpace, $entityName, []);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createCollection(string $nameSpace, string $entityName): bool
    {
        return (new CollectionGenerator($this->buildId))->generateFile($nameSpace, $entityName, []);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createService(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new ServiceGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createPackage(string $nameSpace, string $entityName): bool
    {
        return (new PackageGenerator($this->buildId))->generateFile($nameSpace, $entityName, []);
    }


    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createForm(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new FormGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createController(string $nameSpace, string $entityName, array $fields): bool
    {
        return (new ControllerGenerator($this->buildId))->generateFile($nameSpace, $entityName, $fields);
    }
}