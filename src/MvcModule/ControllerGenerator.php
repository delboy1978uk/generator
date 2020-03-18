<?php

namespace Del\Generator\MvcModule;

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
     * @param string $moduleName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $moduleName, array $fields): bool
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $moduleNamespace = $nameSpace . '\\' . $moduleName;
        $namespace = $file->addNamespace($moduleNamespace . '\\Controller');

        $namespace->addUse('Bone\Controller\Controller');
        $namespace->addUse(ResponseInterface::class);
        $namespace->addUse(ServerRequestInterface::class);
        $namespace->addUse('Laminas\Diactoros\Response\HtmlResponse');

        $class = $namespace->addClass($moduleName . 'Controller');
        $class->addExtend('Bone\Controller\Controller');

        // indexAction
        $lcEntity = strtolower($moduleName);
        $method = $class->addMethod('indexAction');
        $method->addParameter('request')->setTypeHint(ServerRequestInterface::class);
        $method->addParameter('args')->setTypeHint('array');
        $method->setBody('$body = $this->view->render(\'' . $lcEntity . '::index\', []);

return new HtmlResponse($body);
');
        $method->addComment('@param ServerRequestInterface $request');
        $method->addComment('@param array $args');
        $method->addComment('@return ResponseInterface $response');
        $method->addComment('@throws \Exception');
        $method->setReturnType(ResponseInterface::class);

        $printer = new PsrPrinter();
        $code = "<?php declare(strict_types=1);\n\n" . $printer->printNamespace($namespace);
        file_put_contents('build/' . $this->buildId . '/' . $moduleName . '/Controller/' . $moduleName . 'Controller.php', $code);

        return true;
    }
}