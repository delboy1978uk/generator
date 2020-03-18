<?php

namespace Del\Generator\MvcModule;

use Del\Generator\FileGenerator;
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
        $namespace->addUse('Barnacle\EntityRegistrationInterface');
        $namespace->addUse('Barnacle\RegistrationInterface');
        $namespace->addUse('Bone\Controller\Init');
        $namespace->addUse('Bone\Router\Router');
        $namespace->addUse('Bone\Router\RouterConfigInterface');
        $namespace->addUse('Bone\View\ViewEngine');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $moduleName . 'ApiController');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $moduleName . 'Controller');
        $namespace->addUse('League\Route\RouteGroup');
        $namespace->addUse('League\Route\Strategy\JsonStrategy');
        $namespace->addUse('Laminas\Diactoros\ResponseFactory');

        $class = $namespace->addClass($moduleName . 'Package');
        $class->addImplement('Barnacle\RegistrationInterface');
        $class->addImplement('Bone\Router\RouterConfigInterface');
        $class->addImplement('Barnacle\EntityRegistrationInterface');

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->setBody('/** @var ViewEngine $viewEngine */
$viewEngine = $c->get(ViewEngine::class);
$viewEngine->addFolder(\'' . $name . '\', __DIR__ . \'/View/' . $moduleName . '/\');

$c[' . $moduleName . 'Controller::class] = $c->factory(function (Container $c) {
    return Init::controller(new ' . $moduleName . 'Controller(), $c);
});

$c[' . $moduleName . 'ApiController::class] = $c->factory(function (Container $c) {
    return new ' . $moduleName . 'ApiController();
});');
        $method->addComment('@param Container $c');

        // addRoutes
        $method = $class->addMethod('addRoutes');
        $method->addComment('@param Container $c');
        $method->addComment('@param Router $router');
        $method->addComment('@return Router');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->setReturnType('Bone\Router\Router');
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