<?php

namespace Del\Generator;

interface FileGeneratorInterface
{
    public function __construct(string $buildId);

    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $entityName, array $fields): bool;
}