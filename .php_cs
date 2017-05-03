<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude('Build')
    ->exclude('cache')
    ->exclude('var')
    ->exclude('vendor')
    ->in(__DIR__);
$config = PhpCsFixer\Config::create();
$config
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
return $config;
