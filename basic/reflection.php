<?php

class Hoge {
  public $foo;
  private $bar;

  public function __construct($foo, $bar) {
    $this->foo = $foo;
    $this->bar = $bar;
  }

  public function getBar() {
    return $this->bar;
  }
}

$h = new Hoge(1, 2);

echo $h->foo . PHP_EOL;

$closure = Closure::bind(
  function() {
    $this->bar = 123;
  },
  $h,
  $h
);
$closure();
echo $h->getBar() . PHP_EOL;

# echo $h->bar . PHP_EOL;
