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

        $arguments = [];

        // Usa una espressione regolare per trovare le occorrenze del filtro trans
        $pattern = "/\{\s*['\"](.*?)['\"]\s*\|\s*trans\s*\}/";

        // Esegui il pattern matching
        if (preg_match_all($pattern, $file_content, $matches)) {
            // Recupera gli argomenti trovati
            $arguments = $matches[1];
        }

        return $arguments;

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
            $index = $function[1]+1;

            $pattern = '/'.$function[0].'\s*\(\s*';
            for ($i = 1; $i <= $index; $i++) {
                $pattern .= '([\'"])(.*?)\1\s*,\s*';
            }
            $pattern = rtrim($pattern, ',\s*') . '\)/';


            if (preg_match_all($pattern, $string, $matches)) {
                // Recupera gli n-esimi argomenti trovati
                $arguments = $matches[$index * 2];
                foreach($arguments as $argument) {
                    $results[] = trim($argument);
                }
            }


        }

        return $results;


    }

    public static function getYmlFromArray($vett, $yaml)
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