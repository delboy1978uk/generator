<?php

namespace Del\Generator\EntityModule\View;

class EditPage implements ViewFileGenerator
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

use Del\Icon; ?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= Icon::SHIELD ?>&nbsp;&nbsp;{$entityName} Admin - Edit</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/{$name}">{$entityName}</a></li>
                    <li class="breadcrumb-item active">Edit {$entityName}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?= \$msg ?>
        <div class="row justify-content-center">
            <div class="card card-primary card-outline col-md-12">
                <br>&nbsp;
                <div class="col justify-content-center">
                    <?= \$form ?>
                </div>
            </div>
        </div>
    </div>
</section>

EOL;

        return $code;
    }
}