<?php

declare(strict_types=1);

namespace Del\Generator;

use BoneMvc\Module\Dragon\Entity\Dragon;
use Del\Form\AbstractForm;
use Del\Form\Field\Submit;
use Del\Form\Field\Text;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        $unique = $this->buildId = uniqid();
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
        ];

        foreach ($folders as $folder) {
            if (!mkdir($folder)) {
                throw new Exception('could not create ' . $folder);
            }
        }

        if (!file_exists('migrations')) {
            mkdir('migrations');
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
        $file = new PhpFile();
        $file->setStrictTypes();
        $namespace = $file->addNamespace($nameSpace . '\\' . $entityName . '\\Entity');
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $namespace->addUse('JsonSerializable');
        $class = new ClassType($entityName);
        $class->addComment('@ORM\Entity(repositoryClass="\\' . $nameSpace . '\Repository\\' . $entityName . 'Repository")');

        $id = $class->addProperty('id');
        $id->setVisibility('private');
        $id->addComment('@var int $id');
        $id->addComment('@ORM\Id');
        $id->addComment('@ORM\Column(type="integer")');
        $id->addComment('@ORM\GeneratedValue');

        $method = $class->addMethod('getId');
        $method->setReturnType('int');
        $method->setBody('return $this->id;');
        $method->addComment('@return int');

        $method = $class->addMethod('setId');
        $method->setReturnType('void');
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
                    $var = ($type === 'decimal') ? 'float' : $type;
                    $type = ($type !== 'decimal') ? 'float' : $type;
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
            $method->setReturnNullable();

            $method = $class->addMethod('set' . ucfirst($name));
            $method->addParameter($name)->setTypeHint($typeHint);
            $method->addComment('@param ' . $var . ' $' . $name);
            $method->setBody('$this->' . $name . ' = $' . $name . ';');
            $method->setReturnType('void');
        }
        reset($fields);

        // toArray()
        $method = $class->addMethod('toArray');
        $method->addComment('@return array');
        $body = '$data = [' . "\n";
        $body .= "    'id' => \$this->getId(),\n";
        foreach ($fields as $field) {
            $body .= "    '{$field['name']}' => \$this->get" . ucfirst($field['name']) . "(),\n";
        }
        $body .= "];\n\nreturn \$data;";
        $method->setBody($body);
        $method->setReturnType('array');
        reset($fields);

        // toJson()
        $method = $class->addMethod('jsonSerialize');
        $method->addComment('@return string');
        $body = 'return \json_encode($this->toArray());';
        $method->setBody($body);
        $method->setReturnType('string');

        // toString()
        $method = $class->addMethod('__toString');
        $method->addComment('@return string');
        $body = 'return $this->jsonSerialize();';
        $method->setBody($body);
        $method->setReturnType('string');

        $namespace->add($class);

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/'. $entityName . '/Entity/' . $entityName . '.php', $code);

        return true;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createRepository(string $nameSpace, string $entityName): bool
    {
        $namespace = new PhpNamespace($nameSpace  . '\\' . $entityName. '\\Repository');
        $namespace->addUse('Doctrine\ORM\EntityRepository');
        $namespace->addUse($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $class = new ClassType($entityName . 'Repository');
        $class->addExtend('Doctrine\ORM\EntityRepository');
        $namespace->add($class);
        $name = lcfirst($entityName);

        $method = $class->addMethod('save');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName  . '\\Entity\\' . $entityName);
        $method->setBody('if(!$' . $name . '->getID()) {
    $this->_em->persist($' . $name . ');
}
$this->_em->flush($' . $name . ');
return $' . $name . ';');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return $' . $name);
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        $method = $class->addMethod('delete');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName   . '\\Entity\\' . $entityName);
        $method->setBody('$this->_em->remove($'. $name . ');
$this->_em->flush($'. $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->addComment('@throws \Doctrine\ORM\ORMException');

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Repository/' . $entityName . 'Repository.php', $code);

        return true;


    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createCollection(string $nameSpace, string $entityName): bool
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
        $method->setReturnType('void' );
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

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createService(string $nameSpace, string $entityName, array $fields): bool
    {
        $namespace = new PhpNamespace($nameSpace . '\\' . $entityName   . '\\Service');
        $namespace->addUse($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $namespace->addUse($nameSpace . '\\' . $entityName . '\\Repository\\' . $entityName . 'Repository');
        $namespace->addUse('Doctrine\ORM\EntityManager');
        $class = new ClassType($entityName . 'Service');
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
        $body = '$' . $name . ' = new ' . $entityName . '();' . "\n";
        $body .= 'isset($data[\'id\']) ? $' . $name . '->setId($data[\'id\']) : null;' . "\n";
        foreach ($fields as $field) {
            if (in_array($field['type'], ['date', 'datetime'])) {
                $namespace->addUse('DateTime');
                $body .= "\n" . 'if (isset($data[\'' . $field['name'] . '\'])) {
    $' . $field['name'] . ' = $data[\'' . $field['name'] . '\'] instanceof DateTime ? $data[\'' . $field['name'] . '\'] : DateTime::createFromFormat(\'Y-m-d\', $data[\'' . $field['name'] . '\']);
    $' . $name . '->set' . ucfirst($field['name']) . '($' . $field['name'] . ');
}' . "\n";
            } else {
                $body .= 'isset($data[\'' . $field['name'] . '\']) ? $' . $name . '->set' . ucfirst($field['name']) . '($data[\'' . $field['name'] . '\']) : null;' . "\n";
            }

        }
        reset($fields);
        $body .= "\nreturn $" . $name . ';';
        $method->setBody($body);
        $method->addComment('@param array $data');
        $method->addComment('@return $' . $entityName);


        // save
        $method = $class->addMethod('save' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName  . '\\Entity\\' . $entityName);
        $method->setBody('return $this->getRepository()->save($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return ' . $entityName );
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->setReturnType($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);


        // delete
        $method = $class->addMethod('delete' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\' . $entityName . '\\Entity\\' . $entityName);
        $method->setBody('return $this->getRepository()->delete($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        // getRepository
        $method = $class->addMethod('getRepository');
        $method->setBody('/** @var ' . $entityName . 'Repository $repository */
$repository = $this->em->getRepository(' .  $entityName . '::class);

return $repository;');
        $method->addComment('@return ' . $entityName . 'Repository');
        $method->setReturnType($nameSpace . '\\' . $entityName . '\\Repository\\' . $entityName . 'Repository');


        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Service/' . $entityName . 'Service.php', $code);

        return true;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createPackage(string $nameSpace, string $entityName): bool
    {
        $namespace = new PhpNamespace($nameSpace . '\\' . $entityName  );
        $namespace->addUse($nameSpace . '\\' . $entityName  . '\\Service\\' . $entityName . 'Service');
        $namespace->addUse('Del\Common\Container\RegistrationInterface');
        $namespace->addUse('Doctrine\ORM\EntityManager');
        $namespace->addUse('Pimple\Container');
        $class = new ClassType($entityName . 'Package');
        $class->addImplement('Del\Common\Container\RegistrationInterface');
        $namespace->add($class);

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Pimple\Container');
        $method->setBody('/** @var EntityManager $em */
$em = $c[\'doctrine.entity_manager\'];
$c[\'service.' . $entityName . '\'] = new ' . $entityName . 'Service($em);');
        $method->addComment('@param Container $c');

        // getEntityPath
        $method = $class->addMethod('getEntityPath');
        $method->setBody("return 'build/" . $this->buildId . "/src/" . $entityName . "/Entity';");
        $method->addComment('@return string');

        // getEntityPath
        $method = $class->addMethod('hasEntityPath');
        $method->setBody("return true;");
        $method->addComment('@return bool');

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/' . $entityName . 'Package.php', $code);

        return true;
    }



    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createForm(string $nameSpace, string $entityName, array $fields): bool
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $moduleNamespace = $nameSpace . '\\' . $entityName;
        $namespace = $file->addNamespace($moduleNamespace . '\\Form');

        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse(AbstractForm::class);
        $namespace->addUse(Submit::class);
        $namespace->addUse(Text::class);

        $class = new ClassType($entityName . 'Form');
        $class->addExtend(AbstractForm::class);

        $namespace->add($class);

        // getEntityPath
        $method = $class->addMethod('init');
        $body = $this->createFormInitMethod($fields);
        $method->setBody($body);
        $method->setReturnType('void');

        $printer = new PsrPrinter();
        $code = $printer->printNamespace($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Form/' . $entityName . 'Form.php', $code);

        return true;
    }

    /**
     * @param array $fields
     * @return string
     */
    private function createFormInitMethod(array $fields)
    {
        $body = '';

        foreach ($fields as $field) {
            $body .= $this->createFieldInitMethod($field);
        }

        $body .= '$submit = new Submit(\'submit\');' . "\n";
        $body .= '$this->addField($submit);';

        return $body;
    }

    /**
     * @param array $field
     * @return string
     */
    private function createFieldInitMethod(array $field)
    {
        $body = '$' . $field['name'] . ' = new Text(\'' . $field['name'] . '\');' . "\n";
        $body .= '$' . $field['name'] . '->setLabel(\'' . ucfirst($field['name']). '\');' . "\n";

        if (!$field['nullable']) {
            $body .= '$' . $field['name'] . '->setRequired(true);' . "\n";
        }

        if (isset($field['filters'])) {
            $filters = $field['filters'];
            foreach ($filters as $filter) {
                $body .= '// add ' . $filter . 'filter here' . "\n";
            }
        }

        if (isset($field['validators'])) {
            $validators = $field['validators'];
            foreach ($validators as $validator) {
                $body .= '// add ' . $validator . 'validator here' . "\n";
            }
        }

        $body .= '$this->addField($' . $field['name'] . ');' . "\n\n";

        return $body;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    private function createController(string $nameSpace, string $entityName, array $fields): bool
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $moduleNamespace = $nameSpace . '\\' . $entityName;
        $namespace = $file->addNamespace($moduleNamespace . '\\Controller');

        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse('Bone\Mvc\View\ViewEngine');
        $namespace->addUse('Bone\View\Helper\AlertBox');
        $namespace->addUse('Bone\View\Helper\Paginator');
        $namespace->addUse($moduleNamespace . '\\Collection\\' . $entityName . 'Collection');
        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse($moduleNamespace . '\\Form\\' . $entityName . 'Form');
        $namespace->addUse($moduleNamespace . '\\Service\\' . $entityName . 'Service');
        $namespace->addUse('Del\Form\Field\Submit');
        $namespace->addUse('Del\Form\Form');
        $namespace->addUse('Del\Icon');
        $namespace->addUse(ResponseInterface::class);
        $namespace->addUse(ServerRequestInterface::class);
        $namespace->addUse('Zend\Diactoros\Response\HtmlResponse');

        $class = new ClassType($entityName . 'Controller');
        $name = lcfirst($entityName);

        $namespace->add($class);

        $property = $class->addProperty('numPerPage');
        $property->addComment('@var int $numPerPage');
        $property->setVisibility('private');

        $property = $class->addProperty('paginator');
        $property->addComment('@var Paginator $paginator');
        $property->setVisibility('private');

        $property = $class->addProperty('service');
        $property->addComment('@var ' . $entityName . 'Service $service');
        $property->setVisibility('private');

        $property = $class->addProperty('view');
        $property->addComment('@var ViewEngine $view');
        $property->setVisibility('private');

        // constructor
        $method = $class->addMethod('__construct');
        $method->addParameter('service')->setTypeHint($nameSpace . '\\' . $entityName  . '\\Service\\' . $entityName . 'Service');
        $method->setBody('$this->service = $service;');
        $method->addComment('@param ' . $entityName . 'Service' . ' $service');


        // indexAction
        $lcEntity = strtolower($entityName);
        $method = $class->addMethod('indexAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('
$db = $this->service->getRepository();
$total = $db->getTotal' . $entityName . 'Count();

$this->paginator->setUrl(\'' . $lcEntity . '?page=:page\');
$page = (int) $request->getQueryParams()[\'page\'] ?: 1;
$this->paginator->setCurrentPage($page);
$this->paginator->setPageCountByTotalRecords($total, $this->numPerPage);

$' . $lcEntity . 's = new ' . $entityName . 'Collection($db->findBy([], null, $this->numPerPage, ($page *  $this->numPerPage) - $this->numPerPage));

$body = $this->view->render(\'' . $lcEntity . '::index\', [
    \'' . $lcEntity . 's\' => $' . $lcEntity . 's,
    \'paginator\' => $this->paginator->render(),
]);

return new HtmlResponse($body);
');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);


        // viewAction
        $method = $class->addMethod('viewAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('
$db = $this->service->getRepository();
$id = $args[\'id\'];
$' . $lcEntity . ' = $db->find($id);
$body = $this->view->render(\'' . $lcEntity . '::view\', [
    \'' . $lcEntity . '\' => $' . $lcEntity . ',
]);

return new HtmlResponse($body);
');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);



        // createAction
        $method = $class->addMethod('createAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('$msg = \'\';
$form = new ' . $entityName . 'Form(\'create' . $entityName . '\');
if ($request->getMethod() === \'POST\') {
    $post = $request->getParsedBody();
    $form->populate($post);
    if ($form->isValid()) {
        $data = $form->getValues();
        $' . $lcEntity . ' = $this->service->createFromArray($data);
        $this->service->save' . $entityName . '($' . $lcEntity . ');
        $msg = $this->alertBox(Icon::CHECK_CIRCLE . \' New ' . $lcEntity . ' added to database.\', \'success\');
        $form = new ' . $entityName . 'Form(\'create' . $entityName . '\');
    } else {
        $msg = $this->alertBox(Icon::REMOVE . \' There was a problem with the form.\', \'danger\');
    }
}

$form = $form->render();
$body = $this->view->render(\'' . $lcEntity . '::create\', [
    \'form\' => $form,
    \'msg\' => $msg,
]);

return new HtmlResponse($body);');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);



        // editAction
        $method = $class->addMethod('editAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('$msg = \'\';
$form = new ' . $entityName . 'Form(\'edit' . $entityName . '\');
$id = $args[\'id\'];
$db = $this->service->getRepository();
/** @var ' . $entityName .' $' . $lcEntity . ' */
$' . $lcEntity . ' = $db->find($id);
$form->populate($' . $lcEntity . '->toArray());

if ($request->getMethod() === \'POST\') {
    $post = $request->getParsedBody();
    $form->populate($post);
    if ($form->isValid()) {
        $data = $form->getValues();
        $' . $lcEntity . ' = $this->service->updateFromArray($' . $lcEntity . ', $data);
        $this->service->save' . $entityName . '($' . $lcEntity . ');
        $msg = $this->alertBox(Icon::CHECK_CIRCLE . \' ' . $entityName . ' details updated.\', \'success\');
    } else {
        $msg = $this->alertBox(Icon::REMOVE . \' There was a problem with the form.\', \'danger\');
    }
}

$form = $form->render();
$body = $this->view->render(\'' . $lcEntity . '::edit\', [
    \'form\' => $form,
    \'msg\' => $msg,
]);

return new HtmlResponse($body);');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);



        // deleteAction
        $method = $class->addMethod('deleteAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('$id = $args[\'id\'];
$db = $this->service->getRepository();
$form = new Form(\'delete' . $entityName . '\');
$submit = new Submit(\'submit\');
$submit->setValue(\'Delete\');
$submit->setClass(\'btn btn-danger\');
$form->addField($submit);
/** @var ' . $entityName . ' $' . $lcEntity . ' */
$' . $lcEntity . ' = $db->find($id);

if ($request->getMethod() === \'POST\') {
    $this->service->delete' . $entityName . '($' . $lcEntity . ');
    $msg = $this->alertBox(Icon::CHECK_CIRCLE . \' ' . $entityName . ' deleted.\', \'warning\');
    $form = \'<a href="/' . $lcEntity . '" class="btn btn-default">Back</a>\';
} else {
    $form = $form->render();
    $msg = $this->alertBox(Icon::WARNING . \' Warning, please confirm your intention to delete.\', \'danger\');
    $msg .= \'<p class="lead">Are you sure you want to delete \' . $' . $lcEntity . '->getName() . \'?</p>\';
}

$body = $this->view->render(\'' . $lcEntity . '::delete\', [
    \'' . $lcEntity . '\' => $' . $lcEntity . ',
    \'form\' => $form,
    \'msg\' => $msg,
]);

return new HtmlResponse($body);');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);



        // alert box
        $method = $class->addMethod('alertBox');
        $method->setVisibility('private');
        $method->addParameter('message')->setTypeHint('string');
        $method->addParameter('class')->setTypeHint('string');
        $method->setBody('return AlertBox::alertBox([
    \'message\' => $message,
    \'class\' => $class,
]);');
        $method->addComment('@param string $message');
        $method->addComment('@param string $class');
        $method->addComment('@return string');
        $method->setReturnType('string');


        $printer = new PsrPrinter();
        $code = "<?php declare(strict_types=1);\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Controller/' . $entityName . 'Controller.php', $code);

        return true;
    }
}