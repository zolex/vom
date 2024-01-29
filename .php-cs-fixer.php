<?php

require __DIR__.'/vendor/autoload.php';
$modixSet = new \Modix\PhpCsRulesets\RuleSet\Sets\ModixSet();

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

$config = new PhpCsFixer\Config();
$config->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
$config
    ->setRiskyAllowed(false)
    ->setRules($modixSet->getRules())
    ->setFinder($finder)
;

return $config;
