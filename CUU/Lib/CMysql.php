<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CMysql extends CAbstractDb
{
	private $_link = NULL;
	private $_lastSql = NULL;
	
	private static $_links = array();//所有连接

	public static function createDb($conf)
	{
		
		$identify = md5($conf['host'].$conf['user'].$conf['password'].$conf['dbName']);
		if (empty(self::$_links[$identify]))
		{
			self::$_links[$identify] = new self($conf);
		}
		return self::$_links[$identify];
	}
	
    private function __construct($conf)
    {
    	$conf['port'] = !empty($conf['port'])?$conf['port']:3306;
    	$conf['charset'] = !empty($conf['charset'])?$conf['charset']:'utf8';
    	$conf['autoCreateDb'] = isset($conf['auto'])?$conf['auto']:FALSE;
    	
    	if (!($this->_link = mysql_connect($conf['host'].':'.$conf['port'], $conf['user'], $conf['password'])))
    	{
    		$msg = "Connect Server Failed: " . mysql_error($this->_link);
    	}
    	if (!empty($conf['dbName']))
    	{
    		if ($conf['autoCreateDb'])
    		{
    			$createDb = "CREATE DATABASE IF NOT EXISTS `{$conf['dbName']}` DEFAULT CHARACTER SET {$conf['charset']}";
    			$this->execute($createDb);
    		}
    		if (!@mysql_select_db($conf['dbName'], $this->_link))
    		{
    			$msg = "Select Error Failed:".mysql_error($this->_link);
    		}
    		else 
    		{
    			$this->execute("SET NAMES {$conf['charset']}");
    		}
    	}
    }
    
    public function find($sTable,$aCondition,$aOption=NULL)
    {
    	$sSql = "SELECT ".$this->_fields($aOption['select'])." FROM {$sTable}";
    	if (!!($where = $this->_where($aCondition))) 
    	{
    		$sSql .= ' WHERE '.$where;
    	}
    	$sSql .= ' LIMIT 1';
    	$result = $this->query($sSql);
    	$rs = array();
    	while (@$row = mysql_fetch_assoc($result))
    	{
    		$rs[] = $row;
    	}
    	
    	return !empty($rs) ? $rs[0] : array();
    }
    
    public function count($sTable,$aCondition)
    {
    	$sSql = "SELECT count(*) as cnt FROM {$sTable}";
    	if (!!($where = $this->_where($aCondition)))
    	{
    		$sSql .= ' WHERE '.$where;
    	}
    	$result = $this->query($sSql);
    	$rs = array();
    	while (@$row = mysql_fetch_assoc($result))
    	{
    		$rs[] = $row;
    	}
    	 
    	return $rs[0]['cnt'];
    }
    
    public function findAll($sTable,$aCondition,$aOption=NULL)
    {
    	$sSql = "SELECT ".$this->_fields($aOption['select'])." FROM {$sTable} ";
    	if (!!($where = $this->_where($aCondition)))
    	{
    		$sSql .= ' WHERE '.$where;
    	}
    	$sSql .= $this->_groupby($aOption['groupby']);
    	
    	$result = $this->query($sSql);
    	$rs = array();
    	while (@$row = mysql_fetch_assoc($result))
    	{
    		$rs[] = $row;
    	}
    	
    	return $rs;
    }
    
    public function insert($sTable,$aData)
    {
    	if (empty($aData))
    	{
    		return;
    	}
    	if (!isset($aData[0]))
    	{
    		$arr[0] = $aData;
    	} 
    	else 
    	{
    		$arr = $aData;
    	}
    
    	$fields = array_keys($arr[0]);
    	$values = array();
    	foreach ($arr as $d) 
    	{
    		$values[] = '("' . implode('", "', array_values($this->escapeString($d))) . '")';
    	}
    
    	$sql = 'INSERT INTO ' . $sTable . '';
    	$sql .= ' (`' . implode('`, `', $fields) . '`)';
    	$sql .= ' VALUES ' . implode(', ', $values);
    	
    	$this->execute($sql);
    	return mysql_insert_id($this->_link);
    }
    
    public function update($sTable,$aData,$aWhere)
    {
    	$sets = array();
    	foreach ($aData as $field => $value) 
    	{
    		// 特殊处理标识,如'count' => '{`count` + 1}'
    		if (preg_match('/^\{.*\}$/', $value)) 
    		{
    			$sets[] = '`' . $field . '` = ' . substr($this->escapeString($value), 1, -1);
    		} 
    		else 
    		{
    			$sets[] = '`' . $field . '` = "' . $this->escapeString($value) . '"';
    		}
    	}
    	$sWhere = ' WHERE 1=1 AND '.$this->_where($aWhere);
    	$sql = 'UPDATE ' . $sTable . ' SET ' . implode(', ', $sets) . $sWhere;
    	return $this->execute($sql);
    }
    
    public function delete($sTable, $aWhere)
    {
    	if (!!($where = $this->_where($aWhere)))
    	{
    		$sWhere .= ' WHERE '.$where;
    	}
    	
    	$sql = 'DELETE FROM ' . $sTable . $sWhere;
    	
    	return $this->execute($sql);
    }
    
    public function getLastSql()
    {
        return $this->_lastSql;
    }

    public function close()
    {
        mysql_close($this->_link);
    }

    protected function escapeString($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = mysql_real_escape_string($v);
            }
            return $data;
        } else {
            return mysql_real_escape_string($data);
        }
    }

    public function begin()
    {
    	$this->execute('BEGIN');
    }

    public function commit()
    {
    	$this->execute('COMMIT');
    }

    public function rollback()
    {
    	$this->execute('ROLLBACK');
    }
    
    public function replace($table,$data)
    {
    	if (empty($data))
    	{
    		return;
    	}
    	if (!isset($data[0]))
    	{
    		$arr[0] = $data;
    	}
    	else
    	{
    		$arr = $data;
    	}
    
    	$fields = array_keys($arr[0]);
    	$values = array();
    	foreach ($arr as $d)
    	{
    		$values[] = '("' . implode('", "', array_values($this->escapeString($d))) . '")';
    	}
    
    	$sql = 'REPLACE INTO ' . $table . '';
    	$sql .= ' (`' . implode('`, `', $fields) . '`)';
    	$sql .= ' VALUES ' . implode(', ', $values);
    	$this->execute($sql);
    	return mysql_insert_id($this->_link);
    }
    
    public function execute($sSql)
    {
    	$this->_lastSql = $sSql;
    	return mysql_query($sSql, $this->_link);
    }
    
    public function query($sSql)
    {
    	$this->_lastSql = $sSql;
    	return mysql_query($sSql, $this->_link);
    }
    
    public function create($dbName,$charset='utf8')
    {
    	$createDb = "CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET $charset";
    	return $this->execute($createDb);
    }
    
    public function createTable($tablename,$fields,$engine='InnoDB')
    {
    	if (!$tablename || !$fields) 
    	{
    		return FALSE;
    	}
    	if ($this->query("SELECT * FROM {$tablename} WHERE 0=1"))
    	{
    		return TRUE;
    	}
    	
    	$sql = "CREATE TABLE IF NOT EXISTS $tablename(";
    	foreach ($fields['columns'] as $fd =>$tp)
    	{
    		$sql .= "`$fd` $tp,";
    	}
    	$sql = trim($sql,',');
    	if (!empty($fields['pk'])) 
    	{
    		$sql .= ",PRIMARY KEY({$fields['pk']})";
    	}
    	$sql .= ')';
    	if (!empty($fields['extra'])) 
    	{
    		$sql .= $fields['extra'];
    	}
    	return $this->execute($sql);
    }
    /**
     * 1.id='1' and name='xxx'
     * 2.array('id'=>1,'name'=>'xxx');
     * 3.array('id'=>1,'name'=>'xxx','_logic_'=>'or');
     * 4.array(array(array('id'=>1,'name'=>'xxx','_logic_'=>'or'),'type'=>'2','_logic_'=>'and'),'game'=>'sss');
     * 5.array('name'=>array('like','xxx'))
     * 6.array('name'=>array(array('=','xxx'),array('like','zzz'),'_logic_'=>'and'))
     */
    private function _where($aCondition)
    {
    	if (empty($aCondition))
    	{
    		return '';
    	}
    	if (is_string($aCondition))//1
    	{
    		return $aCondition;
    	}
    	elseif(is_array($aCondition))
    	{
    		foreach ($aCondition as $key=>$value)
    		{
    			if (is_string($key) && $value && is_array($value))//5|6
    			{
    				if (!empty($value[0]) && is_array($value[0]))//6
    				{
    					foreach ($value as $k=>$val)
    					{
    						if ($k=='_logic_' && !is_array($val))
    						{
    							$piece[$k] = $val;
    						}
    						else
    						{
    							$piece[$k] = $this->_getOp($key,$val);
    						}
    					}
    				}
    				else//5
    				{
    					$piece[$key] = $this->_getOp($key,$value);
    				}
    			}
    			elseif ($value && is_array($value))//4
    			{
    				$piece[] = $this->_where($value);
    			}
    			else//2|3
    			{
    				$piece[$key] = (strtolower($key)!='_logic_') ?$this->_getOp($key,$value): $value;
    			}
    		}
    		if (!empty($piece['_logic_']))
    		{
    			$logic = ' '.strtoupper($piece['_logic_']).' ';
    			unset($piece['_logic_']);
    		}
    		else
    		{
    			$logic = " AND ";
    		}
    		return '('.implode($logic,$piece).')';
    	}
    }
    
    private function _getOp($key,$aValue)
    {
    	if ($key && $aValue && (is_string($aValue) || is_numeric($aValue))) 
    	{
    		return "`$key`"."='{$aValue}'";
    	}
    	elseif ($key && is_array($aValue))
    	{
    		return "`$key`".$aValue[0]."'{$aValue[1]}'";
    	}
    }
    
    private function _fields($aSelect=array())
    {
    	if ($aSelect && is_string($aSelect))
    	{
    		return $aSelect;
    	}
    	elseif ($aSelect && is_array($aSelect))
    	{
    		return implode(',', $aSelect);
    	}
    	return "*";
    }
    
    private function _groupby($aGroupby=array())
    {
    	if ($aGroupby && is_string($aGroupby))
    	{
    		return ' GROUP BY '.$aGroupby;
    	}
    	elseif ($aGroupby && is_array($aGroupby))
    	{
    		return ' GROUP BY '.implode(',', $aGroupby);
    	}
    	return "";
    }
}

?>