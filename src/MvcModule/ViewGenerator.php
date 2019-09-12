<?php

namespace Del\Generator\MvcModule;

use Del\Generator\FileGenerator;

class ViewGenerator extends FileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $moduleName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $moduleName, array $fields): bool
    {
        $name = strtolower($moduleName);

        // index
        $code = '<?php
use Del\Icon;
?>
<h1>' . $moduleName . '</h1>
<p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci commodi consectetur consequuntur, 
delectus modi nulla praesentium vel voluptatum. Ad aperiam debitis in officia placeat porro quae quaerat similique 
velit voluptates.</p>' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $moduleName . '/View/' . $moduleName . '/index.php', $code);

        return true;
    }
}