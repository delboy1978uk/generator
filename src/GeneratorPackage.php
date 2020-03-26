<?php

namespace Del\Generator;

use Barnacle\Container;
use Barnacle\RegistrationInterface;
use Bone\Console\CommandRegistrationInterface;
use Del\Generator\BlankModuleCommand;
use Del\Generator\ClearBuildsCommand;
use Del\Generator\GeneratorCommand;
use Del\Generator\MvcModuleCommand;
use Del\Generator\VendorPackageCommand;


class GeneratorPackage implements RegistrationInterface, CommandRegistrationInterface
{
    /**
     * @param Container $container
     * @return array
     */
    public function registerConsoleCommands(Container $container): array
    {
        $gen = new GeneratorCommand();
        $blank = new BlankModuleCommand();
        $mvc = new MvcModuleCommand();
        $clear = new ClearBuildsCommand();
        $vendor = new VendorPackageCommand();

        $gen->setName('generate:entity');
        $blank->setName('generate:blank');
        $mvc->setName('generate:mvc');
        $clear->setName('generate:clear-builds');
        $vendor->setName('generate:vendor');

        return [
            $gen, $blank, $mvc, $clear, $vendor
        ];
    }

    public function addToContainer(Container $c)
    {
    }
}
