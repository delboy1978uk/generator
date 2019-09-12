<?php

declare(strict_types=1);

namespace Del\Generator;

use Del\Generator\BlankModule\BlankPackageGenerator;
use Del\Generator\EntityModule\ApiControllerGenerator;
use Del\Generator\EntityModule\CollectionGenerator;
use Del\Generator\EntityModule\ControllerGenerator;
use Del\Generator\EntityModule\EntityGenerator;
use Del\Generator\EntityModule\FormGenerator;
use Del\Generator\EntityModule\PackageGenerator;
use Del\Generator\EntityModule\RepositoryGenerator;
use Del\Generator\EntityModule\ServiceGenerator;
use Del\Generator\EntityModule\ViewGenerator;
use Del\Generator\MvcModule\ApiControllerGenerator as MvcApiControllerGenerator;
use Del\Generator\MvcModule\ControllerGenerator as MvcControllerGenerator;
use Del\Generator\MvcModule\PackageGenerator as MvcPackageGenerator;
use Del\Generator\MvcModule\ViewGenerator as MvcViewGenerator;
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
     * @param string $nameSpace
     * @param string $entityName
     * @param bool $composerPackage
     * @return string
     * @throws Exception
     */
    public function createBlankModule(string $nameSpace, string $moduleName)
    {
        $this->createBlankBuildFolder($moduleName);
        $this->createBlankPackage($nameSpace, $moduleName);

        return $this->buildId;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param bool $composerPackage
     * @return string
     * @throws Exception
     */
    public function createMvcModule(string $nameSpace, string $moduleName)
    {
        $this->createMvcBuildFolders($moduleName);
        $this->createMvcPackage($nameSpace, $moduleName);

        return $this->buildId;
    }

    /**
     * @param string $nameSpace
     * @param string $moduleName
     * @return bool
     */
    private function createBlankPackage(string $nameSpace, string $moduleName): bool
    {
        return (new BlankPackageGenerator($this->buildId))->generateFile($nameSpace, $moduleName, []);
    }

    /**
     * @param string $nameSpace
     * @param string $moduleName
     * @return bool
     */
    private function createMvcPackage(string $nameSpace, string $moduleName): bool
    {
        (new MvcApiControllerGenerator($this->buildId))->generateFile($nameSpace, $moduleName, []);
        (new MvcControllerGenerator($this->buildId))->generateFile($nameSpace, $moduleName, []);
        (new MvcPackageGenerator($this->buildId))->generateFile($nameSpace, $moduleName, []);
        (new MvcViewGenerator($this->buildId))->generateFile($nameSpace, $moduleName, []);

        return true;
    }

    /**
     * @return string
     */
    private function generateBuildId(): string
    {
        $this->buildId = uniqid('_gen', false);

        return $this->buildId;
    }

    /**
     * @param string $entityName
     * @return bool
     * @throws Exception
     */
    private function createBlankBuildFolder(string $moduleName): bool
    {
        $unique = $this->generateBuildId();
        $folders = [
            'build/' . $unique,
            'build/' . $unique . '/' . $moduleName,
        ];

        foreach ($folders as $folder) {
            if (!mkdir($folder) && !is_dir($folder)) {
                throw new Exception('could not create ' . $folder);
            }
        }

        return true;
    }



    /**
     * @param string $entityName
     * @return bool
     * @throws Exception
     */
    private function createMvcBuildFolders(string $moduleName): bool
    {
        $unique = $this->generateBuildId();
        $folders = [
            'build/' . $unique,
            'build/' . $unique . '/' . $moduleName,
            'build/' . $unique . '/' . $moduleName . '/Controller',
            'build/' . $unique . '/' . $moduleName . '/View',
            'build/' . $unique . '/' . $moduleName . '/View/' . $moduleName,
        ];

        foreach ($folders as $folder) {
            if (!mkdir($folder) && !is_dir($folder)) {
                throw new Exception('could not create ' . $folder);
            }
        }

        return true;
    }

    /**
     * @param string $entityName
     * @return bool
     * @throws Exception
     */
    private function createBuildFolders(string $entityName): bool
    {
        $unique = $this->generateBuildId();
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