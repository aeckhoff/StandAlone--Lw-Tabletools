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

putenv('NLS_SORT=GERMAN');
putenv('NLS_COMP=ANSI');

/**
 * The "DB_Oracle" class is the oracle DB-Abstraction Class of the Contentory-System.
 * 
 * @package  Framework
 * @author   Dr. Andreas Eckhoff
 * @version  3.0 (beta)
 * @since    PHP 5.0
 */
 class LwDbOracle extends lw_db
{
    public function lw_db_oracle($user, $pass, $host, $db)
    {
        $this->dbuser   = $user;
        $this->pass     = $pass;
	    $reg  			= \lw_registry::getInstance();
        $this->firephp	= $reg->getEntry("firephp");
		if ($this->firephp) $this->firephp->log(1, 'lw_db_oracle instanziert');
        if ($db)
		{
			$this->db = $db;
		}
		else
		{
	        $this->config       = $reg->getEntry("config");
			$this->db = trim($this->loadFile($this->config['custom']['sidfile']));
		}
        $this->host         = $host;
        $this->transaction  = 0;
        $this->dbg          = 0;
        $this->phptype      = "oracle";
        $this->dbdbg        = false; //getr und dbquery incl stmt nummer
    }
    
	public function quote($str)
	{
        $str = str_replace("\\'", "__::lw_quoted__", $str);
        $str = str_replace("'","__::lw_quoted__",$str);
        return str_replace("__::lw_quoted__","''",$str);
	}      

    public function specialquote($sql) {
        $sql = str_replace("''", "__::lw_quoted__", $sql);
        $sql = str_replace("\\'", "__::lw_quoted__", $sql);
        $sql = str_replace("__::lw_quoted__", "''", $sql);
        return $sql;
    }   
    
    public function close()
    {
        oci_close($this->connect);
    }
    
    public function connect($flag="")
    {
        if ($this->firephp) 
        {
            $timer = \lw_timer::getInstance('connect');
            $timer->start();
        }
    	
    	if ($this->host)
        {
            $this->connect = oci_connect($this->dbuser,$this->pass,'//'.$this->host.'/'.$this->db);
        }
        else
        {
            $this->connect = oci_connect($this->dbuser,$this->pass,$this->db);
        }
        
        if ($this->debug) echo "connection: ".$this->connect."<br>";
        
        if (!$this->connect || OCIError())
        {
			die("connection error");
        }
        
        if ($this->firephp) 
        {
          	$timer->stop();
          	$this->firephp->log('connect', 'lw_db_oracle connect aufgerufen ('.$timer->getAlloverTime().'sec)');
        }          
        return true;
    }
    
	public function explainTable($table)
	{
		$sql = "SELECT column_name as name, data_type as type, data_length as size, nullable FROM user_tab_columns where table_name = '".$table."'";
		return $this->select1($sql);
	}
	
    public function select($sql, $start=false, $amount=false)
    {
		if ($amount>0)
		{
		    $amount = $amount+1;
			$sql = "SELECT * FROM (SELECT a.*, rownum r FROM (".$sql.") a WHERE rownum < ".($start+$amount).") WHERE r > ".$start;
		}
    	$r = $this->getR($sql, 1);
        return $r['result'];
    }
    
    public function getR($sql, $array="") 
    {
        $sql = $this->specialquote($sql);
        if (empty($sql))
        {
            $this->error = "[db_Error] no sql passed";
            return false;   
        }
        if (!eregi("^select",$sql)) 
        {
            $this->error = "[db_Error] no select statement";
            return false;
        }
        else 
        {
            if ($this->firephp) 
            {
            	$this->counter['getR']++;
            	$timer = \lw_timer::getInstance('getR'.$this->counter['getR']);
            	$timer->start();
            }
        	if ($this->dbdbg) echo "\n\n<!-- ".$sql." :  ";
            $stmt   = oci_parse($this->connect,$sql);
            if ($this->dbdbg) echo $stmt." -->\n\n";
            $results= $this->dbexecute($stmt, 1);
            $count  = 0;
            $data   = array();
            $i=0;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS  )) 
            {
                $data[$i] = array_change_key_case($row,CASE_LOWER);
                $i++;
            }

