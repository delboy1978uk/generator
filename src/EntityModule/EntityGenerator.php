<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class EntityGenerator extends FileGenerator
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
        $namespace = $file->addNamespace($nameSpace . '\\' . $entityName . '\\Entity');
        $namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $namespace->addUse('JsonSerializable');
        $class = new ClassType($entityName);
        $class->addComment('@ORM\Entity(repositoryClass="\\' . $nameSpace . '\\' . $entityName . '\Repository\\' . $entityName . 'Repository")');

        $id = $class->addProperty('id');
        $id->setVisibility('private');
        $id->addComment('@var int $id');
        $id->addComment('@ORM\Id');
        $id->addComment('@ORM\Column(type="integer")');
        $id->addComment('@ORM\GeneratedValue');

        $method = $class->addMethod('getId');
        $method->setReturnType('int');
        $method->setReturnNullable();
        $method->setBody('return $this->id;');
        $method->addComment('@return int');

        $method = $class->addMethod('setId');
        $method->setReturnType('void');
        $method->addParameter('id');
        $method->addComment('@param int $id');
        $method->setBody('$this->id = $id;');

        $useDateTime = false;

        foreach ($fields as $fieldInfo) {

            $name = $fieldInfo['name'];
            $type = $fieldInfo['type'];

            switch ($type) {
                case 'bool':
                    $type = 'boolean';
                    $var = 'bool';
                    $typeHint = 'bool';
                    break;
                case 'varchar':
                    $type = 'string';
                    $var = 'string';
                    $typeHint = 'string';
                    break;
                case 'double':
                case 'decimal':
                case 'float':
                    $var = ($type === 'decimal') ? 'float' : $type;
                    $type = ($type !== 'decimal') ? 'float' : $type;
                    $typeHint = 'float';
                    $fieldInfo['precision'] = $fieldInfo['length'];
                    unset($fieldInfo['length']);
                    break;
                case 'int':
                    $type = 'integer';
                    $var = 'int';
                    $typeHint = 'int';
                    break;
                case 'date':
                case 'datetime':
                    $var = 'DateTime';
                    $typeHint = 'DateTime';
                    if (!$useDateTime) {
                        $useDateTime = true;
                        $namespace->addUse('DateTime');
                    }
                    break;
            }

            $typeString = 'type="' . $type . '"';

            if (isset($fieldInfo['length'])) {
                $typeString .= ', length=' . $fieldInfo['length'];
            }

            if (isset($fieldInfo['decimals'])) {
                $typeString .= ', precision=' . $fieldInfo['precision'] . ', scale=' . $fieldInfo['decimals'];
            }

            $isNullable = $fieldInfo['nullable'] ? 'true' : 'false';
            $typeString .= ', nullable=' . $isNullable;

            $field = $class->addProperty($name);
            $field->setVisibility('private');
            $field->addComment('@var ' . $var . ' $' . $name);
            $field->addComment('@ORM\Column(' . $typeString . ')');

            $method = $class->addMethod('get' . ucfirst($name));
            $method->setBody('return $this->' . $name . ';');
            $method->addComment('@return ' . $var);
            $method->setReturnType($var);
            $nullableTypeDoc = '';
            if ($fieldInfo['nullable']) {
                $method->setReturnNullable();
                $nullableTypeDoc = '|null';
            }

            $method = $class->addMethod('set' . ucfirst($name));
            $parameter = $method->addParameter($name)->setTypeHint($typeHint);
            if ($fieldInfo['nullable']) {
                $parameter->setNullable(true);
            }
            $method->addComment('@param ' . $var . $nullableTypeDoc . ' $' . $name);
            $method->setBody('$this->' . $name . ' = $' . $name . ';');
            $method->setReturnType('void');
        }
        reset($fields);

        // toArray()
        $addDateFormatParam = false;
        $method = $class->addMethod('toArray');
        $method->addComment('@return array');
        $body = '$data = [' . "\n";
        $body .= "    'id' => \$this->getId(),\n";

        foreach ($fields as $field) {
            switch ($field['type']) {
                case 'date':
                    $addDateFormatParam = true;
                    $body .= "    '{$field['name']}' => ($" . $field['name'] . " = \$this->get" . ucfirst($field['name']) . "()) ? $" . $field['name'] . "->format(\$dateFormat) : null,\n";
                    break;
                case 'datetime':
                    $addDateFormatParam = true;
                    $body .= "    '{$field['name']}' => ($" . $field['name'] . " = \$this->get" . ucfirst($field['name']) . "()) ? $" . $field['name'] . "->format(\$dateFormat . ' H:i') : null,\n";
                    break;
                case 'varchar':
                default:
                    $body .= "    '{$field['name']}' => \$this->get" . ucfirst($field['name']) . "(),\n";
            }
        }

        if ($addDateFormatParam) {
            $method->addComment('@param string $dateFormat');
            $method->addParameter('dateFormat')->setTypeHint('string')->setDefaultValue('d/m/Y');
        }

        $body .= "];\n\nreturn \$data;";
        $method->setBody($body);
        $method->setReturnType('array');
        reset($fields);

        // toJson()
        $method = $class->addMethod('jsonSerialize');
        $method->addComment('@return string');
        $body = 'return \json_encode($this->toArray());';
        $method->setBody($body);
        $method->setReturnType('string');

        // toString()
        $method = $class->addMethod('__toString');
        $method->addComment('@return string');
        $body = 'return $this->jsonSerialize();';
        $method->setBody($body);
        $method->setReturnType('string');

        $namespace->add($class);

        $printer = new PsrPrinter();
        $code = $printer->printFile($file);
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/Entity/' . $entityName . '.php', $code);

        return true;
    }
}