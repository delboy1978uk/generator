<?php

namespace Del\Generator\EntityModule\View;

interface ViewFileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return string
     */
    public function generatePage(string $nameSpace, string $entityName, array $fields): string;
}