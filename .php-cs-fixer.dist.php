<?php

$finder = PhpCsFixer\Finder::create()->in(['src']);

return (new PhpCsFixer\Config())
    ->setRules(['@Symfony' => true])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
