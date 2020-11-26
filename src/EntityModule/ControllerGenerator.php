<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($moduleNamespace . '\\Controller');

        $namespace->addUse('Bone\Controller\Controller');
        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
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
        $namespace->addUse('Laminas\Diactoros\Response\HtmlResponse');

        $class = $namespace->addClass($entityName . 'Controller');
        $class->addExtend('Bone\Controller\Controller');

        $property = $class->addProperty('numPerPage');
        $property->addComment('@var int $numPerPage');
        $property->setVisibility('private');
        $property->setValue(10);

        $property = $class->addProperty('paginator');
        $property->addComment('@var Paginator $paginator');
        $property->setVisibility('private');

        $property = $class->addProperty('service');
        $property->addComment('@var ' . $entityName . 'Service $service');
        $property->setVisibility('private');

        // constructor
        $method = $class->addMethod('__construct');
        $method->addParameter('service')->setTypeHint($nameSpace . '\\' . $entityName . '\\Service\\' . $entityName . 'Service');
        $method->setBody('$this->paginator = new Paginator();
$this->service = $service;');
        $method->addComment('@param ' . $entityName . 'Service' . ' $service');


        // index
        $lcEntity = strtolower($entityName);
        $method = $class->addMethod('index');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->setBody('
$db = $this->service->getRepository();
$total = $db->getTotal' . $entityName . 'Count();
$this->paginator->setUrl(\'' . $lcEntity . '?page=:page\');
$params = $request->getQueryParams();
$page = array_key_exists(\'page\', $params) ?(int) $params[\'page\'] : 1;
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
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);


        // view
        $method = $class->addMethod('view');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->setBody('
$db = $this->service->getRepository();
$id = $request->getAttribute(\'id\');
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


        // create
        $method = $class->addMethod('create');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
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


        // edit
        $method = $class->addMethod('edit');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->setBody('$msg = \'\';
$form = new ' . $entityName . 'Form(\'edit' . $entityName . '\');
$id = $request->getAttribute(\'id\');
$db = $this->service->getRepository();
/** @var ' . $entityName . ' $' . $lcEntity . ' */
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


        // delete
        $method = $class->addMethod('delete');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->setBody('$id = $request->getAttribute(\'id\');
$db = $this->service->getRepository();
$form = new Form(\'delete' . $entityName . '\');
$submit = new Submit(\'submit\');
$submit->setValue(\'Delete\');
$submit->setClass(\'btn btn-lg btn-danger\');
$form->addField($submit);
/** @var ' . $entityName . ' $' . $lcEntity . ' */
$' . $lcEntity . ' = $db->find($id);

if ($request->getMethod() === \'POST\') {
    $this->service->delete' . $entityName . '($' . $lcEntity . ');
    $msg = $this->alertBox(Icon::CHECK_CIRCLE . \' ' . $entityName . ' deleted.\', \'warning\');
    $form = \'<a href="/' . $lcEntity . '" class="btn btn-lg btn-default">Back</a>\';
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
        $method->setBody('$helper = new AlertBox();

return $helper->alertBox([
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