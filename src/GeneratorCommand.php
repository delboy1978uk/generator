<?php

namespace Del\Generator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('save', 's', InputOption::VALUE_OPTIONAL, 'Saves the generated config as a json file.', false);
        $this->addOption('load', 'l', InputOption::VALUE_OPTIONAL, 'Loads a generator config from a json file.', false);
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

        if ($input->getOption('load') !== false) {
            return $this->generateFromConfigFile($input, $output);
        }

        $question = new Question('Enter the base namespace: ', 'BoneMvc\\Module');
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

            $question = new ChoiceQuestion('What type of data does it hold? ', [
                'date', 'datetime', 'decimal', 'double', 'float', 'int', 'varchar', 'bool'
            ]);
            $field['type'] = $this->helper->ask($input, $output, $question);

            switch ($field['type']) {
                case 'decimal':
                    $field['decimals'] = 2;
                case 'double':
                case 'float':
                case 'int':
                case 'varchar':
                    $question = new Question('What length is the field? ');
                    $field['length'] = $this->helper->ask($input, $output, $question);
                    break;
                default:
                    break;
            }

            if (in_array($field['type'], ['double', 'float'])) {
                $question = new Question('How many decimal places for your ' . $field['type'] . '?');
                $field['decimals'] = $this->helper->ask($input, $output, $question);
            }

            $question = new ConfirmationQuestion('Is the field nullable?  (Y/n)', true);
            $field['nullable'] = $this->helper->ask($input, $output, $question);

            switch ($field['type']) {
                case 'bool':
                    $formTypes = ['checkbox'];
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                case 'int':
                    $formTypes = ['radio', 'select', 'text'];
                    break;
                case 'date':
                case 'datetime':
                    $formTypes = ['text'];
                    break;
                default:
                case 'varchar':
                    $formTypes = ['file', 'radio', 'select', 'text'];
            }

            $field['form'] = [];
            if (count($formTypes) > 1) {
                $question = new ChoiceQuestion('What type of form field does it have? ', $formTypes);
                $field['form']['type'] = $this->helper->ask($input, $output, $question);
            } else {
                $field['form']['type'] = $formTypes[0];
            }

            $question = new Question('Type in a label for your ' . $field['form']['type'] . ': ');
            $field['form']['label'] = $this->helper->ask($input, $output, $question);


            $field['form']['values'] = [];
            switch ($field['form']['type']) {
                case 'radio':
                case 'select':
                    $askForValues = true;
                    break;
                case 'checkbox':
                    $field['form']['values']  = [1 => ''];
                    break;
                case 'file':
                case 'text':
                case 'textarea':
                default:
                    $askForValues = false;
            }

            if ($askForValues === true) {
                $question = new ConfirmationQuestion('Add an option for your ' . $field['form']['type'] . '? (Y/n)', true);
                while ($this->helper->ask($input, $output, $question)) {

                    $question = new Question('Enter the field\'s value: ');
                    $value = $this->helper->ask($input, $output, $question);

                    $question = new Question('Enter the display value: ');
                    $label = $this->helper->ask($input, $output, $question);

                    $field['form']['values'][$value] = $label;
                    $question = new ConfirmationQuestion('Add another option for your ' . $field['form']['type'] . '? (Y/n)', true);
                }
            }

            $fields[] = $field;

            $question = new ConfirmationQuestion('Create another field? (Y/n)', true);
            $output->writeln('');
        }

        if ($input->getOption('save') !== false) {
            $save = $input->getOption('save') ?: 'generator';
            $save = str_replace('.json', '', $save);
            $save = $save . '.json';
            $output->writeln('Saving Config as ' . $save);
            $config = [
                'namespace' => $nameSpace,
                'entity' => $entityName,
                'fields' => $fields,
            ];
            $json = json_encode($config);
            file_put_contents($save, $json);
        }
        
        $output->writeln('');
        $generator = new GeneratorService();

        if ($buildId = $generator->createEntityModule($nameSpace, $entityName, $fields)) {
            $output->writeln('Successfully generated in build/' . $buildId . '.');
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exceptio
     */
    private function generateFromConfigFile(InputInterface $input, OutputInterface $output): void
    {
        $filename = $input->getOption('load') ?: 'generator';
        $filename = str_replace('.json', '', $filename);
        $filename = $filename . '.json';
        $output->writeln('Generating from config file ' . $filename);
        if (!$json = file_get_contents($filename)) {
            $output->writeln('Failed to load ' . $filename);
            return;
        }
        $output->writeln('');
        $config = json_decode($json, true);
        $namespace = $config['namespace'];
        $entity = $config['entity'];
        $fields = $config['fields'];

        $generator = new GeneratorService();

        if ($buildId = $generator->createEntityModule($namespace, $entity, $fields)) {
            $output->writeln('Successfully generated in build/' . $buildId . '.');
        }
    }
}