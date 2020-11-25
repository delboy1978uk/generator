<?php

namespace Del\Generator\EntityModule;

use Bone\User\Http\Middleware\SessionAuth;
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
        $namespace->addUse('Barnacle\EntityRegistrationInterface');
        $namespace->addUse('Barnacle\RegistrationInterface');
        $namespace->addUse('Bone\Http\Middleware\HalCollection');
        $namespace->addUse('Bone\Http\Middleware\HalEntity');
        $namespace->addUse('Bone\Router\Router');
        $namespace->addUse('Bone\Router\RouterConfigInterface');
        $namespace->addUse('Bone\User\Http\Middleware\SessionAuth');
        $namespace->addUse('Bone\View\ViewRegistrationInterface');
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
        $class->addImplement('Barnacle\EntityRegistrationInterface');
        $class->addImplement('Bone\View\ViewRegistrationInterface');

        // add to container
        $method = $class->addMethod('addToContainer');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->setBody('$c[' . $entityName . 'Service::class] = $c->factory(function (Container $c) {
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



        // getViews
        $method = $class->addMethod('addViews');
        $method->setBody("return ['" . strtolower($entityName) . "' => __DIR__ . '/View'];");
        $method->addComment('@return array');
        $method->setReturnType('array');

        $method = $class->addMethod('addViewExtensions');
        $method->setBody('return [];');
        $method->addComment('@return array');
        $method->setReturnType('array');

        // getEntityPath
        $method = $class->addMethod('getEntityPath');
        $method->setBody("return __DIR__ . '/Entity';");
        $method->addComment('@return string');
        $method->setReturnType('string');

        // addRoutes
        $method = $class->addMethod('addRoutes');
        $method->addComment('@param Container $c');
        $method->addComment('@param Router $router');
        $method->addComment('@return Router');
        $method->addParameter('c')->setTypeHint('Barnacle\Container');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->addParameter('router')->setTypeHint('Bone\Router\Router');
        $method->setReturnType('Bone\Router\Router');
        $method->setBody('$auth = $c->get(SessionAuth::class);
$router->group(\'/dog\', function (RouteGroup $route) {
    $route->map(\'GET\', \'/\', [' . $entityName . 'Controller::class, \'index\']);
    $route->map(\'GET\', \'/{id:number}\', [' . $entityName . 'Controller::class, \'view\']);
    $route->map(\'GET\', \'/create\', [' . $entityName . 'Controller::class, \'create\']);
    $route->map(\'GET\', \'/edit/{id:number}\', [' . $entityName . 'Controller::class, \'edit\']);
    $route->map(\'GET\', \'/delete/{id:number}\', [' . $entityName . 'Controller::class, \'delete\']);

    $route->map(\'POST\', \'/' . $name . '/create\', [' . $entityName . 'Controller::class, \'create\']);
    $route->map(\'POST\', \'/' . $name . '/edit/{id:number}\', [' . $entityName . 'Controller::class, \'edit\']);
    $route->map(\'POST\', \'/' . $name . '/delete/{id:number}\', [' . $entityName . 'Controller::class, \'delete\']);
})->middlewares([$auth]);

$factory = new ResponseFactory();
$strategy = new JsonStrategy($factory);
$strategy->setContainer($c);

$router->group(\'/api\', function (RouteGroup $route) {
    $route->map(\'GET\', \'/' . $name . '\', [' . $entityName . 'ApiController::class, \'index\'])->prependMiddleware(new HalCollection(5));
    $route->map(\'GET\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'view\'])->prependMiddleware(new HalEntity());
    $route->map(\'POST\', \'/' . $name . '\', [' . $entityName . 'ApiController::class, \'create\']);
    $route->map(\'PUT\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'update\']);
    $route->map(\'DELETE\', \'/' . $name . '/{id:number}\', [' . $entityName . 'ApiController::class, \'delete\']);
})
->setStrategy($strategy);

return $router;');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/' . $entityName . 'Package.php', $code);

        return true;
    }
}