<?php

namespace Del\Generator\EntityModule;

use Del\Generator\FileGenerator;

class ViewGenerator extends FileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return bool
     */
    public function generateFile(string $nameSpace, string $entityName, array $fields): bool
    {
        $name = strtolower($entityName);

        // create
        $code = '<a href="/' . $name . '" class="btn btn-default pull-right">Back</a>
<h1>Add a ' . $entityName . '</h1>
<?= $msg . $form;' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/create.php', $code);

        // delete
        $code = '<a href="/' . $name . '" class="btn btn-default pull-right">Back</a>
<h1>Delete ' . $entityName . '</h1>
<?= $msg . $form;' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/delete.php', $code);

        // edit
        $code = '<a href="/' . $name . '" class="btn btn-default pull-right">Back</a>
<h1>Edit ' . $entityName . '</h1>
<?= $msg . $form;' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/edit.php', $code);

        // index
        $code = '<?php
use Del\Icon;
?>
<a href="/' . $name . '/create" class="btn btn-default pull-right"><?= Icon::ADD ;?> Add a ' . $entityName . '</a>
<h1>' . $entityName . ' Admin</h1>
<?= $paginator ?>
<table class="table table-condensed table-bordered">
    <thead>
        <tr>
            <td>Id</td>
            <td>Name</td>
            <td>Edit</td>
            <td>Delete</td>
        </tr>
    </thead>
    <tbody>
    <?php
    /** @var \BoneMvc\Module\\' . $entityName . '\Entity\\' . $entityName . ' $' . $name . ' */
    foreach ($' . $name . 's as $' . $name . ') { ?>
        <tr>
            <td><a href="/' . $name . '/<?= $' . $name . '->getId() ?>"><?= $' . $name . '->getId() ;?></a></td>
            <td><?= $' . $name . '->getName() ;?></td>
            <td><a href="/' . $name . '/edit/<?= $' . $name . '->getId() ?>"><?= Icon::EDIT ;?></a></td>
            <td><a href="/' . $name . '/delete/<?= $' . $name . '->getId() ?>"><?= Icon::REMOVE ;?></a></td>
        </tr>
    <?php } ?>
    </tbody>

</table>' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/index.php', $code);


        // view
        $code = '<?php
use Del\Icon;

/** @var \BoneMvc\Module\\' . $entityName . '\Entity\\' . $entityName . ' $' . $name . ' */
?>
<a href="/' . $name . '" class="btn btn-default pull-right"><?= Icon::CARET_LEFT ;?> Back</a>

<h1>View ' . $entityName . '</h1>
<div class="">
    <h2><?= $' . $name . '->getName() ?></h2>
</div>
<a href="/' . $name . '/edit/<?= $' . $name . '->getId() ?>" class="btn btn-default">
    <?= Icon::EDIT ;?> Edit
</a>' . "\n";
        file_put_contents('build/' . $this->buildId . '/' . $entityName . '/View/' . $entityName . '/view.php', $code);

        /** @todo ask which fields need displayed and searchable */

        return true;
    }
}