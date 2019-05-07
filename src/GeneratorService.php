<?php

declare(strict_types=1);

namespace Del\Generator;

use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

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
        $this->createBuildFolders();
        $generated = $this->createEntity($nameSpace, $entityName, $fields);
        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($generated);
        file_put_contents('build/' . $this->buildId . '/src/Entity/' . $entityName . '.php', $code);

        return $this->buildId;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function createBuildFolders(): bool
    {
        $unique = $this->buildId = uniqid();
        $folders = [
            'build/' . $unique,
            'build/' . $unique . '/src',
            'build/' . $unique . '/src/Entity',
            'build/' . $unique . '/src/Service',
            'build/' . $unique . '/src/Repository',
            'build/' . $unique . '/src/Form',
            'build/' . $unique . '/src/Collection',
        ];

        foreach ($folders as $folder) {
            if (!mkdir($folder)) {
                throw new Exception('could not create ' . $folder);
            }
        }

        return true;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return PhpNamespace
     */
    private function createEntity(string $nameSpace, string $entityName, array $fields): PhpNamespace
    {
        $namespace = new PhpNamespace($nameSpace);
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $class = new ClassType($entityName);
        $class->addComment('@ORM\Entity');

        $id = $class->addProperty('id');
        $id->setVisibility('private');
        $id->addComment('@var int $id');
        $id->addComment('@ORM\Id');
        $id->addComment('@ORM\Column(type="integer")');
        $id->addComment('@ORM\GeneratedValue');

        $method = $class->addMethod('getId');
        $method->setBody('return $this->id;');
        $method->addComment('@return int');

        $method = $class->addMethod('setId');
        $method->addParameter('id');
        $method->addComment('@param int $id');
        $method->setBody('$this->id = $id;');


        $namespace->add($class);

        return $namespace;
    }
}