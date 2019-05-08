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
        $this->createEntity($nameSpace, $entityName, $fields);

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
     * @return bool
     */
    private function createEntity(string $nameSpace, string $entityName, array $fields): bool
    {
        $namespace = new PhpNamespace($nameSpace);
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $class = new ClassType($entityName);
        $class->addComment('@ORM\Entity(repositoryClass="\\' . $nameSpace . '\Repository\\' . $entityName  . 'Repository")');

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

        $useDateTime = false;

        foreach ($fields as $fieldInfo) {

            $name = $fieldInfo['name'];
            $type = $fieldInfo['type'];

            switch ($type) {
                case 'bool':
                    $type = 'boolean';
                    $var = 'bool';
                    $typeHint = 'bool';
                    break;
                case 'varchar':
                    $type = 'string';
                    $var = 'string';
                    $typeHint = 'string';
                    break;
                case 'double':
                case 'decimal':
                case 'float':
                    $var = ($type == 'decimal') ? 'float' : $type;
                    $type = ($type != 'decimal') ? 'float' : $type;
                    $typeHint = 'float';
                    $fieldInfo['precision'] = $fieldInfo['length'];
                    unset($fieldInfo['length']);
                    break;
                case 'int':
                    $type = 'integer';
                    $var = 'int';
                    $typeHint = 'int';
                    break;
                case 'date':
                case 'datetime':
                    $var = 'DateTime';
                    $typeHint = 'DateTime';
                    if (!$useDateTime) {
                        $useDateTime = true;
                        $namespace->addUse('DateTime');
                    }
                    break;
            }

            $typeString = 'type="' . $type . '"';

            if (isset($fieldInfo['length'])) {
                $typeString .= ', length=' . $fieldInfo['length'];
            }

            if (isset($fieldInfo['decimals'])) {
                $typeString .= ', precision=' . $fieldInfo['precision'] . ', scale=' . $fieldInfo['decimals'];
            }

            $isNullable = $fieldInfo['nullable'] ? 'true' : 'false';
            $typeString .= ', nullable=' . $isNullable;

            $field = $class->addProperty($name);
            $field->setVisibility('private');
            $field->addComment('@var ' . $var . ' $' . $name);
            $field->addComment('@ORM\Column(' . $typeString . ')');

            $method = $class->addMethod('get' . ucfirst($name));
            $method->setBody('return $this->' . $name . ';');
            $method->addComment('@return ' . $var);
            $method->setReturnType($var);

            $method = $class->addMethod('set' . ucfirst($name));
            $method->addParameter($name)->setTypeHint($typeHint);
            $method->addComment('@param ' . $var . ' $' .$name);
            $method->setBody('$this->' . $name . ' = $' . $name . ';');
        }


        $namespace->add($class);

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Entity/' . $entityName . '.php', $code);

        return true;
    }
}