<?php

namespace Gerp\System;

use PDO;

class SchemaUpdater
{


    private PDO $pdo;

    /**
     * @var bool
     */
    private $preview = true;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->setPreviewUpdateOn();
    }


    public function setPreviewUpdateOff()
    {

        $this->preview = false;

    }

    public function setPreviewUpdateOn()
    {

        $this->preview = true;

    }

    private function loadSchema($file_schema)
    {


        if (!file_exists($file_schema)) {

            $this->printLogLine("File schema db $file_schema non corretto");
            return false;

        }

        $this->printLogLine("\nControllo schema $file_schema");

        // controlla il database
        $obj_schema = simplexml_load_file($file_schema);


        return $obj_schema;

    }


    private function checkTableExist($table)
    {

        $check = $this->preview;

        $this->printLogLine($table['name']);

        // verifica esistenza
        $ris_table = $this->pdo->query("SHOW TABLES LIKE '$table[name]'");


        if (!$lin_table = ($ris_table->fetch(PDO::FETCH_ASSOC))) {


            $primary_key_field = null;


            // la deve creare
            $str_sql = "CREATE TABLE $table[name] ( ";
            foreach ($table->children() as $children) {
                if ($children->getName() == "field") {
                    $str_sql .= " $children[name] $children[type] ";
                    if ($children['null'] == "YES") {
                        $str_sql .= " NULL";
                    } else {
                        $str_sql .= " NOT NULL";
                    }

                    if (isset($children['default'])) {
                        //  $str_sql .= " DEFAULT '$children[default]' ";
                    }

                    if (isset($children['extra'])) {
                        $str_sql .= "  AUTO_INCREMENT ";
                        $primary_key_field = $children['name'];
                    }
                    $str_sql .= ", ";

                }
            }

            // toglie l'ultima virgola
            if ($primary_key_field) {

                $str_sql .= "PRIMARY KEY ($primary_key_field) ";
            } else {
                $str_sql = substr($str_sql, 0, -2);

            }
            $str_sql .= " ) ENGINE = INNODB ";

            if (!$check) {


                if ($this->pdo->query($str_sql)) {


                    $this->printLogLine("Table " . $table['name'] . " created");

                    return true;

                } else {

                    $this->printLogLine("Errore creating table " . $table['name']);
                    $this->printLogLine($str_sql);


                    return false;
                }

            } else {
                $this->printLogLine("Table " . $table['name'] . " does not exist");
                $this->printLogLine($str_sql);
                return false;


            }

        }

        return true;


    }

    private function loadTableFields($table_name)
    {
        $vett_campi_comp = array();

        $ris = $this->pdo->query("SHOW FIELDS FROM $table_name ");

        while ($lin = $ris->fetch(PDO::FETCH_ASSOC)) {
            $vett_campi_comp[$lin['Field']] = $lin;
        }

        return $vett_campi_comp;
    }

    private function loadTableIndexes($table_name)
    {


        $vett_index = array();

        $ris = $this->pdo->query("SHOW INDEXES FROM $table_name ");
        while ($lin = $ris->fetch(PDO::FETCH_ASSOC)) {

            $vett_index[$lin['Key_name']][$lin['Seq_in_index']] = $lin;


        }


        return $vett_index;
    }



    /**
     * @param $table \SimpleXMLElement
     * @return bool
     */
    private function checkTable($table)
    {
        if ( ! $this->checkTableExist($table) ) {
            return false;
        }

        $vett_columns = $this->loadTableFields($table['name']);

        if (count($vett_columns) == 0) {
            return false;
        }


        foreach ($table->children() as $children) {

            if ($children->getName() == "field") {
                $this->checkTableColumn($table, $vett_columns, $children);
            }

        }


        $vett_index = $this->loadTableIndexes($table['name']);


        foreach ($table->children() as $children) {

            if ($children->getName() == "index") {
                $this->checkTableIndex($table, $children, $vett_index);
            }

        }

    }



    public function checkDatabaseSchema($file_schema, $check = true)
    {
        if ( ! $obj_schema = $this->loadSchema($file_schema) ) {
            return false;
        }


        foreach ($obj_schema->children() as $table) {

            $this->checkTable($table);

        }

        return true;

    } // end verifica_schema

    private function addField($table, $children)
    {
        $this->printLogLine('Column ' . $table['name'] .'.'. $children['name'] . ' is missing');

        $str_sql = "ALTER TABLE $table[name] ADD $children[name] $children[type]  ";

        if ($children['null'] == "YES") {
            $str_sql .= " NULL ";
        } else {
            $str_sql .= " NOT NULL ";
        }



        if ($children['null'] == "YES") {

            $str_sql .= " DEFAULT NULL ";

        } elseif ($children['type'] != 'text')  {

            if (isset($children['default'])) {

                $str_sql .= " DEFAULT '$children[default]' ";
            }


        }




        if ($this->preview) {
            $this->printLogLine($str_sql);
        } else {
            $this->printLogLine($str_sql);

            if ($this->pdo->query($str_sql)) {
                $this->printLogLine('column created');
            }

        }

    }



    /**
     * @param $xml_field \SimpleXMLElement
     * @param $vett_field array
     */
    private function checkTableFieldType($xml_field, $vett_field)
    {


        if (substr($xml_field['type'], 0, 3) == 'int') {
            $xml_field['type'] = 'int';
            $vett_field['Type'] = substr($vett_field['Type'], 0, 3);
        }

        if (substr($xml_field['type'], 0, 8) == 'smallint') {
            $xml_field['type'] = 'smallint';
            $vett_field['Type'] = substr($vett_field['Type'], 0, 8);
        }


        if (substr($xml_field['type'], 0, 7) == 'tinyint') {
            $xml_field['type'] = 'tinyint';
            $vett_field['Type'] = substr($vett_field['Type'], 0, 7);
        }


        if (substr($xml_field['type'], 0, 6) == 'bigint') {
            $xml_field['type'] = 'bigint';
            $vett_field['Type'] = substr($vett_field['Type'], 0, 6);
        }



        if ($vett_field['Type'] != $xml_field['type']) {
            echo $vett_field['Type'];
            echo $xml_field['type'];

            return false;
        }

        if (strtoupper($vett_field['Null']) != strtoupper($xml_field['null'])) {
            return false;
        }


        $tabdefault = trim( (string) $vett_field['Default']);

        $xmldefault = trim( (string) $xml_field['default']);

        if (strtoupper($xmldefault) === 'NULL' && strtoupper($xml_field['null']) == 'YES') {
            $xmldefault = '';
        }


        if ( $tabdefault != $xmldefault && $vett_field['Type']!='text' ) {
            var_dump($tabdefault);
            var_dump($xmldefault);

            return false;
        }

        if (trim($vett_field['Extra']) != trim($xml_field['extra'] . "")) {
            return false;
        }

        return true;
    }



    /**
     * @param $table
     * @param $xml_field \SimpleXMLElement
     * @param $vett_columns array
     */
    private function checkTableColumn($table, $vett_columns, $xml_field)
    {
        $children = $xml_field;

        if ( ! isset($vett_columns[$children['name'] . ""]) ) {
            $this->addField($table, $children);
            return true;
        }

        $vett_column = $vett_columns[$children['name'] . ""];

        //print_r($vett_campi_comp[$children['name'].""]);

        // controlla il null e il default e l'autoincrement
        if ($this->checkTableFieldType($children, $vett_column)) {
            return true;
        }

        if ($children['null'] == 'NO') {
            $des_null = 'NOT NULL';
        } else {
            $des_null = '';
        }
        $des_default = '';


        if (empty($children['extra']) && $children['type']!='text') {
            if ($children['null'] == 'YES') {
                $des_default = " DEFAULT NULL ";
            } elseif(isset($children['default'])) {
                $des_default = " DEFAULT '$children[default]' ";
            }
        }

        $children['extra'] = $children['extra'] ?? '';

        $des_extra = strtoupper($children['extra']);


        if (!$this->preview) {
            if (strlen($des_extra) > 1) {
                $query = "ALTER TABLE  $table[name] DROP PRIMARY KEY";
                $this->pdo->query($query);
                $query = "ALTER TABLE  $table[name] ADD PRIMARY KEY ($children[name])";
                $this->pdo->query($query);
            }
        }
        //Fine modifica

        //echo "<br/>Campo $children[name] null non corretto. ".$vett_campi_comp[$children['name'].""]['Null']."<>$children[null]";

        //Modifica per non far convertire da varchar a int e da txt a varchar
        if (
            (stripos($vett_column['Type'], 'varchar') !== false
                && stripos($children['type'], 'int') !== false)
            &&
            (stripos($vett_column['Type'], 'text') !== false
                && stripos($children['type'], 'varchar') !== false)
        ) {

            $tipo = $vett_column['Type'];

            $this->printLogLine('Column '.$table['name'].'.'.$children['name'].' not updated to preserve data' );


        } else {

            $str_sql = " ALTER TABLE $table[name]  CHANGE $children[name] $children[name] $children[type] $des_null $des_default $des_extra ";



            $this->printLogLine($str_sql);

            if (!$this->preview) {
                $this->pdo->query($str_sql);
                $this->printLogLine('executed');

            }

        }


    }



    /**
     * @param $table  array
     * @param $xml_index \SimpleXMLElement
     * @param $vett_indexes array
     */
    private function checkTableIndex($table, $xml_index, $vett_indexes)
    {
        $vett_colums = array();
        $vett_update = array();

        $index_name = (string)$xml_index['name'];
        $index_type = (string)$xml_index['type'];

        foreach ($xml_index->children() as $columns) {

            $name = (string)$columns['name'];
            $seq = (string)$columns['seq'];
            $vett_colums[$index_name][] = $columns['name'];

            if (!isset($vett_indexes[$index_name][$seq])) {
                $vett_update[$index_name] = true;

            } else if ($vett_indexes[$index_name][$seq]['Index_type'] == 'FULLTEXT') {

                $vett_update[$index_name] = true;

            }

        }


        foreach ($vett_update as $index_name => $value) {

            $this->printLogLine($table['name'] . ' ' . $index_name . ':' . $value);

            $str_colums = implode(',', $vett_colums[$index_name]);

            if (!$this->preview) {

                $ris_check = $this->pdo->query("SHOW INDEX FROM $table[name] WHERE Key_name = '$index_name'");

                $lin_check = ($ris_check->fetch(PDO::FETCH_ASSOC));


                if ($index_name == "PRIMARY") {
                    if ($lin_check) {

                        $query = " ALTER TABLE $table[name] DROP PRIMARY KEY ";
                        $this->pdo->query($query);

                    }

                    $query = " ALTER TABLE $table[name] ADD PRIMARY KEY ($str_colums) ";
                    $this->pdo->query($query);

                } elseif ($index_type === "FULLTEXT") {

                    if ($lin_check) {

                        $query = " ALTER TABLE $table[name]  DROP INDEX $index_name ";
                        $this->pdo->query($query);

                    }

                    $query = " ALTER TABLE $table[name] ADD FULLTEXT INDEX $index_name( $str_colums ) ";
                    $this->pdo->query($query);


                } else {

                    if ($lin_check) {

                        $query = " ALTER TABLE $table[name]  DROP INDEX $index_name  ";

                        $this->pdo->query($query);

                    }

                    $query = " ALTER TABLE $table[name] ADD INDEX $index_name ( $str_colums ) ";
                    $this->pdo->query($query);

                }


            }

        }

    }



    public function dropColumn($table, $column_name)
    {
        if ( ! $column_name ) {
            return false;
        }

        $query = "ALTER TABLE $table[name] DROP COLUMN $column_name";
        return $this->pdo->query($query);
    }



    private function printLogLine($str)
    {
        echo ("\n$str");

    }



    public function export()
    {


        $db  = $this->pdo;


        $result_tbl = $db->query("SHOW TABLES ");


        $tables = array();
        while ($row = $result_tbl->fetch()) {
            $tables[] = $row[0];
        }


        $output = "<?xml version=\"1.0\" ?>\n";
        $output .= "<schema>";

// iterate over each table and return the fields for each table
        foreach ($tables as $table) {
            $output .= "\n\n\n<table name=\"$table\">";
            $result_fld = $db->query("SHOW FIELDS FROM `$table`");


            while ($row1 = $result_fld->fetch()) {
                $output .= "\n<field name=\"$row1[Field]\" type=\"$row1[Type]\"";
                $output .= " null=\"$row1[Null]\" default=\"$row1[Default]\" ";
                $output .= ($row1['Key'] == "PRI") ? " primary_key=\"yes\" />" : " />";
            }

            $vett_indici = array();

            $ris_index = $db->query("SHOW INDEXES FROM `$table` ");
            while ($lin_index = $ris_index->fetch()) {
                $vett_indici[$lin_index['Key_name']][] = $lin_index;

            }

            foreach ($vett_indici as $nome => $vett_campi) {
                $output .= "\n\n<index name=\"$nome\">";
                foreach ($vett_campi as $c) {
                    $output .= "\n<field name=\"$c[Column_name]\" seq=\"$c[Seq_in_index]\" />";
                }
                $output .= "</index>\n";
            }

            $output .= "\n</table>";

        }

        $output .= "</schema>";

// tell the browser what kind of file is come in
        header("Content-type: text/xml");
// print out XML that describes the schema
        echo $output;

    }





}

