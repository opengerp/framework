<?php

use PHPUnit\Framework\TestCase;

final class getYmlTest extends TestCase
{
    public function testGetYml(): void
    {
        $vett = ['test'=>'test'];

        $str = \Opengerp\Translation\Tools::getYmlFromArray($vett);

        $this->assertSame("\n".'"test" : ', $str);
    }

    public function testFetchTermsFromCode()
    {
        $str = '<?php trans("some"); ';



        $vett_functions = [];
        $vett_functions[] = ['trans', 0];

        $results = \Opengerp\Translation\Tools::fetchTermsFromCode($str, $vett_functions);

        $this->assertEquals('some', $results[0]);
    }
}