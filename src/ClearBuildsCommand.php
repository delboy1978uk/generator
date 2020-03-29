<?php

namespace Del\Generator;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearBuildsCommand extends Command
{

    /**
     * GeneratorCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('clear');
    }

    /**
     * configure options
     */
    protected function configure()
    {
        $this->setDescription('Clears the build folder out');
        $this->setHelp('Clears the build folder out');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Deleting old builds...');
        $folders = glob('./build/_gen*');

        foreach ($folders as $folder) {
            $this->deleteDir($folder);
            $output->writeln('Removing ' . $folder);
        }

        return 0;
    }

    /**
     * @param string $dirPath
     */
    private static function deleteDir(string $dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }

        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }
}