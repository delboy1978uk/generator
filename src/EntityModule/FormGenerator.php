<?php

namespace Del\Generator\EntityModule;

use Del\Form\AbstractForm;
use Del\Form\Field\CheckBox;
use Del\Form\Field\FileUpload;
use Del\Form\Field\Radio;
use Del\Form\Field\Select;
use Del\Form\Field\Submit;
use Del\Form\Field\Text;
use Del\Form\Field\TextArea;
use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
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
        $this->addElementUseStatements($namespace, $fields);

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
     * @param PhpNamespace $namespace
     * @param array $fields
     */
    private function addElementUseStatements(PhpNamespace $namespace, array $fields): void
    {
        $used = [];
        foreach ($fields as $field) {
            $type = $field['form']['type'];
            if (!in_array($type, $used)) {
                $used[] = $type;
            }
        }
        reset($fields);

        $formClasses  =  [
            'checkbox' => CheckBox::class,
            'file' => FileUpload::class,
            'radio' => Radio::class,
            'select' => Select::class,
            'text' => Text::class,
            'textarea' => TextArea::class,
        ];

        foreach ($used as $use) {
            $namespace->addUse($formClasses[$use]);
        }
    }

    /**
     * @param array $fields
     * @return string
     */
    private function createFormInitMethod(array $fields): string
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
    private function createFieldInitMethod(array $field): string
    {
        $addOptions = false;
        switch ($field['form']['type']) {
            case 'checkbox':
                $body = $this->createFieldObject( $field['name'], 'CheckBox');
                $addOptions = true;
                break;
            case 'file':
                $body = $this->createFieldObject( $field['name'], 'FileUpload');
                break;
            case 'radio':
                $body = $this->createFieldObject( $field['name'], 'Radio');
                $addOptions = true;
                break;
            case 'select':
                $body = $this->createFieldObject( $field['name'], 'Select');
                $addOptions = true;
                break;
            case 'textarea':
                $body = $this->createFieldObject( $field['name'], 'TextArea');
                break;
            case 'text':
            default:
                $body = $this->createFieldObject( $field['name'], 'Text');
        }

        switch ($field['type']) {
            case 'date':
                $body .= $this->addClassToField($field['name'],'form-control datepicker');
                break;
            case 'datetime':
                $body .= $this->addClassToField($field['name'], 'form-control datetimepicker');
                break;
            default:
        }

        if ($addOptions) {
            $body .= $this->setOptions($field['name'], $field['form']['values']);
        }

        $body .= $this->setFieldLabel($field['name'], $field['form']['label']);

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

    /**
     * @param string $fieldName
     * @param string $label
     * @return string
     */
    private function setOptions(string $fieldName, array $options): string
    {
        $body = '$' . $fieldName . '->setOptions([' . "\n";
        foreach ($options as $key => $value) {
            $key = is_int($key) ? $key : "'$key'";
            $value = is_numeric($value) ? $value : "'$value'";
            $body .= "     $key => $value,\n";
        }
        return $body . "]);\n";
    }

    /**
     * @param string $fieldName
     * @param string $label
     * @return string
     */
    private function setFieldLabel(string $fieldName, string $label): string
    {
        return '$' . $fieldName . '->setLabel(\'' . $label . '\');' . "\n";
    }

    /**
     * @param string $fieldName
     * @param string $class
     * @return string
     */
    private function createFieldObject(string $fieldName, string $class): string
    {
        return '$' . $fieldName . ' = new ' . $class .'(\'' . $fieldName . '\');' . "\n";
    }

    /**
     * @param string $fieldName
     * @param string $class
     * @return string
     */
    private function addClassToField(string $fieldName, string $class): string
    {
        return '$' . $fieldName . "->setClass('" . $class . "');\n";
    }
}