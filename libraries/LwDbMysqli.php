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
class LwDbMysqli extends \lwTabletools\libraries\LwDb
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
        $this->dbuser       = &$user;
        $this->pass         = &$pass;
        $this->host         = &$host;
        $this->db           = &$db;
        $this->transaction  = false;
        $this->phptype      = "mysqli";
        $this->firephp = false;
    }
    
	public function quote($str) {
		if (get_magic_quotes_gpc())  $str = stripslashes($str);
		return$this->db->real_escape_string($str);
	}      
    
   /**
    * Connect-Funktion
    * hier wird die Verbindung zur Datenbank hergestellt
    * Der Connectionstring wird in der Klassenvariable $connect abgelegt.
    */
    public function connect()
    {
    	if ($this->firephp) 
        {
            $timer = \lw_timer::getInstance('connect');
            $timer->start();
        }
    	$this->db = @mysqli_connect($this->host, $this->dbuser, $this->pass, $this->db);
		if (mysqli_connect_errno()) 
		{
		    printf("Connect failed: %s\n", mysqli_connect_error());
		    exit();
		}
        if ($this->firephp) 
        {
          	$timer->stop();
          	$this->firephp->log('connect', 'lw_db_mysqli connect aufgerufen ('.$timer->getAlloverTime().'sec)');
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
        if (!$this->db) 
        {
            throw new \Exception("[db_mysql::getR] no db connection");
        }
        else 
        {
            if ($this->firephp) 
            {
            	$this->counter['getR']++;
            	$timer = \lw_timer::getInstance('getR'.$this->counter['getR']);
            	$timer->start();
            }
        	//echo "\n\n<!-- ".$sql." -->\n\n";
        	//echo $sql."<br>";
            $result = $this->db->query($sql);
            $count   = 0;
            $data    = array();
            // select result will be put associatively into the data array
            if (is_object($result))
            {
	            while ( $row = $result->fetch_array(MYSQLI_ASSOC)) 
	            {
	                $data[$count] = $row;
	                $count++;
	            }
	            $result->close();
            }

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
                if(isset($data[0])) {
                    $res['result'] = $data[0];
                } else {
                    $res = false;
                }
            }
            if ($this->firephp) 
            {
				$timer->stop();
            	$this->firephp->log($sql, $this->counter['getR'].'. lw_db_mysqli getR aufgerufen ('.$timer->getAlloverTime().'sec)');	
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
        $result = $this->dbquery($sql);
        while ( $row = $result->fetch_array(MYSQL_ASSOC)) 
        {
            if ($row['Msg_type'] == "error")
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
        if (!$this->db) 
        {
            throw new \Exception("[db_mysql] no db connection");
        }
        else 
        {
            //echo $sql."<br>";
            $this->result = $this->db->query($sql);
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
        while ( $row = $this->result->fetch_array(MYSQL_ASSOC)) 
        {
            $data[$count] = $row;
            $count++;
        }
        $this->result->close();
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
        	return $this->result->fetch_array(MYSQL_ASSOC);
        }
        else 
        {
    		return $this->result->fetch_row();
        }
    }

   /**
    * error-Funktion
    * gibt den letzten Fehlertext aus
    * 
    */
    public function error()
    {
        return $this->db->error();
    }

    public function logSQL($sql) {
		if (!strstr($sql, "lw_usage") && !strstr($sql, "lw_slog"))  {
	    	$reg = \lw_registry::getInstance();
			$this->config = $reg->getEntry("config");    	
			if ($this->config['logging']['dblogfile'] && is_writable($this->config['logging']['dblogfile'])) {
		    	$obj = debug_backtrace();
		        foreach($obj as $entry) {
		        	$output.= "(".$entry['class'].":".$entry['function'].":".$entry['line'].")>";
		        }
	        	$out   = date("YmdHis").":::".$sql.":::".$output."start"."\n";
				\lw_io::appendFile($this->config['logging']['dblogfile'].date("Ymd")."_ddl.log", $out);
	        	chmod($this->config['logging']['dblogfile'].date("Ymd")."_ddl.log", 0770);
	        }
		}
    }
    
   /**
    * dbquery-Funktion
    * diese Funktion führt SQL-Statements aus und gibt bei Erfolg ein true zurück.
    *
    * @param string sql   DML-SQL-Statement
    * 
    */
    public function dbquery($sql) {
    	if (empty($sql)) {
            throw new \Exception("[db_mysql::dbquery] no sql passed");
        }
        if (!$this->db) {
            throw new \Exception("[db_mysql::dbquery] no db connection");
        }
        else {
            if ($this->firephp) {
            	$this->counter['dbquery']++;
            	$timer = \lw_timer::getInstance('dbquery'.$this->counter['dbquery']);
            	$timer->start();
            }
        	$result = $this->db->query($sql);
			
        	if ($this->firephp) {
	          	$timer->stop();
	          	$this->firephp->log($sql, $this->counter['dbquery'].'. lw_db_mysqli dbquery aufgerufen ('.$timer->getAlloverTime().'sec)');
	        }        	
        }
        if (!$result) {
            throw new \Exception("[db_mysql::dbquery] ".$this->db->error);
        }
        return $result;
    }
    
   /**
    * dbinsert-Funktion
    * diese Funktion führt INSERT-Statements aus und gibt bei Erfolg die neue ID zurück.
    * ACHTUNG: auch wenn es für MySQL nicht benötigt wird, sollte dennoch immer der 
    * 2. Parameter ($table) auch übergeben werden. In Oracle ist es notwendig den
    * Tabellennamen zu übergeben und wenn die Anwendung mit beiden (MySQL und Oracle) 
    * laufen soll, dann muss der Funktionsaufruf identisch sein.
    *
    * @param string sql   DML-SQL-Statement
    * @param string table DML-SQL-Statement
    * 
    */
    public function dbinsert($sql, $table="") 
    {
        $this->dbquery($sql);
        return $this->db->insert_id;
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
        if ($this->firephp) 
        {
            $this->counter['saveClob']++;
            $timer = \lw_timer::getInstance('saveClob'.$this->counter['saveClob']);
            $timer->start();
        }
    	$ok = $this->dbquery($sql);
        if ($this->firephp) 
        {
          	$timer->stop();
          	$this->firephp->log($sql, $this->counter['saveClob'].'. lw_db_mysqli saveClob aufgerufen ('.$timer->getAlloverTime().'sec)');
        }
    	return $ok;
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


