<?php

declare(strict_types=1);
use NunoMaduro\PhpInsights\Domain\Insights\Composer\ComposerLockMustBeFresh;
use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenGlobals;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenSecurityIssues;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraitUsage;
use NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff;
use ObjectCalisthenics\Sniffs\CodeAnalysis\OneObjectOperatorPerLineSniff;
use ObjectCalisthenics\Sniffs\ControlStructures\NoElseSniff;
use ObjectCalisthenics\Sniffs\Files\FunctionLengthSniff;
use ObjectCalisthenics\Sniffs\Metrics\MaxNestingLevelSniff;
use ObjectCalisthenics\Sniffs\Metrics\MethodPerClassLimitSniff;
use ObjectCalisthenics\Sniffs\Metrics\PropertyPerClassLimitSniff;
use ObjectCalisthenics\Sniffs\NamingConventions\ElementNameMinimalLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\EmptyStatementSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\TodoSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics\CyclomaticComplexitySniff;
use SlevomatCodingStandard\Sniffs\Arrays\TrailingArrayCommaSniff;
use SlevomatCodingStandard\Sniffs\Classes\ClassConstantVisibilitySniff;
use SlevomatCodingStandard\Sniffs\Classes\DisallowLateStaticBindingForConstantsSniff;
use SlevomatCodingStandard\Sniffs\Classes\EmptyLinesAroundClassBracesSniff;
use SlevomatCodingStandard\Sniffs\Classes\ModernClassNameReferenceSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousAbstractClassNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\InlineDocCommentDeclarationSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowEmptySniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowShortTernaryOperatorSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\RequireNullCoalesceOperatorSniff;
use SlevomatCodingStandard\Sniffs\Exceptions\DeadCatchSniff;
use SlevomatCodingStandard\Sniffs\Functions\DisallowArrowFunctionSniff;
use SlevomatCodingStandard\Sniffs\Functions\DisallowEmptyFunctionSniff;
use SlevomatCodingStandard\Sniffs\Functions\StrictCallSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseDoesNotStartWithBackslashSniff;
use SlevomatCodingStandard\Sniffs\Operators\DisallowEqualOperatorsSniff;
use SlevomatCodingStandard\Sniffs\PHP\ShortListSniff;
use SlevomatCodingStandard\Sniffs\PHP\UselessSemicolonSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\UnionTypeHintFormatSniff;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;

