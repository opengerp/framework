<?php

use PHPUnit\Framework\TestCase;

final class GreeterTest extends TestCase
{
    public function testGetYml(): void
    {
        $vett = ['test'=>''];

        $str = \Opengerp\Translation\Tools::getYmlFromArray($vett);

        $this->assertSame("\n".'"test" : ', $str);
    }
}