            if ($array)
            {
                $res['result'] = $data;
            }
            else
            {
                $res['result'] = $data[0];
            }
            if ($this->firephp) 
            {
				$timer->stop();
            	$this->firephp->log($sql, $this->counter['getR'].'. lw_db_oracle getR aufgerufen ('.$timer->getAlloverTime().'sec)');	
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
        $table = strtoupper($table);
        $sql   = "SELECT table_name FROM USER_TABLES WHERE table_name = '".$table."'";
        $erg   = $this->select1($sql);
        if ($erg['table_name'] == $table && strlen($erg['table_name'])>0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }	
	
    public function fetch_array($lower)
    {
        $count  = 0;
        $data   = array();
        $i=0;
        while ($row = oci_fetch_array ($this->stmt, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS  )) 
        {
            if ($lower)
            {
                $data[$i] = array_change_key_case($row,CASE_LOWER);
            }
            else
            {
                $data[$i] = $row;
            }
            $i++;
        }
        return $data;
    }
    
    public function error()
    {
        return oci_error($this->stmt);
    }

    public function fetch_row()
    {
        return oci_fetch_row($this->stmt);    
    }
    
    public function fetch_object()
    {
        $count  = 0;
        $data   = array();
        $i=0;
        $object = & new oci_object();
        while (OCIFetchInto ($this->stmt, $row, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS  )) 
        {
            foreach($row as $key => $value)
            {
                if ($lower)
                {
                    $key = strtolower($key);
                    $data[$i]->$key = $value;
                }
                else
                {
                    $data[$i]->$key = $value;
                }
            }
            $i++;
        }
        return $data;        
    }

    public function dbquery($sql) 
    {
        //$sql = $this->specialquote($sql);
        if ($this->dbg) echo "query: ".$sql."<br>";
        if (empty($sql))
        {
            $this->error = "[db_Error] no sql passed";
            return false;   
        }
        else 
        {
            if ($this->firephp) 
            {
            	$this->counter['dbquery']++;
            	$timer = \lw_timer::getInstance('dbquery'.$this->counter['dbquery']);
            	$timer->start();
            }
        	if ($this->dbdbg) echo "\n\n<!-- ".$sql." :-: ";
            $stmt       = oci_parse($this->connect,$sql);
            if ($this->dbdbg) echo $stmt." -->\n\n";
            $results    = $this->dbexecute($stmt);
	        if ($this->firephp) 
	        {
	          	$timer->stop();
	          	$this->firephp->log($sql, $this->counter['dbquery'].'. lw_db_oracle dbquery aufgerufen ('.$timer->getAlloverTime().'sec)');
	        }             
        }
          
        if (!$results) 
        {
            $this->error = "[db_Error] no result";
            return false; 
        }
     
        return $results;
    }
    
    public function dbinsert($sql, $table="") 
    {
        //$sql = $this->specialquote($sql);
        if ($this->dbg) echo "insert: ".$sql."<br>";
        if (empty($sql))
        {
            $this->error = "[db_Error] no sql passed";
            return false;   
        }
        elseif (empty($table))
        {
            $this->error = "[db_Error] no table passed";
            return false;   
        }
        else 
        {
            if ($this->firephp) 
            {
            	$this->counter['dbinsert']++;
            	$timer = \lw_timer::getInstance('dbinsert'.$this->counter['dbinsert']);
            	$timer->start();
            }
        	$stmt       = oci_parse($this->connect,$sql." returning ROWID into :rid");
            if ($this->dbg) echo "stmt: ".$stmt."<br>";
            $rowid      = oci_new_descriptor($this->connect, OCI_D_ROWID);
            oci_bind_by_name($stmt, ":rid", $rowid, -1, OCI_B_ROWID);
            $results    = $this->dbexecute($stmt);
            $stmt2      = oci_parse($this->connect,"SELECT id FROM ".$table." WHERE ROWID = :rid");
            if ($this->dbg) echo "stmt2: ".$stmt2."<br>";
            oci_bind_by_name($stmt2,":rid",$rowid,-1,OCI_B_ROWID);
            $results2   = $this->dbexecute($stmt2);
            oci_fetch($stmt2);
            $data[0][strtolower(oci_field_name($stmt2,1))] = oci_result($stmt2,oci_field_name($stmt2,1));
            $erg        = $data[0][id];
            if ($this->firephp) 
	        {
	          	$timer->stop();
	          	$this->firephp->log($sql, $this->counter['dbinsert'].'. lw_db_oracle dbinsert aufgerufen ('.$timer->getAlloverTime().'sec)');
	        }
        }
        if (!$results) 
        {
            $this->error = "[db_Error] ".oci_error()."<br>";
            return false; 
        }
        return $erg;
    }
    
	public function createSequence($name)
	{	
		$sql = "INSERT INTO ".$this->sequence." (name, seqid) VALUES ('".$name."', 2)";
		$ok  = $this->dbquery($sql);
		return 1;
	}
    
    public function saveClob($table, $field, $data, $id) 
    {
        if ($this->firephp) 
        {
            $this->counter['saveClob']++;
            $timer = \lw_timer::getInstance('saveClob'.$this->counter['saveClob']);
            $timer->start();
        }
    	if (!$data) $data = " ";
        $clob = oci_new_descriptor($this->connect, OCI_D_LOB);
        if ($this->dbg) echo "save_clob: UPDATE $table SET $field = EMPTY_CLOB() WHERE id='$id' returning $field into :the_blob <br>";
        $stmt = oci_parse($this->connect,"UPDATE $table SET $field = EMPTY_CLOB() WHERE id='$id' returning $field into :the_blob");
        oci_bind_by_name($stmt, ':the_blob', $clob, -1, OCI_B_CLOB);
        if ($this->dbg) echo "stmt: ".$stmt."<br>";
        $this->dbexecute($stmt, 1);
        $data = str_replace("''", "'", $data);
        $ok = $clob->save($data);
        OCIFreeDesc($clob);
        oci_free_statement($stmt);
        oci_commit($this->connect);
        if ($this->firephp) 
        {
          	$timer->stop();
          	$this->firephp->log("UPDATE $table SET $field = EMPTY_CLOB() WHERE id='$id' returning $field into :the_blob", $this->counter['saveClob'].'. lw_db_oracle saveClob aufgerufen ('.$timer->getAlloverTime().'sec)');
        }       
        return $ok;
    }

    public function dbexecute($stmt, $flag="")
    {
        if (!$stmt || strlen(trim($stmt))<1)
        {
            $this->error = "[db_error] empty statement !";
            return false;
        }
        if ($this->dbdbg) echo "\n\n<!-- dbexecute: ".$stmt." -->\n\n";
        if ($this->transaction || $flag)
        {
            return oci_execute($stmt, OCI_DEFAULT);
        }
        else
        {
            return oci_execute($stmt);
        }
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
            $stmt   = oci_parse($this->connect,$sql);
	        if ($this->transaction || $flag)
	        {
	            return oci_execute($stmt, OCI_DEFAULT);
	        }
	        else
	        {
	            return oci_execute($stmt);
	        }
        }
    }

