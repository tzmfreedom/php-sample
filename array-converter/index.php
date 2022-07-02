<?php

$json = file_get_contents('php://stdin');
$jsonArray = json_decode($json, true);

echo prettyPrint($jsonArray);

function prettyPrint($target, $indent = 0) {
  $str = '';
  if (is_array($target)) {
    if (array_is_list($target)) {
      $str .= '[' . PHP_EOL;
      foreach ($target as $key => $value) {
        $str .= str_repeat(' ', $indent + 4) . prettyPrint($value, $indent + 4) . ',' . PHP_EOL;
      }
      $str .= str_repeat(' ', $indent) . ']';
    } else {
      $str .= '[' . PHP_EOL;
      foreach ($target as $key => $value) {
        $str .= str_repeat(' ', $indent + 4) . prettyPrint($key) . ' => ' . prettyPrint($value, $indent + 4) . ',' . PHP_EOL;
      }
      $str .= str_repeat(' ', $indent) . ']';
    }
  } else {
    if (is_bool($target)) {
      $str .= $target ? 'true' : 'false';
    } elseif (is_string($target)) {
      $str .= "'$target'";
    } else {
      $str .= $target;
    }
  }
  return $str;
}
