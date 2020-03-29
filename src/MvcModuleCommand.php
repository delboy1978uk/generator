<?php

namespace Del\Generator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MvcModuleCommand extends Command
{

    /** @var QuestionHelper $helper */
    private $helper;

    /**
     * GeneratorCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('mvc');
    }

    /**
     * configure options
     */
    protected function configure()
    {
        $this->setDescription('Generates a blank MVC module');
        $this->setHelp('Create an empty MVC module');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->helper = $this->getHelper('question');
        $output->writeln('Empty Module Generator');
        $output->writeln('');

        $question = new Question('Enter the base namespace: ', 'BoneMvc\\Module');
        $nameSpace = $this->helper->ask($input, $output, $question);

        $question = new Question('Enter the name of the module: ', false);
        $entityName = $this->helper->ask($input, $output, $question);
        $output->writeln('');

        $generator = new GeneratorService();

        if ($buildId = $generator->createMvcModule($nameSpace, $entityName)) {
            $output->writeln('Successfully generated in build/' . $buildId . '.');
        }

        return 0;
    }
}