<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class ServiceGenerator extends FileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $entityName, array $fields): bool
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $moduleNamespace = $nameSpace . '\\' . $entityName;
        $namespace = $file->addNamespace($moduleNamespace . '\\Service');

        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse($moduleNamespace . '\\Repository\\' . $entityName . 'Repository');
        $namespace->addUse('Doctrine\ORM\EntityManager');

        $class = $namespace->addClass($entityName . 'Service');
        $namespace->add($class);
        $name = lcfirst($entityName);

        $prop = $class->addProperty('em');
        $prop->setVisibility('private');
        $prop->addComment('@var EntityManager $em');


        // constructor
        $method = $class->addMethod('__construct');
        $method->addParameter('em')->setTypeHint('Doctrine\ORM\EntityManager');
        $method->setBody('$this->em = $em;');
        $method->addComment('@param EntityManager $em');


        // createFromArray
        $method = $class->addMethod('createFromArray');
        $method->addParameter('data')->setTypeHint('array');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $body = '$' . $name . ' = new ' . $entityName . '();

return $this->updateFromArray($' . $name . ', $data);';
        $method->setBody($body);
        $method->addComment('@param array $data');
        $method->addComment('@return ' . $entityName);
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);

        // update from array
        $method = $class->addMethod('updateFromArray');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@param array $data');
        $method->addComment('@return ' . $entityName);
        $method->addParameter($name)->setTypeHint($moduleNamespace . '\\Entity\\' . $entityName);
        $method->addParameter('data')->setTypeHint('array');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $body = 'isset($data[\'id\']) ? $' . $name . '->setId($data[\'id\']) : null;' . "\n";
        foreach ($fields as $field) {
            switch ($field['type']) {
                case 'date':
                    $body .= "\n" . 'if (isset($data[\'' . $field['name'] . '\'])) {
    $' . $field['name'] . ' = $data[\'' . $field['name'] . '\'] instanceof DateTime ? $data[\'' . $field['name'] . '\'] : DateTime::createFromFormat(\'d/m/Y\', $data[\'' . $field['name'] . '\']);
    $' . $field['name'] . ' = $' . $field['name'] . ' ?: null;
    $' . $name . '->set' . ucfirst($field['name']) . '($' . $field['name'] . ');
}' . "\n";
                    break;
                case 'datetime':
                    $body .= "\n" . 'if (isset($data[\'' . $field['name'] . '\'])) {
    $' . $field['name'] . ' = $data[\'' . $field['name'] . '\'] instanceof DateTime ? $data[\'' . $field['name'] . '\'] : DateTime::createFromFormat(\'d/m/Y H:i\', $data[\'' . $field['name'] . '\']);
    $' . $field['name'] . ' = $' . $field['name'] . ' ?: null;
    $' . $name . '->set' . ucfirst($field['name']) . '($' . $field['name'] . ');
}' . "\n";
                    break;
                case 'bool':
                    $body .= 'isset($data[\'' . $field['name'] . '\']) ? $' . $name . '->set' . ucfirst($field['name']) . '((bool) $data[\'' . $field['name'][0] . '\']) : null;' . "\n";
                    break;
                case 'int':
                    $body .= 'isset($data[\'' . $field['name'] . '\']) ? $' . $name . '->set' . ucfirst($field['name']) . '((int) $data[\'' . $field['name'] . '\']) : null;' . "\n";
                    break;
                default:
                    $body .= 'isset($data[\'' . $field['name'] . '\']) ? $' . $name . '->set' . ucfirst($field['name']) . '($data[\'' . $field['name'] . '\']) : null;' . "\n";
            }
                $namespace->addUse('DateTime');


        }
        $body .= "\nreturn $" . $name . ';';
        reset($fields);
        $method->setBody($body);


        // save
        $method = $class->addMethod('save' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('return $this->getRepository()->save($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return ' . $entityName);
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);


        // delete
        $method = $class->addMethod('delete' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('$this->getRepository()->delete($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->setReturnType('void');

        // getRepository
        $method = $class->addMethod('getRepository');
        $method->setBody('/** @var ' . $entityName . 'Repository $repository */
$repository = $this->em->getRepository(' . $entityName . '::class);

return $repository;');
        $method->addComment('@return ' . $entityName . 'Repository');
        $method->setReturnType($moduleNamespace . '\\Repository\\' . $entityName . 'Repository');


        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Service/' . $entityName . 'Service.php', $code);

        return true;
    }
}