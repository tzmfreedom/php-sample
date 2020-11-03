<?php

require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\ParserFactory;
use PhpParser\NodeDumper;

function main(string $code)
{
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
        $ast = $parser->parse($code);
        foreach ($ast as $stmt) {
            process($stmt);
        }
        $dumper = new NodeDumper;
        echo $dumper->dump($ast) . "\n";
    } catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";
        return;
    }
}

function process($node)
{
    switch (get_class($node)) {
        case Stmt\Expression::class:
            return process($node->expr);
        case Expr\FuncCall::class:
            switch ($node->name->toString()) {
                case "var_dump":
                    $value = process($node->args[0]);
                    var_dump($value);
                    break;
                case "echo":
                    $value = process($node->args[0]);
                    echo $value;
                    break;
            }
            return;
        case \PhpParser\Node\Arg::class:
            return process($node->value);
        case \PhpParser\Node\Scalar\String_::class:
            return $node->value;
    }
}

$code = file_get_contents("php://stdin");
main($code);
