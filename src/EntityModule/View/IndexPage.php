<?php

namespace Del\Generator\EntityModule\View;

class IndexPage implements ViewFileGenerator
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
/** @var \Bone\\{$entityName}\Entity\\{$entityName}[] \${$name}s */

use Del\Icon;

?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= Icon::SHIELD ?>&nbsp;&nbsp;{$entityName} Admin</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item active">{$entityName} Admin</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col"><?= \$paginator ?></div>
            <div class="col">
                <div class="input-group">
                    <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="col"><a href="/admin/{$name}/create" class="btn btn-primary pull-right"><?= Icon::ADD ?> Add a {$entityName}</a></div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline table-responsive p-0">
                    <table class="table card-body table-hover text-nowrap">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Date of birth</th>
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count(\${$name}s)) {
                            foreach (\${$name}s as \${$name}) { ?>
                                <tr>
                                    <td><a href="/admin/{$name}/<?= \${$name}->getId() ?>"><?= \${$name}->getId() ?></a></td>
                                    <td><?= \${$name}->getName() ?></td>
                                    <td><?= \${$name}->getDob()->format('d M Y') ?></td>
                                    <td><a href="/admin/{$name}/edit/<?= \${$name}->getId() ?>"><?= Icon::EDIT ;?></a></td>
                                    <td><a href="/admin/{$name}/delete/<?= \${$name}->getId() ?>"><?= Icon::REMOVE ;?></a></td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="5" class="text-danger">No records have been found in the database.</td>
                            </tr>
                        <?php } ?>
    
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

EOL;

        return $code;
    }
}