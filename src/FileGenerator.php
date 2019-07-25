<?php

namespace Del\Generator;

abstract class FileGenerator implements FileGeneratorInterface
{
    /**
     * @var string$buildId
     */
    protected $buildId;

    public function __construct(string $buildId)
    {
        $this->buildId = $buildId;
    }
}