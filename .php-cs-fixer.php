<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
$config
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);

return $config;
