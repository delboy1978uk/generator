<?php

namespace Del\Generator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GeneratorCommand extends Command
{

    /** @var QuestionHelper $helper */
    private $helper;

    /**
     * GeneratorCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('entity');
    }

    /**
     * configure options
     */
    protected function configure()
    {
        $this->setDescription('Generates a new entity and its related classes.');
        $this->setHelp('Create a new entity package');
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
        $output->writeln('Entity and Service Generator');
        $output->writeln('');

        $question = new Question('Enter the base namespace: ', false);
        $nameSpace = $this->helper->ask($input, $output, $question);

        $question = new Question('Enter the name of the entity: ', false);
        $entityName = $this->helper->ask($input, $output, $question);
        $output->writeln('');

        $question = new ConfirmationQuestion('Create a field? (Y/n)', true);

        $fields = [];
        while ($this->helper->ask($input, $output, $question)) {
            $field = [];

            $question = new Question('Enter the name of the field: ');
            $field['name'] = $this->helper->ask($input, $output, $question);

            $question = new ChoiceQuestion('What type of data does it hold?', [
                'date', 'datetime', 'decimal', 'double', 'float', 'int', 'varchar'
            ]);
            $field['type'] = $this->helper->ask($input, $output, $question);

            switch ($field['type']) {
                case 'decimal':
                    $field['decimals'] = 2;
                case 'double':
                case 'float':
                case 'int':
                case 'varchar':
                    $question = new Question('What length is the field?');
                    $field['length'] = $this->helper->ask($input, $output, $question);
                    break;
                default:
                    break;
            }

            if (in_array($field['type'], ['double', 'float'])) {
                $question = new Question('How many decimal places for your ' . $field['type'] . '?');
                $field['decimals'] = $this->helper->ask($input, $output, $question);
            }

            $fields[] = $field;

            $question = new ConfirmationQuestion('Create another field? (Y/n)', true);
        }




        $output->writeln('');
        $generator = new GeneratorService();

        if ($buildId = $generator->createEntityModule($nameSpace, $entityName, $fields)) {
            $output->writeln('Successfully generated in build/' . $buildId . '.');
        }
    }
}