return [
    'preset' => 'laravel',
    'ide' => 'vscode',
    'exclude' => [
        // Dependencies and build artifacts
        'vendor',
        'node_modules',
        'storage/app',
        'storage/framework',
        'storage/logs',
        'bootstrap/cache',
        'public/build',
        'public/hot',
        'public/storage',

        // Development and testing
        'tests/Browser/console',
        'tests/Browser/screenshots',
        'tests/coverage',
        'tests/reports',

        // Configuration and infrastructure
        'database/migrations',
        'database/seeders',
        'database/factories',
        'resources/views/vendor',
        'resources/lang/vendor',
        'routes/channels.php',
        'config/app.php',
        'config/auth.php',
        'config/broadcasting.php',
        'config/cache.php',
        'config/cors.php',
        'config/database.php',
        'config/filesystems.php',
        'config/hashing.php',
        'config/logging.php',
        'config/mail.php',
        'config/queue.php',
        'config/sanctum.php',
        'config/services.php',
        'config/session.php',
        'config/view.php',

        // Development tools and documentation
        'docs',
        'build',
        'reports',
        'scripts',
        'dev-docker',
        'docker',
        '.git',
        '.github',
        '.vscode',
        '.devcontainer',
        '.husky',
        '.lighthouseci',
        '.marscode',
        '.qodo',
        '.sfdx',
        '.zencoder',
        'tmp',
        'temp',
        'cache',
        '.phpunit.cache',
        '.php-cs-fixer.cache',
        'infection.log',
        'phpstan.neon.dist',
        'psalm.xml.dist',
    ],
    'add' => [
        // Security and quality insights
        ForbiddenSecurityIssues::class,
        ForbiddenGlobals::class,
        ForbiddenPrivateMethods::class,

        // Code quality insights
        DeclareStrictTypesSniff::class,
        UnionTypeHintFormatSniff::class,
        UnusedUsesSniff::class,
        UseDoesNotStartWithBackslashSniff::class,
        TrailingArrayCommaSniff::class,
        EmptyLinesAroundClassBracesSniff::class,
        RequireNullCoalesceOperatorSniff::class,
        StrictCallSniff::class,
        DisallowEqualOperatorsSniff::class,
        UselessSemicolonSniff::class,
        UnusedVariableSniff::class,
        DeadCatchSniff::class,
        ModernClassNameReferenceSniff::class,
        ShortListSniff::class,
        DuplicateSpacesSniff::class,
    ],
    'remove' => [
        // Remove overly opinionated insights that conflict with Laravel standards
        ForbiddenDefineFunctions::class,
        ForbiddenNormalClasses::class,
        ForbiddenTraitUsage::class,
        DisallowMixedTypeHintSniff::class,
        ElementNameMinimalLengthSniff::class,
        ForbiddenSetterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        DisallowShortTernaryOperatorSniff::class,
        DisallowEmptyFunctionSniff::class,
        TodoSniff::class,
        FunctionLengthSniff::class,
        PropertyPerClassLimitSniff::class,
        MethodPerClassLimitSniff::class,
        OneObjectOperatorPerLineSniff::class,
        UselessOverridingMethodSniff::class,
        UnusedParameterSniff::class,
        InlineDocCommentDeclarationSniff::class,
        SpaceAfterNotSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenPublicPropertySniff::class,
        EmptyStatementSniff::class,
        MaxNestingLevelSniff::class,
        LineLengthSniff::class,
        ClassConstantVisibilitySniff::class,
        CyclomaticComplexitySniff::class,
        ComposerLockMustBeFresh::class,

        // Remove insights that are too strict for Laravel development
        ParameterTypeHintSniff::class,
        PropertyTypeHintSniff::class,
        ReturnTypeHintSniff::class,
        DisallowLateStaticBindingForConstantsSniff::class,
        DisallowEmptySniff::class,
        DisallowArrowFunctionSniff::class,
        NoElseSniff::class,
    ],
    'config' => [
        // Line length configuration
        LineLengthSniff::class => [
            'lineLimit' => 120,
            'absoluteLineLimit' => 150,
            'ignoreComments' => false,
        ],

        // Cyclomatic complexity
        CyclomaticComplexityIsHigh::class => [
            'maxComplexity' => 10,
        ],
        CyclomaticComplexitySniff::class => [
            'complexity' => 10,
            'absoluteComplexity' => 15,
        ],

        // Function and method limits
        FunctionLengthSniff::class => [
            'maxLength' => 50,
        ],
        MethodPerClassLimitSniff::class => [
            'maxCount' => 20,
        ],
        PropertyPerClassLimitSniff::class => [
            'maxCount' => 15,
        ],

        // Nesting level
        MaxNestingLevelSniff::class => [
            'maxNestingLevel' => 4,
        ],

        // Class naming
        SuperfluousAbstractClassNamingSniff::class => [
            'superfluous' => false,
        ],
        SuperfluousInterfaceNamingSniff::class => [
            'superfluous' => false,
        ],
        SuperfluousExceptionNamingSniff::class => [
            'superfluous' => false,
        ],

        // Type hints
        ParameterTypeHintSniff::class => [
            'enableObjectTypeHint' => true,
            'enableMixedTypeHint' => true,
            'enableUnionTypeHint' => true,
            'enableIntersectionTypeHint' => true,
        ],
        PropertyTypeHintSniff::class => [
            'enableNativeTypeHint' => true,
            'enableMixedTypeHint' => true,
            'enableUnionTypeHint' => true,
            'enableIntersectionTypeHint' => true,
        ],
        ReturnTypeHintSniff::class => [
            'enableObjectTypeHint' => true,
            'enableStaticTypeHint' => true,
            'enableMixedTypeHint' => true,
            'enableUnionTypeHint' => true,
            'enableIntersectionTypeHint' => true,
            'enableNeverTypeHint' => true,
        ],

        // Unused variables and imports
        UnusedVariableSniff::class => [
            'ignoreUnusedValuesWhenOnlyKeysAreUsedInForeach' => true,
        ],
        UnusedUsesSniff::class => [
            'searchAnnotations' => true,
            'ignoredAnnotationNames' => ['@var', '@param', '@return', '@throws'],
        ],

        // Array formatting
        TrailingArrayCommaSniff::class => [
            'enableAfterHeredoc' => true,
        ],

        // Whitespace
        DuplicateSpacesSniff::class => [
            'ignoreSpacesInAnnotation' => true,
            'ignoreSpacesInComment' => true,
        ],

        // Documentation
        DocCommentSpacingSniff::class => [
            'linesCountBeforeFirstContent' => 0,
            'linesCountBetweenDescriptionAndAnnotations' => 1,
            'linesCountBetweenDifferentAnnotationsTypes' => 0,
            'linesCountBetweenAnnotationsGroups' => 1,
            'linesCountAfterLastContent' => 0,
            'annotationsGroups' => [
                '@internal', '@deprecated',
                '@link', '@see', '@uses',
                '@param', '@return', '@throws',
            ],
        ],

        // Security and best practices
        StrictCallSniff::class => [
            'strictCalls' => [
                'in_array',
                'array_search',
                'array_keys',
                'base64_decode',
            ],
        ],
    ],

    // Minimum quality requirements
    'requirements' => [
        'min-quality' => 90,
        'min-complexity' => 85,
        'min-architecture' => 85,
        'min-style' => 95,
        'disable-security-check' => false,
    ],

    // Threads for parallel processing
    'threads' => null, // Auto-detect

    // Summary options (commented out due to compatibility issues)
    // 'summary' => [
    //     'only' => [
    //         'code',
    //         'complexity',
    //         'architecture',
    //         'style',
    //     ],
    // ],
];
