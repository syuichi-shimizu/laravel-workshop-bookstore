<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    // チェックするディレクトリの指定
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        // お気に入りの設定があればお好きに設定
        // @see https://cs.symfony.com/doc/rules/index.html
        '@Symfony' => true, // Symfony が公式で提供する Coding Standards https://symfony.com/doc/current/contributing/code/standards.html
        '@PhpCsFixer' => true,
        '@PSR12' => true, // PSR2 を拡張したもの

        // declare(strict_types=1) を強制する
        'declare_strict_types' => true,

        // [@PhpCsFixer]
        // PHPUnit TestCase に @internal を付与しなくてもよい
        'php_unit_internal_class' => false,

        // [@PhpCsFixer]
        // @covers* アノテーションのないテストに @coversNothing を付与しない
        'php_unit_test_class_requires_covers' => false,

        // [@PhpCsFixer]
        // セミコロンを置く場所
        // メソッドチェインしたときは最後の呼び出し行に置き，改行しない
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    ])
    ->setFinder($finder);