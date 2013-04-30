<?php
namespace lwTabletools\libraries;
/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 1998-2009 Logic Works GmbH
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *  
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 * ************************************************************************* */

class LwDb  {

    private static $instance = null;

    public static function getInstance() {
        $reg = \lw_registry::getInstance();
        $config = $reg->getEntry("config");
        $key = base64_encode($config['lwdb']['user'] . $config['lwdb']['pass'] . $config['lwdb']['host'] . $config['lwdb']['name']);
        if (self::$instance[$key] == null) {
            $dbobj = "lw_db_" . $config['lwdb']['type'];
            self::$instance[$key] = new $dbobj($config['lwdb']['user'], $config['lwdb']['pass'], $config['lwdb']['host'], $config['lwdb']['name']);
            if (!self::$instance[$key]) {
                throw new \Exception("[lwdb:getInstance] DB-Type not supportet!");
            }
            self::$instance[$key]->connect();
        }
        return self::$instance[$key];
    }

    /**
     * getType-Funktion
     * gibt den DB-Type zur�ck
     * @return string
     */
    public function getDBType() {
        return $this->phptype;
    }

    public function getPrefix() {
        #$reg = lw_registry::getInstance();
        #$config = $reg->getEntry("config");
        #return $config['lwdb']['prefix'];
        return "";
    }

    public function gt($name) {
        return $this->getTable($name);
    }

    public function getTable($name) {
        return $this->getPrefix() . $name;
    }

    public function simpleQuery($sql) {
        return $this->dbquery($sql);
    }

    public function query($sql) {
        return $this->dbquery($sql);
    }

    /**
     * select1-Funktion
     * ist eine Wrapper-Funktion f�r die getR Funktion. 
     * Wird verwendet, wenn bei der Abfrage vermutlich 
     * nur ein Datensatz zur�ckgegeben wird.
     *
     * @param string sql   Select-Statement, welches vermutlich einen Datensatz zur�ckgibt
     * 
     */
    public function select1($sql) {
        $r = $this->getR($sql);
        return $r['result'];
    }

    /**
     * lwGetLimit-Funktion
     * hiermit wird eine Abfrage mit Limit durchgef�hrt
     *
     * @param string sql   
     * @param number start 
     * @param number	amount
     * 
     * @return array
     */
    public function dbGetLimitSelect($sql, $start, $amount) {
        return $this->select($sql, $start, $amount);
    }

    public function lwGetLimit($sql, $start, $amount) {
        return $this->select($sql, $start, $amount);
    }

    /**
     * save_clob-Funktion
     * Wrapper f�r saveClob
     *
     * @param string table Tabelle, die das CLOB Datenfeld enth�lt
     * @param string field Name des CLOB Datenfeldes
     * @param string data  Einzuf�gende Daten
     * @param string id    ID des Datensatzes
     * 
     */
    public function save_clob($table, $field, $data, $id) {
        return $this->saveClob($table, $field, $data, $id);
    }

    /**
     * beginTransaction
     * es wird eine Transaktion gestartet
     */
    public function beginTransaction() {
        $this->transaction = true;
    }

    /**
     * endTransaction
     * es wird eine Transaktion beendet
     */
    public function endTransaction() {
        $this->transaction = false;
    }

    public function lwUpdateEntry($table, $array, $id, $field=false, $options=false) {
        if (!$field) {
            $field = "id";
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($update) {
                    $update.= ", ";
                }
                $update.= $key . " = '" . $value . "'";
            }
            $sql = "UPDATE " . $table . " SET " . $update . " WHERE " . $field . " = '" . $id . "'";
            return $this->dbquery($sql);
        } else {
            return false;
        }
    }

    public function lwInsertEntry($table, $array, $noReturnID=false) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($fields) {
                    $fields.= ", ";
                    $values.= ", ";
                }
                $fields.= " " . $key . " ";
                $values.= " '" . $value . "' ";
            }
            $sql = "INSERT INTO " . $table . " (" . $fields . ") VALUES (" . $values . ")";
            if ($noReturnID == false) {
                return $this->dbinsert($sql, $table);
            } else {
                return $this->dbquery($sql);
            }
        } else {
            return false;
        }
    }

    public function lwDeleteEntry($table, $id, $where=false) {
        if (!$where) {
            $where = "id = '" . $id . "'";
        }
        return $this->dbquery("DELETE FROM " . $table . " WHERE " . $where);
    }

    public function buildSelectSQL($table, $options="") {
        if (is_array($options)) {
            if ($options['sql']) {
                $sql = $options['sql'];
            } else {
                if ($options['where']) {
                    $where = " WHERE " . $options['where'];
                }
                if ($options['order']) {
                    $order = " ORDER BY " . $options['order'];
                }
                if ($options['group']) {
                    $group = " GROUP BY " . $options['group'];
                }
                if ($options['field']) {
                    $field = $options['field'];
                } else {
                    $field = "*";
                }
                if ($options['table']) {
                    $table = $options['table'];
                }
                $sql = "SELECT " . $field . " FROM " . $table . " " . $where . " " . $group . " " . $order;
            }
        } else {
            $sql = "SELECT * FROM " . $table;
        }
        return $sql;
    }

    public function setStatement($sql) {
        $this->resetParameter();
        $this->sql = $sql;
    }

    public function bindParameter($name, $type, $value) {

        switch ($type) {
            // t = tablename
            case "t":
                $value = $this->gt($value);
                break;

            // i = integer
            case "i":
                if (!is_numeric($value))
                    $value = 0;
                break;

            // s = string
            case "s":
                $value = "'" . $this->quote($value) . "'";
                break;

            // f = fieldname
            case "f":
                $value = $this->quote($value);
                break;
        }
        $this->bindings[$name] = $value;
    }

    public function resetParameter() {
        unset($this->bindings);
        $this->bindings = array();
    }

    public function resetStatement() {
        unset($this->sql);
    }

    public function prepare() {
        $sql = $this->sql;
        $ok = preg_match_all("^t:([a-zA-Z0-9_]*?)[\s]^sm", $sql, $erg);
        $i = 0;
        foreach ($erg[0] as $entry) {
            $sql = str_replace($entry, " " . $this->gt($erg[1][$i]) . " ", $sql);
            $i++;
        }

        $ok = preg_match_all("^t:([a-zA-Z0-9_]*?)[\.]^sm", $sql, $erg);
        $i = 0;
        foreach ($erg[0] as $entry) {
            $sql = str_replace($entry, " " . $this->gt($erg[1][$i]) . ".", $sql);
            $i++;
        }

        $ok = preg_match_all("^t:([a-zA-Z0-9_]*?)[\,]^sm", $sql, $erg);
        $i = 0;
        foreach ($erg[0] as $entry) {
            $sql = str_replace($entry, " " . $this->gt($erg[1][$i]) . ",", $sql);
            $i++;
        }
        foreach ($this->bindings as $key => $value) {
            $sql = str_replace(":" . $key, $value, $sql);
        }

        $sql = str_replace(":instance", "'" . $this->config['general']['instance'] . "'", $sql);
        $sql = str_replace(":lw_date", "'" . date("YmdHis") . "'", $sql);
        return $sql;
    }

    public function pdbquery() {
        $sql = $this->prepare();
        return $this->dbquery($sql);
    }

    public function pselect1() {
        $sql = $this->prepare();
        return $this->select1($sql);
    }

    public function pselect($start=false, $amount=false) {
        $sql = $this->prepare();
        return $this->select($sql, $start, $amount);
    }

    public function pdbinsert($table) {
        $sql = $this->prepare();
        return $this->dbinsert($sql, $table);
    }

}