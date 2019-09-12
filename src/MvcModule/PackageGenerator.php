<?php

namespace Del\Generator\MvcModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class PackageGenerator extends FileGenerator
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
        $name = strtolower($moduleName);
        $namespace = $file->addNamespace($moduleNamespace);

        $namespace->addUse('Barnacle\Container');
        $namespace->addUse('Barnacle\RegistrationInterface');
        $namespace->addUse('Bone\Mvc\Router\RouterConfigInterface');
        $namespace->addUse('Bone\Mvc\View\PlatesEngine');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $moduleName . 'ApiController');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $moduleName . 'Controller');
        $namespace->addUse('League\Route\RouteGroup');
        $namespace->addUse('League\Route\Router');
        $namespace->addUse('League\Route\Strategy\JsonStrategy');
        $namespace->addUse('Zend\Diactoros\ResponseFactory');

        $class = $namespace->addClass($moduleName . 'Package');
        $class->addImplement('Barnacle\RegistrationInterface');
        $class->addImplement('Bone\Mvc\Router\RouterConfigInterface');

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->setBody('/** @var PlatesEngine $viewEngine */
$viewEngine = $c->get(PlatesEngine::class);
$viewEngine->addFolder(\'' . $name . '\', \'src/' . $moduleName . '/View/' . $moduleName . '/\');

$c[' . $moduleName . 'Controller::class] = $c->factory(function (Container $c) {
    /** @var PlatesEngine $viewEngine */
    $viewEngine = $c->get(PlatesEngine::class);

    return new ' . $moduleName . 'Controller($viewEngine);
});

$c[' . $moduleName . 'ApiController::class] = $c->factory(function (Container $c) {
    return new ' . $moduleName . 'ApiController();
});');
        $method->addComment('@param Container $c');

        // getEntityPath
        $method = $class->addMethod('getEntityPath');
        $method->setBody("return '';");
        $method->addComment('@return string');
        $method->setReturnType('string');

        // hasEntityPath
        $method = $class->addMethod('hasEntityPath');
        $method->setBody("return false;");
        $method->addComment('@return bool');
        $method->setReturnType('bool');

        // addRoutes
        $method = $class->addMethod('addRoutes');
        $method->addComment('@param Container $c');
        $method->addComment('@param Router $router');
        $method->addComment('@return Router');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->addParameter('router')->setTypeHint('League\Route\Router');
        $method->addParameter('router')->setTypeHint('League\Route\Router');
        $method->setReturnType('League\Route\Router');
        $method->setBody('$router->map(\'GET\', \'/' . $name . '\', [' . $moduleName . 'Controller::class, \'indexAction\']);

$factory = new ResponseFactory();
$strategy = new JsonStrategy($factory);
$strategy->setContainer($c);

$router->group(\'/api\', function (RouteGroup $route) {
    $route->map(\'GET\', \'/' . $name . '\', [' . $moduleName . 'ApiController::class, \'indexAction\']);
})
->setStrategy($strategy);

return $router;');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $moduleName . '/' . $moduleName . 'Package.php', $code);

        return true;
    }
}