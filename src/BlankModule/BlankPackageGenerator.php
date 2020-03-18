<?php

namespace Del\Generator\BlankModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class BlankPackageGenerator extends FileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $moduleName, array $fields): bool
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $moduleNamespace = $nameSpace . '\\' . $moduleName;
        $name = strtolower($moduleName);
        $namespace = $file->addNamespace($moduleNamespace);

        $namespace->addUse('Barnacle\Container');
        $namespace->addUse('Barnacle\RegistrationInterface');

        $class = $namespace->addClass($moduleName . 'Package');
        $class->addImplement('Barnacle\RegistrationInterface');

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->setBody('');
        $method->addComment('@param Container $c');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $moduleName . '/' . $moduleName . 'Package.php', $code);

        return true;
    }
}