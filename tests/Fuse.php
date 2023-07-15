<?php

use PHPUnit\Framework\TestCase;
use SDF\Fuse;

class FuseTest extends TestCase
{
  public function testRender()
  {
    $fuse = new Fuse();

    $data = [
      'name' => 'John Doe',
      'age' => 25,
    ];

    $result = $fuse->withObject($data)->render('test_view.php');

    $expected = '<h1>Hello, John Doe</h1><p>Age: 25</p>';
    $this->assertEquals($expected, $result);
  }
}
