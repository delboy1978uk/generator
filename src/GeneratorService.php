<?php

declare(strict_types=1);

namespace Del\Generator;

use Del\Form\AbstractForm;
use Del\Form\Field\Text;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
        $this->createRepository($nameSpace, $entityName);
        $this->createCollection($nameSpace, $entityName);
        $this->createService($nameSpace, $entityName, $fields);
        $this->createForm($nameSpace, $entityName, $fields);
        $this->createController($nameSpace, $entityName, $fields);
        $this->createPackage($nameSpace, $entityName);

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
            'build/' . $unique . '/src/Collection',
            'build/' . $unique . '/src/Controller',
            'build/' . $unique . '/src/Entity',
            'build/' . $unique . '/src/Form',
            'build/' . $unique . '/src/Repository',
            'build/' . $unique . '/src/Service',
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
        $namespace = new PhpNamespace($nameSpace . '\\Entity');
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $class = new ClassType($entityName);
        $class->addComment('@ORM\Entity(repositoryClass="\\' . $nameSpace . '\Repository\\' . $entityName . 'Repository")');

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
            $method->setReturnNullable();

            $method = $class->addMethod('set' . ucfirst($name));
            $method->addParameter($name)->setTypeHint($typeHint);
            $method->addComment('@param ' . $var . ' $' . $name);
            $method->setBody('$this->' . $name . ' = $' . $name . ';');
        }
        reset($fields);

        $namespace->add($class);

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Entity/' . $entityName . '.php', $code);

        return true;
    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createRepository(string $nameSpace, string $entityName): bool
    {
        $namespace = new PhpNamespace($nameSpace . '\\Repository');
        $namespace->addUse('Doctrine\ORM\EntityRepository');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName);
        $class = new ClassType($entityName . 'Repository');
        $class->addExtend('Doctrine\ORM\EntityRepository');
        $namespace->add($class);
        $name = lcfirst($entityName);

        $method = $class->addMethod('save');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('if(!$' . $name . '->getID()) {
    $this->_em->persist($' . $name . ');
}
$this->_em->flush($' . $name . ');
return $' . $name . ';');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return $' . $name);
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        $method = $class->addMethod('delete');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('$this->_em->remove($'. $name . ');
$this->_em->flush($'. $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Repository/' . $entityName . 'Repository.php', $code);

        return true;


    }

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @return bool
     */
    private function createCollection(string $nameSpace, string $entityName): bool
    {
        $namespace = new PhpNamespace($nameSpace . '\\Collection');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName);
        $namespace->addUse('Doctrine\Common\Collections\ArrayCollection');
        $namespace->addUse('LogicException');
        $class = new ClassType($entityName . 'Collection');
        $class->addExtend('Doctrine\Common\Collections\ArrayCollection');
        $namespace->add($class);
        $name = lcfirst($entityName);

        $method = $class->addMethod('update');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('$key = $this->findKey($' . $name . ');
if($key) {
    $this->offsetSet($key,$' . $name . ');
    return $this;
}
throw new LogicException(\'' . $entityName . ' was not in the collection.\');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return $this');
        $method->addComment('@throws LogicException');


        $method = $class->addMethod('append');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('$this->add($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);


        $method = $class->addMethod('current');
        $method->setBody('return parent::current();');
        $method->addComment('@return ' . $entityName . '|null');

        $method = $class->addMethod('findKey');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('$it = $this->getIterator();
$it->rewind();
while($it->valid()) {
    if($it->current()->getId() == $' . $name . '->getId()) {
        return $it->key();
    }
    $it->next();
}
return false;');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return bool|int');


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
return false;');
        $method->addComment('@param int $id');
        $method->addComment('@return ' . $entityName . '|bool');


        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Collection/' . $entityName . 'Collection.php', $code);

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
        $namespace = new PhpNamespace($nameSpace . '\\Service');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName);
        $namespace->addUse($nameSpace . '\\Repository\\' . $entityName . 'Repository');
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
            $body .= 'isset($data[\'' . $field['name'] . '\']) ? $' . $name . '->set' . ucfirst($field['name']) . '($data[\'' . $field['name'] . '\']) : null;' . "\n";
        }
        reset($fields);
        $body .= "\nreturn $" . $name . ';';
        $method->setBody($body);
        $method->addComment('@param array $data');
        $method->addComment('@return $' . $entityName);

        // toArray()
        $method = $class->addMethod('toArray');
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $body = '$data = [' . "\n";
        $body .= "    'id' => $" . $name . "->getId(),\n";
        foreach ($fields as $field) {
            $body .= "    '{$field['name']}' => $" . $name . "->get" . ucfirst($field['name']) . "(),\n";
        }
        reset($fields);
        $body .= "];\n\nreturn " . '$data;';
        $method->setBody($body);
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return array');


        // save
        $method = $class->addMethod('save' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('return $this->getRepository()->save($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@return ' . $entityName );
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->setReturnType($nameSpace . '\\Entity\\' . $entityName);


        // delete
        $method = $class->addMethod('delete' . $entityName);
        $method->addParameter($name)->setTypeHint($nameSpace . '\\Entity\\' . $entityName);
        $method->setBody('return $this->getRepository()->delete($' . $name . ');');
        $method->addComment('@param ' . $entityName . ' $' . $name);
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');

        // getRepository
        $method = $class->addMethod('getRepository');
        $method->setBody('/** @var \\' . $nameSpace . '\Repository\\' . $entityName . 'Repository $repository */
$repository = $this->em->getRepository(' .  $entityName . '::class);

return $repository;');
        $method->addComment('@return ' . $entityName . 'Repository');
        $method->setReturnType($nameSpace . '\\Repository\\' . $entityName . 'Repository');


        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Service/' . $entityName . 'Service.php', $code);

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
        $namespace = new PhpNamespace($nameSpace);
        $namespace->addUse($nameSpace . '\\Service\\' . $entityName . 'Service');
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
        $method->setBody("return 'build/" . $this->buildId . "/src/Entity';");
        $method->addComment('@return string');

        // getEntityPath
        $method = $class->addMethod('hasEntityPath');
        $method->setBody("return true;");
        $method->addComment('@return bool');

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/' . $entityName . 'Package.php', $code);

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
        $namespace = new PhpNamespace($nameSpace . '\\Service');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName);
        $namespace->addUse(AbstractForm::class);
        $namespace->addUse(Text::class);
        $class = new ClassType($entityName . 'Form');
        $class->addExtend(AbstractForm::class);

        $namespace->add($class);

        // getEntityPath
        $method = $class->addMethod('init');
        $body = $this->createFormInitMethod($fields);
        $method->setBody($body);

        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Form/' . $entityName . 'Form.php', $code);

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

        return $body;
    }

    /**
     * @param array $field
     * @return string
     */
    private function createFieldInitMethod(array $field)
    {
        $body = '$' . $field['name'] . ' = new Text(\'' . $field['name'] . '\');' . "\n";

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
        $namespace = new PhpNamespace($nameSpace . '\\Controller');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName);
        $namespace->addUse(RequestInterface::class);
        $namespace->addUse(ResponseInterface::class);
        $namespace->addUse($nameSpace . '\\Form\\' . $entityName . 'Form');
        $namespace->addUse($nameSpace . '\\Entity\\' . $entityName . 'Service');
        $class = new ClassType($entityName . 'Controller');
        $name = ucfirst($entityName);

        $namespace->add($class);

        $property = $class->addProperty('service');
        $property->addComment('@var ' . $entityName . 'Service');

        // constructor
        $method = $class->addMethod('__construct');
        $method->addParameter('service')->setTypeHint($nameSpace . '\\Entity\\' . $entityName . 'Service');
        $method->setBody('$this->service = $service;');
        $method->addComment('@param ' . $entityName . 'Service' . ' $service');

        // create
        $method = $class->addMethod('create');
        $method->addParameter('request')->setTypeHint(RequestInterface::class);
        $method->setBody('$post = $this->getJsonPost($request);
$form = new ' . $entityName . 'Form();
$form->populate($post);
if ($form->isValid()) {
    $data = $form->getValues();
    $' . $name . ' = $this->service->createFromArray($data);
    $this->service->save(' . $name . ');
    return $this->jsonResponse(' . $name . ');
} else {
    // handle errors
}');
        $method->addComment('@param RequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->setReturnType(ResponseInterface::class);

        // read
        $method = $class->addMethod('read');
        $method->addParameter('request')->setTypeHint(RequestInterface::class);
        $method->setBody('');
        $method->addComment('@param RequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->setReturnType(ResponseInterface::class);;

        // update
        $method = $class->addMethod('update');
        $method->addParameter('request')->setTypeHint(RequestInterface::class);
        $method->setBody('');
        $method->addComment('@param RequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->setReturnType(ResponseInterface::class);

        // update
        $method = $class->addMethod('delete');
        $method->addParameter('request')->setTypeHint(RequestInterface::class);
        $method->setBody('');
        $method->addComment('@param RequestInterface $request');
        $method->addComment('@return ResponseInterface $response');
        $method->setReturnType(ResponseInterface::class);

        // get Json Post
        $method = $class->addMethod('getJsonPost');
        $method->addParameter('request')->setTypeHint(RequestInterface::class);
        $method->setBody('return json_decode($request->getBody()->getContents(), true);');
        $method->addComment('@param RequestInterface $request');
        $method->addComment('@return array');
        $method->setReturnType('array');

        // Json Response
        $method = $class->addMethod('jsonResponse');
        $method->addParameter('data')->setTypeHint('array');
        $method->setBody('$json = json_encode($data);
// create proper $response later
header(\'Content-Type: application/json\');
echo $json;
exit;');
        $method->addComment('@param array $data');
        $method->addComment('@return ' . ResponseInterface::class);
        $method->setReturnType(ResponseInterface::class);


        $printer = new PsrPrinter();
        $code = "<?php\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/src/Controller/' . $entityName . 'Controller.php', $code);

        return true;
    }
}