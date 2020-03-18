<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class PackageGenerator extends FileGenerator
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
        $name = strtolower($entityName);
        $namespace = $file->addNamespace($moduleNamespace);

        $namespace->addUse('Barnacle\Container');
        $namespace->addUse('Barnacle\RegistrationInterface');
        $namespace->addUse('Bone\Http\Middleware\HalCollection');
        $namespace->addUse('Bone\Http\Middleware\HalEntity');
        $namespace->addUse('Bone\Router\Router');
        $namespace->addUse('Bone\Router\RouterConfigInterface');
        $namespace->addUse('Bone\View\ViewEngine');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $entityName . 'ApiController');
        $namespace->addUse($moduleNamespace . '\\Controller\\' . $entityName . 'Controller');
        $namespace->addUse($moduleNamespace . '\\Service\\' . $entityName . 'Service');
        $namespace->addUse('Doctrine\ORM\EntityManager');
        $namespace->addUse('League\Route\RouteGroup');
        $namespace->addUse('League\Route\Strategy\JsonStrategy');
        $namespace->addUse('Laminas\Diactoros\ResponseFactory');

        $class = $namespace->addClass($entityName . 'Package');
        $class->addImplement('Barnacle\RegistrationInterface');
        $class->addImplement('Bone\Router\RouterConfigInterface');

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->setBody('/** @var ViewEngine $viewEngine */
$viewEngine = $c->get(ViewEngine::class);
$viewEngine->addFolder(\'' . $name . '\', __DIR__ . \'/View/' . $entityName . '/\');

$c[' . $entityName . 'Service::class] = $c->factory(function (Container $c) {
    $em =  $c->get(EntityManager::class);
    
    return new ' . $entityName . 'Service($em);
});

$c[' . $entityName . 'Controller::class] = $c->factory(function (Container $c) {
    $service = $c->get(' . $entityName . 'Service::class);
    /** @var ViewEngine $viewEngine */
    $viewEngine = $c->get(ViewEngine::class);

    return new ' . $entityName . 'Controller($viewEngine, $service);
});

$c[' . $entityName . 'ApiController::class] = $c->factory(function (Container $c) {
    $service = $c->get(' . $entityName . 'Service::class);

    return new ' . $entityName . 'ApiController($service);
});');
        $method->addComment('@param Container $c');

        // getEntityPath
        $method = $class->addMethod('getEntityPath');
        $method->setBody("return __DIR__ . '/Entity';");
        $method->addComment('@return string');
        $method->setReturnType('string');

        // hasEntityPath
        $method = $class->addMethod('hasEntityPath');
        $method->setBody("return true;");
        $method->addComment('@return bool');
        $method->setReturnType('bool');

        // addRoutes
        $method = $class->addMethod('addRoutes');
        $method->addComment('@param Container $c');
        $method->addComment('@param Router $router');
        $method->addComment('@return Router');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->setReturnType('Bone\Router\Router');
        $method->setBody('$router->map(\'GET\', \'/' . $name . '\', [' . $entityName . 'Controller::class, \'indexAction\']);
$router->map(\'GET\', \'/' . $name . '/{id:number}\', [' . $entityName . 'Controller::class, \'viewAction\']);
$router->map(\'GET\', \'/' . $name . '/create\', [' . $entityName . 'Controller::class, \'createAction\']);
$router->map(\'GET\', \'/' . $name . '/edit/{id:number}\', [' . $entityName . 'Controller::class, \'editAction\']);
$router->map(\'GET\', \'/' . $name . '/delete/{id:number}\', [' . $entityName . 'Controller::class, \'deleteAction\']);

$router->map(\'POST\', \'/' . $name . '/create\', [' . $entityName . 'Controller::class, \'createAction\']);
$router->map(\'POST\', \'/' . $name . '/edit/{id:number}\', [' . $entityName . 'Controller::class, \'editAction\']);
$router->map(\'POST\', \'/' . $name . '/delete/{id:number}\', [' . $entityName . 'Controller::class, \'deleteAction\']);

$factory = new ResponseFactory();
$strategy = new JsonStrategy($factory);
$strategy->setContainer($c);

$router->group(\'/api\', function (RouteGroup $route) {
    $route->map(\'GET\', \'/' . $name . '\', [' . $entityName . 'ApiController::class, \'indexAction\'])->prependMiddleware(new HalCollection(5));
    $route->map(\'GET\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'viewAction\'])->prependMiddleware(new HalEntity());
    $route->map(\'POST\', \'/' . $name . '\', [' . $entityName . 'ApiController::class, \'createAction\']);
    $route->map(\'PUT\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'updateAction\']);
    $route->map(\'DELETE\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'deleteAction\']);
})
->setStrategy($strategy);

return $router;');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/' . $entityName . 'Package.php', $code);

        return true;
    }
}