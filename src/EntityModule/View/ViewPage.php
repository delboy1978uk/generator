<?php

namespace Del\Generator\EntityModule\View;

class ViewPage implements ViewFileGenerator
{
    /**
     * @param string $nameSpace
     * @param string $entityName
     * @param array $fields
     * @return string
     */
    public function generatePage(string $nameSpace, string $entityName, array $fields): string
    {
        $name = strtolower($entityName);

        $code = <<<EOL
<?php
use Del\Icon;
/** @var \Bone\\{$entityName}\Entity\\{$entityName} \${$name}} */
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= Icon::SHIELD ?>&nbsp;&nbsp;{$entityName} Admin - View</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/{$name}">{$entityName}</a></li>
                    <li class="breadcrumb-item active">View {$entityName}</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="text-center">
                <h2><?= \${$name}->getName() ?></h2>
                <br>
                <a href="/{$name}/edit/<?= \${$name}->getId() ?>" class="btn btn-primary">
                    <?= Icon::EDIT ;?> Edit
                </a>
            </div>
        </div>
    </div>
</section>


EOL;

        return $code;
    }
}