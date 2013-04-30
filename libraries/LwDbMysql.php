<?php
namespace lwTabletools\libraries;
/**************************************************************************
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
***************************************************************************/

/**
 * The "DB_MYSQL" class is the mysql DB-Abstraction Class of the Contentory-System.
 * 
 * @package  Framework
 * @author   Andreas Eckhoff
 * @version  3.0 (beta)
 * @since    PHP 5.0
 */
class LwDbMysql extends lw_db
{
    
    /**
     * beinhaltet den Usernamen der aktuellen Datenbankverbindung
     * @var string
     */
    protected $dbuser;

    /**
     * beinhaltet das Passwort in Klartext (!!) der aktuellen Datenbankverbindung
     * @var string
     */
    protected $pass;

    /**
     * beinhaltet den Hostnamen der aktuellen Datenbankverbindung
     * @var string
     */
    protected $host;

    /**
     * beinhaltet den Datenbanknamen der aktuellen Datenbankverbindung
     * @var string
     */
    protected $db;

    /**
     * wird auf 1 gesetzt, wenn transactionen genutzt werden sollen. 
     * Ist derzeit in der mysql Klasse noch nicht implementiert.
     * @var string
     */
    protected $transaction;

    /**
     * beinhaltet den Datenbanktyp "oracle", "mysql" oder "sqlite". 
     * In diesem Fall "mysql".
     * @var string
     */
    protected $phptype;
    
    /**
     * beinhaltet den Connectionstring der aufgebauten DB-Verbindung
     * @var string
     */
    protected $connect;

    /**
     * wenn $error gesetzt ist, dann ist bei den Aktionen ein Fehler aufgetreten. 
     * In der Regel sollte auch die Fehlerbeschreibung enthalten sein.
     * @var string
     */
    public $error;

    /**
     * enth�lt die Konfigurationsparameter
     * 
     * @var string
     */
    protected $config;
    
    /**
     * schlatet Audit-Funkiton ein oder aus
     * 
     * @var bool
     */
    protected $audit;

   /**
    * Constructor
    * hier werden die Grundvariablen gesetzt und der Klasse zur Verf�gung gestellt.
    *
    * @param    string db username
    * @param    string db password
    * @param    string db hostname
    * @param    string db name
    */
    function __construct($user="", $pass="", $host="", $db="", $seq="")
    {
        parent::__construct();
    	if ($user)
        {
            $this->dbuser       = &$user;
            $this->pass         = &$pass;
            $this->host         = &$host;
            $this->db           = &$db;
        }
        else
        {
	        $reg = \registry::getInstance();
	        $this->config       = $reg->getEntry("config");
        	$this->dbuser       = $this->config['lwdb']['user'];
            $this->pass         = $this->config['lwdb']['pass'];
            $this->host         = $this->config['lwdb']['host'];
            $this->db           = $this->config['lwdb']['name'];
        }
        $this->transaction  = false;
        $this->phptype      = "mysql";
    }
    
	public function quote($str)
	{
		if (get_magic_quotes_gpc()) $str = stripslashes($str); 
		return mysql_real_escape_string($str);
	}      
    
   /**
    * Connect-Funktion
    * hier wird die Verbindung zur Datenbank hergestellt
    * Der Connectionstring wird in der Klassenvariable $connect abgelegt.
    */
    public function connect()
    {
        // Die Klasse meldet sich bei dem Datenbankserver an.
        $this->connect = @mysql_connect($this->host, $this->dbuser,$this->pass);
        // wenn dabei ein Fehler auftritt, wird die $error-Variable gesetzt und
        // es wird false zur�ckgegeben
        if (!$this->connect)
        {
            throw new \Exception("[db_mysql] no db connection");
        }
        else
        {
            // bei erfolgreicher Anmeldung verbindet sich die Klasse mit der gew�nschten Datenbank.
            $select = @mysql_select_db($this->db,$this->connect);
            // wenn dabei ein fehler auftritt, wird die $error-Variable gesetzt und
            // es wird false zur�ckgegeben
            if (!$select) 
            {
                throw new \Exception("[db_mysql] database (".$this->db.") not available");
            }
        }
        return true;
    }
 
    /**
    * select-Funktion
    * ist eine Wrapper-Funktion f�r die getR Funktion. 
    * Wird verwendet, wenn bei der Abfrage vermutlich 
    * mehrere Datens�tze zur�ckgegeben werden.
    *
    * @param string sql   Select-Statement, welches vermutlich mehrere Datens�tze zur�ckgibt
    * 
    */
    public function select($sql, $start=false, $amount=false)
    {
		if ($amount>0)
		{
	    	$sql = $sql." LIMIT ".$start.", ".$amount;
		}
    	$r = $this->getR($sql, 1);
        return $r['result'];
    }
    
