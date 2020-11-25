<?php

namespace Del\Generator\EntityModule\View;

class DeletePage implements ViewFileGenerator
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

    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><?= Icon::SHIELD ?>&nbsp;&nbsp;{$entityName} Admin - Delete</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                        <li class="breadcrumb-item"><a href="/{$name}">{$entityName}</a></li>
                        <li class="breadcrumb-item active">Delete {$entityName}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?= \$msg ?>
            <!-- Small boxes (Stat box) -->
            <div class="row justify-content-center">
                 <?= \$form ?>
            </div>
        </div>
    </section>

EOL;

        return $code;
    }
}