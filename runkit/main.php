<?php

class Foo
{
  public function bar() {
    return 'hello';
  }
}

function hoge() {
  return 12345;
}

echo time() .PHP_EOL;
$a = time() + 100;
echo (new Foo())->bar() .PHP_EOL;
runkit7_method_redefine('Foo', 'bar', function(){ return 123123; });
echo (new Foo())->bar() .PHP_EOL;

runkit7_function_redefine('time', '', 'return hoge();');
// var_dump(Closure::fromCallable(function(){ return 'hello'; }));
// runkit7_function_redefine('time', Closure::fromCallable(function(){ return 1; }));
// runkit7_function_redefine('time', function(): int { return 1; } );

echo time() .PHP_EOL;
// echo hoge() .PHP_EOL;
