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
/** @var \Bone\\{$entityName}\Entity\\{$entityName} \${$name} */
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= Icon::SHIELD ?>&nbsp;&nbsp;{$entityName} Admin - View</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/{$name}">{$entityName}</a></li>
                    <li class="breadcrumb-item active">View {$entityName}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="card card-primary card-outline col-md-12">
                <div class="card-body p-10">
                    <div class="mailbox-read-info">
                        <h2><?= \${$name}->getName() ?></h2>
                    </div>
                    <div class="mailbox-read-message">
                        <p>Details will go here</p>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="float-right">
                        <a href="/admin/{$name}" class="btn btn-default"><i class="fa fa-backward"></i> Back</a>
                        <a href="/admin/{$name}/edit/<?= \${$name}->getId() ?>" class="btn btn-primary"><?= Icon::EDIT ;?> Edit</a>
                    </div>
                    <a href="/admin/{$name}/delete/<?= \${$name}->getId() ?>" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
        </div>
    </div>
</section>

EOL;

        return $code;
    }
}