<?php

use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    public function testSearch(): void
    {

        $dir = 'src/Opengerp/Files/';
        $vett = \Opengerp\Files\Search::readDirRecursiveAsArray($dir, 'php');

        $this->assertEquals('src/Opengerp/Files/Search.php', $vett[0]['nome']);
    }
}