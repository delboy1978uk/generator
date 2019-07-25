<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiControllerGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($moduleNamespace . '\\' . 'Controller');
        $name = lcfirst($entityName);

        $namespace->addUse($moduleNamespace . '\\Collection\\' . $entityName . 'Collection');
        $namespace->addUse($moduleNamespace . '\\Form\\' . $entityName . 'Form');
        $namespace->addUse($moduleNamespace . '\\Service\\' . $entityName . 'Service');
        $namespace->addUse('League\Route\Http\Exception\NotFoundException');
        $namespace->addUse(ResponseInterface::class);
        $namespace->addUse(ServerRequestInterface::class);
        $namespace->addUse('Zend\Diactoros\Response\JsonResponse');

        $class = $namespace->addClass($entityName . 'ApiController');

        $property = $class->addProperty('service');
        $property->addComment('@param ' . $entityName . 'Service $service');
        $property->setVisibility('private');

        // constructor
        $method = $class->addMethod('__construct');
        $method->addComment('@param ' . $entityName . 'Service $service');
        $method->addParameter('service')->setTypeHint($moduleNamespace . '\\Service\\' . $entityName . 'Service');
        $method->setBody('$this->service = $service;');


        // index action
        $method = $class->addMethod('indexAction');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface');
        $method->addComment('@throws NotFoundException');
        $method->addComment('@throws \Doctrine\ORM\NonUniqueResultException');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setReturnType(ResponseInterface::class);
        $method->setBody('$params = $request->getQueryParams();
$limit = $params[\'limit\'];
$offset = $params[\'offset\'];
$db = $this->service->getRepository();
$' . $name . 's = new ' . $entityName . 'Collection($db->findBy([], null, $limit, $offset));
$total = $db->getTotal' . $entityName . 'Count();
$count = count($' . $name . 's);
if ($count < 1) {
    throw new NotFoundException();
}

$payload[\'_embedded\'] = $' . $name . 's->toArray();
$payload[\'count\'] = $count;
$payload[\'total\'] = $total;

return new JsonResponse($payload);');


        // create action
        $method = $class->addMethod('createAction');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface');
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setReturnType(ResponseInterface::class);
        $method->setBody('$post = json_decode($request->getBody()->getContents(), true) ?: $request->getParsedBody();
$form = new ' . $entityName . 'Form(\'create\');
$form->populate($post);

if ($form->isValid()) {
    $data = $form->getValues();
    $' . $name . ' = $this->service->createFromArray($data);
    $this->service->save' . $entityName . '($' . $name . ');

    return new JsonResponse($' . $name . '->toArray());
}

return new JsonResponse([
    \'error\' => $form->getErrorMessages(),
]);');


        // view action
        $method = $class->addMethod('viewAction');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface');
        $method->addComment('@throws \Doctrine\ORM\EntityNotFoundException');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setReturnType(ResponseInterface::class);
        $method->setBody('$' . $name . ' = $this->service->getRepository()->find($args[\'id\']);

return new JsonResponse($' . $name . '->toArray());');


        // update action
        $method = $class->addMethod('updateAction');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface');
        $method->addComment('@throws \Doctrine\ORM\EntityNotFoundException');
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setReturnType(ResponseInterface::class);
        $method->setBody('$db = $this->service->getRepository();
$' . $name . ' = $db->find($args[\'id\']);

$post = json_decode($request->getBody()->getContents(), true) ?: $request->getParsedBody();
$form = new ' . $entityName . 'Form(\'update\');
$form->populate($post);

if ($form->isValid()) {
    $data = $form->getValues();
    $' . $name . ' = $this->service->updateFromArray($' . $name . ', $data);
    $this->service->save' . $entityName . '($' . $name . ');

    return new JsonResponse($' . $name . '->toArray());
}

return new JsonResponse([
    \'error\' => $form->getErrorMessages(),
]);');


        // delete action
        $method = $class->addMethod('deleteAction');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface');
        $method->addComment('@throws \Doctrine\ORM\EntityNotFoundException');
        $method->addComment('@throws \Doctrine\ORM\ORMException');
        $method->addComment('@throws \Doctrine\ORM\OptimisticLockException');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setReturnType(ResponseInterface::class);
        $method->setBody('$db = $this->service->getRepository();
$' . $name . ' = $db->find($args[\'id\']);
$this->service->delete' . $entityName . '($' . $name . ');

return new JsonResponse([\'deleted\' => true]);');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Controller/' . $entityName . 'ApiController.php', $code);


        return true;
    }
}