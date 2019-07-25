<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class RepositoryGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($moduleNamespace . '\\Repository');

        $namespace->addUse('Doctrine\ORM\EntityNotFoundException');
        $namespace->addUse('Doctrine\ORM\EntityRepository');
        $namespace->addUse($moduleNamespace . '\\Collection\\' . $entityName . 'Collection');
        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);

        $class = new ClassType($entityName . 'Repository');
        $class->addExtend('Doctrine\ORM\EntityRepository');
        $namespace->add($class);
        $name = lcfirst($entityName);

        // find
        $method = $class->addMethod('find');
        $method->addParameter('id');
        $method->addParameter('lockMode', null);
        $method->addParameter('lockVersion', null);
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $method->setBody('        /** @var ' . $entityName . ' $' . $name . ' */
$' . $name . ' =  parent::find($id, $lockMode, $lockVersion);
if (!$' . $name . ') {
    throw new EntityNotFoundException(\'' . $entityName . ' not found.\', 404);
}

return $' . $name . ';');
        $method->addComment('@param int $id');
        $method->addComment('@param int|null $lockMode');
        $method->addComment('@param int|null $lockVersion');
        $method->addComment('@return ' . $entityName);
        $method->addComment('@throws \Doctrine\ORM\ORMException');


        // save
        $method = $class->addMethod('save');
        $method->addParameter($name)->setTypeHint($moduleNamespace . '\\Entity\\' . $entityName);
        $method->setBody('if(!$' . $name . '->getID()) {
    $this->_em->persist($' . $name . ');
}
$this->_em->flush($' . $name . ');

return $' . $name . ';');
        $method->setReturnType($moduleNamespace . '\\Entity\\' . $entityName);
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return $' . $name);
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        // delete
        $method = $class->addMethod('delete');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('$this->_em->remove($' . $name . ');
$this->_em->flush($' . $name . ');');
        $method->setReturnType('void');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->addComment('@throws \Doctrine\ORM\ORMException');


        // get total count
        $method = $class->addMethod('getTotal' . $entityName . 'Count');
        $letter = $name[0];
        $method->setBody('        $qb = $this->createQueryBuilder(\'' . $letter . '\');
$qb->select(\'count(' . $letter . '.id)\');
$query = $qb->getQuery();

return (int) $query->getSingleScalarResult();');
        $method->setReturnType('int');
        $method->addComment('@return int');
        $method->addComment('@throws \Doctrine\ORM\NoResultException');
        $method->addComment('@throws \Doctrine\ORM\NonUniqueResultException');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Repository/' . $entityName . 'Repository.php', $code);

        return true;
    }
}