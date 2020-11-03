<?php

require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter;

$code = <<<'CODE'
<?php

function test($foo)
{
    var_dump($foo);
    return h('h');
}
CODE;

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
try {
    $ast = $parser->parse($code);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

$dumper = new NodeDumper;
echo $dumper->dump($ast) . "\n";

class Converter extends PrettyPrinter\Standard {
    protected function pExpr_Variable(PhpParser\Node\Expr\Variable $node) {
        if ($node->name instanceof Expr) {
            return '(' . $this->p($node->name) . ')';
        } else {
            return '' . $node->name;
        }
    }

    protected function pExpr_FuncCall(PhpParser\Node\Expr\FuncCall $node) {
        $fName = $this->pCallLhs($node->name);
        if ($fName === 'h') {
            $fName = 'React\component';
        }
        return $fName . '(' . $this->pMaybeMultiline($node->args) . ')';
    }

    private function hasNodeWithComments(array $nodes) {
        foreach ($nodes as $node) {
            if ($node && $node->getComments()) {
                return true;
            }
        }
        return false;
    }

    private function pMaybeMultiline(array $nodes, $trailingComma = false) {
        if (!$this->hasNodeWithComments($nodes)) {
            return $this->pCommaSeparated($nodes);
        } else {
            return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
        }
    }
}

//$prettyPrinter = new PrettyPrinter\Standard;
$prettyPrinter = new Converter();
echo $prettyPrinter->prettyPrintFile($ast);

