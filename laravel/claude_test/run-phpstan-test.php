<?php

/**
 * PHPStan TypedBlade 型チェックのテスト実行スクリプト
 * 
 * このスクリプトはPHPStanの型チェック機能をテストするためのものです。
 * 意図的に型エラーを含むコードに対してPHPStanを実行し、
 * 適切にエラーが検出されることを確認します。
 */

echo "=== PHPStan TypedBlade Type Checking Test ===" . PHP_EOL . PHP_EOL;

// テスト対象ファイル
$testFiles = [
    'app/Http/Controllers/PHPStanTestController.php',
    'app/Http/Controllers/TypedBladeController.php',
];

echo "Testing files:" . PHP_EOL;
foreach ($testFiles as $file) {
    echo "  - {$file}" . PHP_EOL;
}
echo PHP_EOL;

// PHPStanの実行
$command = './vendor/bin/phpstan analyse ' . implode(' ', $testFiles) . ' --level=8 --no-progress';

echo "Running PHPStan..." . PHP_EOL;
echo "Command: {$command}" . PHP_EOL . PHP_EOL;

$output = [];
$returnCode = 0;
exec($command . ' 2>&1', $output, $returnCode);

// 結果の表示
echo "=== PHPStan Analysis Results ===" . PHP_EOL;
echo implode(PHP_EOL, $output) . PHP_EOL;

if ($returnCode === 0) {
    echo PHP_EOL . "✅ No errors found (this might indicate the rules are not working properly)" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ Errors found (this is expected for test files with intentional type mismatches)" . PHP_EOL;
}

echo PHP_EOL . "=== Expected Errors ===" . PHP_EOL;
$expectedErrors = [
    "user-profile template expects User but string provided",
    "user-profile template expects string but int provided", 
    "user-profile template expects Collection but string provided",
    "user-profile template expects bool but string provided",
    "Missing required variable",
    "Unused variable",
    "does not expect variable but it was provided",
];

foreach ($expectedErrors as $error) {
    echo "  - {$error}" . PHP_EOL;
}

echo PHP_EOL . "Test completed." . PHP_EOL;