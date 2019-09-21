<?php

namespace Del\Generator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class VendorPackageCommand extends Command
{
    private $helper;

    /**
     * GeneratorCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('vendor');
    }

    /**
     * configure options
     */
    protected function configure()
    {
        $this->setDescription('Converts a build into a vendor package');
        $this->setHelp('Creates a vendor package from a build');
        $this->addArgument('buildId', InputArgument::REQUIRED, 'the build folder containing the code to package');
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

        $question = new Question('Enter the vendor name: ', '');
        $vendor = $this->helper->ask($input, $output, $question);
        $output->writeln('');
        $question = new Question('Enter the package name: ', '');
        $package = $this->helper->ask($input, $output, $question);
        $output->writeln('');
        $question = new Question('Enter the author name: ', '');
        $author = $this->helper->ask($input, $output, $question);
        $output->writeln('');
        $question = new Question('Enter the author\'s email: ', '');
        $email = $this->helper->ask($input, $output, $question);
        $output->writeln('');

        $buildId = $input->getArgument('buildId');
        $folder = realpath('./build/' . $buildId);
        if ($folder) {
            // rename folder to src
            $output->writeln('Examining build ' . $buildId);
            $folder = glob('./build/' . $buildId . '/*')[0];
            $parts = explode('/', $folder);
            $toReplace = array_pop($parts);
            $newPath = str_replace($toReplace, 'src', $folder);
            $output->writeln('Packaging ' . $folder . ' module...');
            rename($folder, $newPath);
            $newPath = str_replace('/src', '', $newPath);



            // namespace
            $packagePhp = file_get_contents($newPath . '/src/' . $toReplace . 'Package.php');
            preg_match('#namespace (?<namespace>.+);#', $packagePhp, $match);
            $namespace = $match['namespace'];

            // add README.md
            $output->writeln('Adding readme...');
            $readme = '# ' . strtolower($toReplace) . "\n";
            $readme .= "$toReplace package for Bone Mvc Framework\n";
            $readme .= '## installation' . "\n";
            $readme .= 'Use Composer' . "\n";
            $readme .= '```' . "\n";
            $readme .= "composer require $vendor/$package\n";
            $readme .= '```' . "\n";
            $readme .= '## usage' . "\n";
            $readme .= 'Simply add to the `config/packages.php`' . "\n";
            $readme .=  "```php
<?php

// use statements here
use {$namespace}\\{$toReplace}Package;

return [
    'packages' => [
        // packages here...,
        {$toReplace}Package::class,
    ],
    // ...
];
```";
            file_put_contents($newPath . '/README.md', $readme);

            // License
            $output->writeln('Adding license...');
            $license = "The MIT License (MIT)

Copyright (c) 2019 $author

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the \"Software\"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

";
            file_put_contents($newPath . '/LICENSE', $license);

            // gitignore
            $output->writeln('Adding gitignore...');
            $gitignore = ".idea/
composer.phar
vendor
";
            file_put_contents($newPath . '/.gitignore', $gitignore);

            $require = [];
            if (is_dir($newPath . '/src/View')) {
                $require['league/plates'] = '^3.3';
            }

            //  migrant
            if (is_dir($newPath . '/src/Entity')) {
                $migrant = "<?php

return [
    'packages' => [

    ],
];
";
                file_put_contents($newPath . '/.migrant', $migrant);
                $require['doctrine/orm'] = '^2.6.3';
            }

            // composer
            $output->writeln('Adding composer json...');


            $config = [
                'name' => $vendor . '/' . $package,
                'description' => $toReplace . ' package for Bone MVC Framework',
                'license' => 'MIT',
                'authors' => [
                    [
                        'name' => $author,
                        'email' => $email,
                    ]
                ],
                'require' => $require,
                'require-dev' => [
                    'roave/security-advisories' => 'dev-master',
                ],
                'autoload' => [
                    'psr-4' => [
                        $namespace . '\\\\' => 'src/'
                    ],
                ],
            ];

            $json = json_encode($config, JSON_PRETTY_PRINT);
            file_put_contents($newPath . '/composer.json', $json);

            $output->writeln('Packaging complete');
        }
    }
}