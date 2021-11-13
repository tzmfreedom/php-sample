<?php

use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\Error\Listeners\DiagnosticErrorListener;
use Antlr\Antlr4\Runtime\InputStream;
use GoParser\Ast\File;
use GoParser\GoLexer;
use GoParser\GoParser;

require_once 'vendor/autoload.php';
require_once './src/GoLexer.php';
require_once './src/GoParser.php';

$input = InputStream::fromPath($argv[1]);
$lexer = new GoLexer($input);
$tokens = new CommonTokenStream($lexer);
$parser = new GoParser($tokens);
$parser->addErrorListener(new DiagnosticErrorListener());
$parser->setBuildParseTree(true);
$file = $parser->sourceFile();
//$exp = $file->functionDecl(0)->block()->statementList()->statement(0)->simpleStmt()->expressionStmt()->expression();

$converter = new \GoParser\GoConverter();
/** @var File $tree */
$tree = $converter->visit($file);

ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);
var_dump($tree);

$interpreter = new \GoParser\GoInterpreter();
$interpreter->visit($tree);
//foreach ($tree->functions['main']->statements as $statement) {
//    if ($statement instanceof \GoParser\Ast\Statement\Expression) {
//        if ($statement->expression instanceof \GoParser\Ast\MethodCall) {
//            if ($statement->expression->expr instanceof \GoParser\Ast\Identifier) {
//                $expr = $statement->expression->expr;
//                if ($expr->name === 'fmt' && $expr->child->name === 'Println') {
//                    echo $statement->expression->arguments[0]->value . PHP_EOL;
//                }
//            }
//        }
//    }
//}
//$tree = $file->packageClause()->IDENTIFIER()->getText();
//var_dump($tree);
//$tree = $file->functionDecl(0)->IDENTIFIER()->getText();
//var_dump($tree);
//$tree = $file->importDecl(0)->importSpec(0)->importPath()->getText();
//var_dump($tree);

//$visitor = new GoParserBaseVisitor();
//$visitor->visit($tree);
