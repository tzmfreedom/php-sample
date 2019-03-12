<?php

declare(strict_types=1);

function hoge(string $a, int $b)
{
  echo $a . PHP_EOL;
  echo $b . PHP_EOL;
}

hoge('a', 1);
hoge(null, 1); // error
hoge('a', null); // error