   /**
    * getR-Funktion (getR steht f�r getResult)
    * diese Funktion f�hrt eine datenbankabfrage udrch und gibt das Ergebnis in einem Array zur�ck.
    * Das R�ckgabearray kann �ber den Schalter ($array) so gesteuert werden, dass nur ein Datensatz 
    * zur�ckgegeben wird oder alle. Im ersten Fall ist der erste Schl�ssel direkt die Datenfeldbezeichnung
    * Im anderen Fall wird als erster Schl�ssel der Datensatziterator und als zweiter Schl�ssel die
    * Datenfeldbezeichnung verwendet.
    *
    * @param string sql    Select-Statement, welches vermutlich einen Datensatz zur�ckgibt
    * @param bool   array  bei true werden mehrere Datens�tze zur�ckgegeben, bei false nur der erste.
    * @param bool   lower  bei true werden alle Datenfeldbezeichnungen auf lower case gesetzt
    * 
    */
    public function getR($sql, $array="", $lower="") 
    {
        // check if $sql is empty
        if (empty($sql))
        {
            throw new \Exception("[db_mysql::getR] no sql passed");
        }
        // check if $sql is a select statement
        if (!eregi("^select",$sql) && !eregi("^show",$sql))
        {
            throw new \Exception("[db_mysql::getR] no select statement");
        }
        // check if connection is available
        if (!$this->connect) 
        {
            throw new \Exception("[db_mysql::getR] no db connection");
        }
        else 
        {
            //echo "\n\n<!-- ".$sql." -->\n\n";
        	//echo $sql."<br>";
            $results = mysql_query($sql, $this->connect);
            $count   = 0;
            $data    = array();
            // select result will be put associatively into the data array
            while ( $row = mysql_fetch_array($results, MYSQL_ASSOC)) 
            {
                $data[$count] = $row;
                $count++;
            }
            mysql_free_result($results);

            // if chosen, all associative names will be transformed to lower characters
            if ($lower)
            {
                for ($i=0; $i<count($data);$i++)
                {
                    foreach ($data[$i] as $key => $value) 
                    {
                        $newdata[$i][strtolower($key)] = $value;
                    }
                }
                $data = $newdata;
            }
            
            // if chosen, the data array(array) will be returned or the single array
            if ($array)
            {
                $res['result'] = $data;
            }
            else
            {
                $res['result'] = $data[0];
            }
            return $res;
        }
    }

   /**
    * tableExists-Funktion
    * �berpr�ft, ob es eine Tabelle mit dem angegebene Namen gibt.
    *
    * @param  string 
    * @return bool
    */    
    public function tableExists($table)
    {
        $sql     = "check table ".$table;
        $results = $this->dbquery($sql);
        
        while ( $row = mysql_fetch_array($results, MYSQL_ASSOC)) 
        {
            if (strtolower($row['Msg_type']) == "error")
            {
                return false;
            }
        }
        return true;
    }

   /**
    * execute-Funktion
    * f�hrt ein sql-Statement aus und �bergibt das Ergebnis
    * an die Klassenvariable $result.
    *
    * @param string sql   DML-SQL-Statement
    * 
    */
    public function execute($sql)
    {
        // check if $sql is empty
        if (empty($sql))
        {
        	throw new \Exception("[db_mysql] no sql passed");
        }
        // check if connection is available
        if (!$this->connect) 
        {
            throw new \Exception("[db_mysql] no db connection");
        }
        else 
        {
            //echo $sql."<br>";
            $this->result = mysql_query($sql, $this->connect);
        }
    }
    
   /**
    * fetchArray-Funktion
    * �bernimmt die Daten aus der Klassenvariable $result und
    * gibt diese in in einem assoziativesd Array zur�ck.
    *
    * @param bool lower   Assoziative Namen werden in Kleinbuchstaben umgewandelt
    * 
    */
    public function fetchArray($lower="")
    {
        $count  = 0;
        $data   = array();
        // select result will be put associatively into the data array
        while ( $row = mysql_fetch_array($this->result, MYSQL_ASSOC)) 
        {
            $data[$count] = $row;
            $count++;
        }
        mysql_free_result($this->result);
        // if chosen, all associative names will be transformed to lower characters
        if ($lower)
        {
            for ($i=0; $i<count($data);$i++)
            {
                foreach ($data[$i] as $key => $value) 
                {
                    $newdata[$i][strtolower($key)] = $value;
                }
            }
            $data = $newdata;
        }
        return $data;
    }
    
