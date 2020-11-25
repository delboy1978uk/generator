<?php

namespace Del\Generator\EntityModule;

use Del\Generator\EntityModule\View\CreatePage;
use Del\Generator\EntityModule\View\DeletePage;
use Del\Generator\EntityModule\View\EditPage;
use Del\Generator\EntityModule\View\IndexPage;
use Del\Generator\EntityModule\View\ViewPage;
use Del\Generator\FileGenerator;

class ViewGenerator extends FileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $entityName, array $fields): bool
    {
        // create
        $name = strtolower($entityName);
        $generator = new CreatePage();
        $code = $generator->generatePage($nameSpace, $entityName, $fields);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/create.php', $code);

        // delete
        $generator = new DeletePage();
        $code = $generator->generatePage($nameSpace, $entityName, $fields);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/delete.php', $code);

        // edit
        $generator = new EditPage();
        $code = $generator->generatePage($nameSpace, $entityName, $fields);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/edit.php', $code);

        // index
        $generator = new IndexPage();
        $code = $generator->generatePage($nameSpace, $entityName, $fields);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/index.php', $code);


        // view
        $generator = new ViewPage();
        $code = $generator->generatePage($nameSpace, $entityName, $fields);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/view.php', $code);

        /** @todo ask which fields need displayed and searchable */

        return true;
    }
}