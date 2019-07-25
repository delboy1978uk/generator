<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class CollectionGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($moduleNamespace . '\\Collection');
        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse('Doctrine\Common\Collections\ArrayCollection');
        $namespace->addUse('JsonSerializable');
        $namespace->addUse('LogicException');

        $className = $entityName . 'Collection';
        $fqcn = $moduleNamespace . '\\' . 'Collection' . '\\' . $className;

        $class = new ClassType($className);
        $class->addExtend('Doctrine\Common\Collections\ArrayCollection');
        $class->addImplement('JsonSerializable');
        $namespace->add($class);
        $name = lcfirst($entityName);


        $method = $class->addMethod('update');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('$key = $this->findKey($' . $name . ');
if($key) {
    $this->offsetSet($key,$' . $name . ');
    return $this;
}
throw new LogicException(\'' . $entityName . ' was not in the collection.\');');
        $method->setReturnType($fqcn);
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return $this');
        $method->addComment('@throws LogicException');


        $method = $class->addMethod('append');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('$this->add($' . $name . ');');
        $method->setReturnType('void');
        $method->addComment('@param ' . $entityName . ' $' . $name);


        $method = $class->addMethod('current');
        $method->setBody('return parent::current();');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $method->setReturnNullable();
        $method->addComment('@return ' . $entityName . '|null');


        $method = $class->addMethod('findKey');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('$it = $this->getIterator();
$it->rewind();
while($it->valid()) {
    if($it->current()->getId() == $' . $name . '->getId()) {
        return $it->key();
    }
    $it->next();
}
return null;');
        $method->setReturnType('int');
        $method->setReturnNullable();
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return int|null');


        $method = $class->addMethod('findById');
        $method->addParameter('id')->setTypeHint('int');
        $method->setBody('$it = $this->getIterator();
$it->rewind();
while($it->valid()) {
    if($it->current()->getId() == $id) {
        return $it->current();
    }
    $it->next();
}
return null;');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $method->setReturnNullable();
        $method->addComment('@param int $id');
        $method->addComment('@return ' . $entityName . '|null');


        $method = $class->addMethod('toArray');
        $method->setBody('$collection = [];
$it = $this->getIterator();
$it->rewind();
while($it->valid()) {
    /** @var ' . $entityName . ' $row */
    $row = $it->current();
    $collection[] = $row->toArray();
    $it->next();
}

return $collection;');
        $method->setReturnType('array');
        $method->addComment('@return array');

        $method = $class->addMethod('jsonSerialize');
        $method->setBody('return \json_encode($this->toArray());');
        $method->setReturnType('string');
        $method->addComment('@return string');

        $method = $class->addMethod('__toString');
        $method->setBody('return $this->jsonSerialize();');
        $method->setReturnType('string');
        $method->addComment('@return string');


        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Collection/' . $entityName . 'Collection.php', $code);

        return true;
    }
}