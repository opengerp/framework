<?php

namespace Opengerp\Translation;

class Tools
{


    public static function fetchTermFromTemplateCode($file_content)
    {
        $string = $file_content;

        $results = array();
        preg_match_all("#{\'(.*?)\'\|trans[\|}]#", $string, $results, PREG_OFFSET_CAPTURE);

        $results2 = array();
        preg_match_all("#{\"(.*?)\"\|trans[\|}]#", $string, $results2, PREG_OFFSET_CAPTURE);

        $results = array_merge($results, $results2);

        return $results;
    }
}