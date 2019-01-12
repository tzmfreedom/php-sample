<?php

class Hoge {
  public function __construct() {
    $this->foo();
    $this->bar(1, [2], '3');
  }
  public function __call(string $method, array $arguments) {
    var_dump($method);
    var_dump($arguments);
  }
}

$h = new Hoge();
