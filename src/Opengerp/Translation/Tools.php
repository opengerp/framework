<?php

namespace Opengerp\Translation;

class Tools
{


    /**
     * @param $file_content string
     * @return array
     */
    public static function fetchTermsFromTemplateCode($file_content)
    {
        $string = $file_content;

        $results = array();
        preg_match_all("#{\'(.*?)\'\|trans[\|}]#", $string, $results, PREG_OFFSET_CAPTURE);

        $results2 = array();
        preg_match_all("#{\"(.*?)\"\|trans[\|}]#", $string, $results2, PREG_OFFSET_CAPTURE);

        $final = [];
        foreach($results as $item) {
            $final[] = $item[1];
        }
        foreach($results2 as $item) {
            $final[] = $item[1];
        }

        return $final;
    }

    /**
     * @param $file_content
     * @param $vett_functions
     * @return array
     */
    public static function fetchTermsFromCode($file_content, $vett_functions)
    {
        $string = $file_content;

        $results = [];
        foreach ($vett_functions as $function) {
            $pattern = $function[0].'((.*?))(?=\))';
            $index = $function[1];

            preg_match_all("/$pattern/", $string, $addField);
            foreach ($addField[1] as $key => $item) {
                $arr_lems = explode(',', $item);
                if (isset($arr_lems[$index])) {
                    if (strpos($arr_lems[$index], 'trans(') == false) {
                        $results[] = self::trimAll($arr_lems[$index]);
                    }
                }
            }
        }

        return $results;


    }

    public static function getYmlFromArray($vett)
    {
        static $vett_duplicates = [];

        $str = '';

        foreach($vett as $k) {


            if (!in_array($k, $vett_duplicates)) {

                array_push($vett_duplicates, $k);

                $k = addcslashes($k, '"');

                if (strlen($k) > 0) {
                    $str .="\n";
                    if (isset($yaml[$k])) {
                        $str .= '"' . $k . '" : ' . addcslashes($yaml[$k], '"');
                    } else {
                        $str .= '"' . $k . '" : ';
                    }
                }


            }

        }

        return $str;

    }

    public static function trimAll($lemma)
    {
        $lemma = rtrim(trim($lemma),')');
        $lemma = ltrim(trim($lemma),'(');
        $lemma = trim(trim($lemma),'"');
        $lemma = trim(trim($lemma),"'");

        return $lemma;
    }
}