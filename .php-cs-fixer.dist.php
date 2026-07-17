<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
;

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'declare_strict_types' => true,                   // ensure strict_types declaration
        'native_function_invocation' => [
            'include' => ['@all'],
        ],
        'native_constant_invocation' => true,             // use \PHP_EOL, \count, etc.
        'no_unused_imports' => true,                      // remove unused use statements
        'ordered_imports' => ['sort_algorithm' => 'alpha'], // alphabetical imports
        'single_quote' => true,                           // prefer single quotes
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'try', 'if', 'foreach'],
        ],
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => [
            'operators' => ['=>' => 'single_space', '=' => 'single_space'],
        ],
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => [
            'elements' => ['method' => 'one'],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use'],
        ],
        'no_whitespace_in_blank_line' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_trait_insert_per_statement' => true,
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],
    ])
    ->setFinder($finder)
;
