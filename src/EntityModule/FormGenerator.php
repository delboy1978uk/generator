<?php

namespace Del\Generator\EntityModule;

use Del\Form\AbstractForm;
use Del\Form\Field\Submit;
use Del\Form\Field\Text;
use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class FormGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($moduleNamespace . '\\Form');

        $namespace->addUse($moduleNamespace . '\\Entity\\' . $entityName);
        $namespace->addUse(AbstractForm::class);
        $namespace->addUse(Submit::class);
        $namespace->addUse(Text::class);

        $class = new ClassType($entityName . 'Form');
        $class->addExtend(AbstractForm::class);

        $namespace->add($class);

        // getEntityPath
        $method = $class->addMethod('init');
        $body = $this->createFormInitMethod($fields);
        $method->setBody($body);
        $method->setReturnType('void');

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Form/' . $entityName . 'Form.php', $code);

        return true;
    }

    /**
     * @param array $fields
     * @return string
     */
    private function createFormInitMethod(array $fields)
    {
        $body = '';

        foreach ($fields as $field) {
            $body .= $this->createFieldInitMethod($field);
        }

        $body .= '$submit = new Submit(\'submit\');' . "\n";
        $body .= '$this->addField($submit);';

        return $body;
    }

    /**
     * @param array $field
     * @return string
     */
    private function createFieldInitMethod(array $field)
    {
        switch ($field['type']) {
            case 'date':
                $body = '$' . $field['name'] . ' = new Text(\'' . $field['name'] . '\');' . "\n";
                $body .= '$' . $field['name'] . "->setClass('form-control datepicker');\n";
                break;
            case 'datetime':
                $body = '$' . $field['name'] . ' = new Text(\'' . $field['name'] . '\');' . "\n";
                $body .= '$' . $field['name'] . "->setClass('form-control datetimepicker');\n";
                break;
            case 'varchar':
            default:
                $body = '$' . $field['name'] . ' = new Text(\'' . $field['name'] . '\');' . "\n";
        }

        $body .= '$' . $field['name'] . '->setLabel(\'' . ucfirst($field['name']) . '\');' . "\n";

        if (!$field['nullable']) {
            $body .= '$' . $field['name'] . '->setRequired(true);' . "\n";
        }

        if (isset($field['filters'])) {
            $filters = $field['filters'];
            foreach ($filters as $filter) {
                $body .= '// add ' . $filter . 'filter here' . "\n";
            }
        }

        if (isset($field['validators'])) {
            $validators = $field['validators'];
            foreach ($validators as $validator) {
                $body .= '// add ' . $validator . 'validator here' . "\n";
            }
        }

        $body .= '$this->addField($' . $field['name'] . ');' . "\n\n";

        return $body;
    }
}