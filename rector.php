<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\CodeQuality\Rector\Expression\TernaryFalseExpressionToIfRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector;
use Rector\CodingStyle\Rector\FuncCall\StrictInArrayRector;
use Rector\CodingStyle\Rector\If_\AlternativeIfToBracketRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php52\Rector\Property\VarToPublicPropertyRector;
use Rector\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php70\Rector\ClassMethod\Php4ConstructorRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php71\Rector\Assign\AssignArrayToStringRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Php80\Rector\ClassConstFetch\ClassOnThisVariableObjectRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector;
use Rector\Php85\Rector\FuncCall\ArrayKeyExistsNullToEmptyStringRector;
use Rector\Php85\Rector\FuncCall\OrdSingleByteRector;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPhpSets(php72: true)
    ->withPaths([
        __DIR__.'/functions',
        __DIR__.'/public',
    ])
    ->withRules([
        // DirNameFileConstantToDirConstantRector::class,
        // LongArrayToShortArrayRector::class,
        // VarToPublicPropertyRector::class,
        // Php4ConstructorRector::class,
        // RandomFunctionRector::class,
        // ThisCallOnStaticMethodToStaticCallRector::class,
        // IfIssetToCoalescingRector::class,
        // AssignArrayToStringRector::class,
        // StringifyStrNeedlesRector::class,
        // ClassOnThisVariableObjectRector::class,
        // ArrayKeyExistsNullToEmptyStringRector::class,
        // OrdSingleByteRector::class,
        // VariableInStringInterpolationFixerRector::class,
        // NewlineAfterStatementRector::class,
        // AbsolutizeRequireAndIncludePathRector::class,
        // TernaryFalseExpressionToIfRector::class,
        // AlternativeIfToBracketRector::class,
        // RemoveAlwaysElseRector::class,
        NullToStrictStringFuncCallArgRector::class,
    ])
    ->withPreparedSets(
        // deadCode: true,
        // typeDeclarationDocblocks: true,
        // earlyReturn: true,
        codeQuality: true,
        codingStyle: true,
    )
    // ->withTypeCoverageLevel(0)
    // ->withTypeCoverageDocblockLevel(0)
    ->withSkip([
        'functions/PEAR',
        'functions/adLDAP',
        'functions/php-excel-reader',
        RenameFunctionRector::class,
        UseIdenticalOverEqualWithSameTypeRector::class,
        SafeDeclareStrictTypesRector::class,
        StrictInArrayRector::class,
        StrictArraySearchRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
    ]);
