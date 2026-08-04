<?php

$finder = PhpCsFixer\Finder::create()
	->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/examples']);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setRules([
		'declare_strict_types' => true,
		'array_syntax' => ['syntax' => 'short'],
		'ordered_imports' => true,
		'no_unused_imports' => true,
		'phpdoc_align' => false,
		'concat_space' => ['spacing' => 'one'],
		'declare_equal_normalize' => true,
	])
	->setFinder($finder);
