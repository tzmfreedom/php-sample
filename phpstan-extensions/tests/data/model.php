<?php

use function PHPStan\Testing\assertType;

function testFind()
{
    $cls = new stdClass();
    assertType('string', $cls->getString());
}