   /**
    * fetchRow-Funktion
    * �bernimmt die Daten aus einem Datensatz in ein numerisches Array
    * und gibt dieses zur�ck.
    *
    */
    public function fetchRow($flag=false)
    {
        if ($flag)
        {
        	return mysql_fetch_array($this->result, MYSQL_ASSOC);
        }
        else 
        {
    		return mysql_fetch_row($this->result);
        }
    }

   /**
    * error-Funktion
    * gibt den letzten Fehlertext aus
    * 
    */
    public function error()
    {
        return mysql_error();
    }

   /**
    * dbquery-Funktion
    * diese Funktion f�hrt SQL-Statements aus und gibt bei Erfolg ein true zur�ck.
    *
    * @param string sql   DML-SQL-Statement
    * 
    */
    public function dbquery($sql) 
    {
    	//echo "<!-- ".$sql." -->\n\n";
    	if (empty($sql))
        {
            throw new \Exception("[db_mysql::dbquery] no sql passed");
        }
        if (!$this->connect) 
        {
            throw new \Exception("[db_mysql::dbquery] no db connection");
        }
        else 
        {
            $results = @mysql_query($sql, $this->connect);
        }
        if (!$results) 
        {
            throw new \Exception("[db_mysql::dbquery] ".mysql_errno()." - ".mysql_error());
        }
        return $results;
    }
    
   /**
    * dbinsert-Funktion
    * diese Funktion f�hrt INSERT-Statements aus und gibt bei Erfolg die neue ID zur�ck.
    * ACHTUNG: auch wenn es f�r MySQL nicht ben�tigt wird, sollte dennoch immer der 
    * 2. Parameter ($table) auch �bergeben werden. In Oracle ist es notwendig den
    * Tabellennamen zu �bergeben und wenn die Anwendung mit beiden (MySQL und Oracle) 
    * laufen soll, dann muss der Funktionsaufruf identisch sein.
    *
    * @param string sql   DML-SQL-Statement
    * @param string table DML-SQL-Statement
    * 
    */
    public function dbinsert($sql, $table="") 
    {
        $this->dbquery($sql);
        return mysql_insert_id();
    }
    
   /**
    * saveClob-Funktion                
    * diese Funktion f�hrt ein UPDATE-Statements mit einem CLOB Inhalt aus. Dies ist in MySQL 
    * eigtnlich nicht notwendig, damit das aber auch in Oracle funktioniert und man diese Klasse
    * m�glichst abstrakt verwendet, sollte man CLOB iNhalt in MySQL auch mit dieser Funktion updaten.
    *
    * @param string table Tabelle, die das CLOB Datenfeld enth�lt
    * @param string field Name des CLOB Datenfeldes
    * @param string data  Einzuf�gende Daten
    * @param string id    ID des Datensatzes
    * 
    */
	public function saveClob($table, $field, $data, $id) 
    {
		$sql = "UPDATE ".$table." SET ".$field." = '".$data."' WHERE id = ".$id;
		return $this->dbquery($sql);
    }

   /**
    * commit-Funktion
    * wird in mysql noch nicht verwendet. Muss aber existieren, da aus Gr�nden der Abstraktion
    * manche Anwednungen diese Funktion aufrufen.
    *
    */
    public function commit()
    {
        return true;
    }
    
   /**
    * rollback-Funktion
    * wird in mysql noch nicht verwendet. Muss aber existieren, da aus Gr�nden der Abstraktion
    * manche Anwednungen diese Funktion aufrufen.
    *
    */
    public function rollback()
    {
        return true;
    }
	
	public function getTableStructure($table)
	{
		$sql = "SHOW FULL FIELDS FROM ".$table;
		return $this->select($sql);
	}
		
	public function fieldExists($table, $name)
	{
		$erg = $this->getTableStructure($table);
		foreach($erg as $field)
		{
			if ($field["Field"] == $name)
			{
				return true;
			}
		}
		return false;
	}
	
	public function addField($table, $name, $type, $size=false, $null=false)
	{
		if (!$this->fieldExists($table, $name))
		{
			
			$sql = "ALTER TABLE ".$table." ADD COLUMN ".$name." ".$this->setField($type, $size);
			if ($null)
			{
				$sql.= " NULL ";
			}
			else
			{
				$sql.= " NOT NULL ";
			}
			return $this->dbquery($sql);
		}
		return false;
	}
	
	private function setField($type, $size)
	{
		switch ($type)
		{
			case "number":
				if ($size>11)
				{
					return " bigint(".$size.") ";
				}
				else
				{
					return " int(".$size.") ";
				}
				break; 

			case "text":
				if ($size>255)
				{
					return " text ";
				}
				else
				{
					return " varchar(".$size.") ";
				}
				break; 

			case "clob":
				return " text ";
				break; 
				
			case "bool":
				return " int(1) ";
				break; 
				
			default:
				die("field not available");
		}
	}	
	
}	

?>