   /**
    * fetchRow-Funktion
    * �bernimmt die Daten aus einem Datensatz in ein numerisches Array
    * und gibt dieses zur�ck.
    *
    */
    public function fetchRow($flag=false)
    {
		return oci_fetch_array($this->stmt, (OCI_ASSOC+OCI_RETURN_LOBS));
    } 

    public function rollback()
    {
        oci_rollback($this->connect);
    }
    
    public function commit()
    {
        oci_commit($this->connect);
    }
	
	public function getTableStructure($table)
	{
		$sql = "SELECT * FROM user_tab_columns WHERE UPPER(table_name) = UPPER('".$table."')"; 
		return $this->select($sql);
	}	
	
	public function fieldExists($table, $name)
	{
		$sql = "SELECT table_name, column_name FROM user_tab_columns WHERE UPPER(table_name) = UPPER('".$table."') AND UPPER(column_name) = UPPER('".$name."')"; 
		$erg = $this->select1($sql);
		if ($erg["column_name"] == strtoupper($name) && strlen(trim($name))>0) return true;
		return false;
	}
	
	public function addField($table, $name, $type, $size=false, $null=false)
	{
		if (!$this->fieldExists($table, $name))
		{
			$sql = "ALTER TABLE ".$table." ADD (".strtoupper($name)." ".$this->setField($type, $size);
			if ($null)
			{
				$sql.= " NULL ";
			}
			else
			{
				$sql.= " NOT NULL ";
			}
			$sql.=")";
			//die($sql);
			return $this->dbquery($sql);
		}
		return false;
	}
	
	private function setField($type, $size)
	{
		switch ($type)
		{
			case "number":
				return " NUMBER(".$size.") ";
				break; 

			case "text":
				return " VARCHAR2(".$size.") ";
				break; 

			case "clob":
				return " CLOB ";
				break; 
				
			case "bool":
				return " NUMBER(1) ";
				break; 
				
			default:
				die("field not available");
		}
	}	
}

?>