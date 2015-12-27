<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CMysqli extends CAbstractDb
{
	private static $_instance = array();
	
    private $_link = NULL;
    private $_lastSql = NULL;

    private function __construct($sHost,$sUser, $sPwd, $sDbname)
    {
    	if (!($this->_link = new mysqli($sHost,$sUser,$sPwd,$sDbname)))
    	{
    		$msg = "Connect Server Failed: " . mysql_error();
    		die($msg);
    	}
    	
    	if (mysql_select_db($sDbname, $this->_link))
    	{
    		$msg = mysql_error($this->_link);
    		die($msg);
    	}
    	
    	//@mysqli_query($this->_link,'SET NAMES UTF8');
    }
    
    public static function createDb($sHost,$sUser, $sPwd, $sDbname)
    {
    	$identify = md5($sHost.$sUser.$sPwd.$sDbname);
    	if (empty(self::$_instance[$identify])) 
    	{
    		self::$_instance[$identify] = new self($sHost,$sUser, $sPwd, $sDbname);
    	}
    	return self::$_instance[$identify];
    }

    public function selectAll($sql, $return_column = false)
    {
        $result = mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
        $rs = array();
        if (!$return_column) 
        {
            while (@$row = mysql_fetch_assoc($result)) 
            {
                $rs[] = $row;
            }
        } 
        else 
        {
            while (@$row = mysql_fetch_assoc($result)) 
            {
                $rs[] = $row[0];
            }
        }

        $this->_lastSql = $sql;
        return $rs;
    }

    public function selectOne($sql)
    {
        $sql = stristr($sql, "LIMIT") ? $sql : $sql . " LIMIT 1";
        $rs = $this->selectAll($sql);
        $this->_lastSql = $sql;
        return !empty($rs) ? $rs[0] : array();
    }

    public function insert($table, array $data)
    {
        if (empty($data)) return;
        if (!isset($data[0])) {
            $arr[0] = $data;
        } else {
            $arr = $data;
        }

        $fields = array_keys($arr[0]);
        $values = array();
        foreach ($arr as $d) {
            $values[] = '("' . implode('", "', array_values($this->escapeString($d))) . '")';
        }

        $sql = 'INSERT INTO `' . $table . '`';
        $sql .= ' (`' . implode('`, `', $fields) . '`)';
        $sql .= ' VALUES ' . implode(', ', $values);

        mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
        $this->_lastSql = $sql;
        return mysql_insert_id($this->_link);
    }

    public function replace($table, array $data)
    {
        if (empty($data)) return;
        if (!isset($data[0])) {
            $arr[0] = $data;
        } else {
            $arr = $data;
        }

        $fields = array_keys($arr[0]);
        $values = array();
        foreach ($arr as $d) {
            $values[] = '("' . implode('", "', array_values($this->escapeString($d))) . '")';
        }

        $sql = 'REPLACE INTO `' . $table . '`';
        $sql .= ' (`' . implode('`, `', $fields) . '`)';
        $sql .= ' VALUES ' . implode(', ', $values);
        mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
        $this->_lastSql = $sql;
        return mysql_insert_id($this->_link);
    }

    public function update($table, array $data, $where)
    {
        $sets = array();
        foreach ($data as $field => $value) {
            if (preg_match('/^\{.*\}$/', $value)) { // 特殊处理标识,如'count' => '{count + 1}'
                $sets[] = '`' . $field . '` = ' . substr($this->escapeString($value), 1, -1);
            } else {
                $sets[] = '`' . $field . '` = "' . $this->escapeString($value) . '"';
            }
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE ' . $where;
        $this->_lastSql = $sql;
        return mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
    }

    public function delete($table, $where)
    {
        $sql = 'DELETE FROM `' . $table . '` WHERE ' . $where;
        $this->_lastSql = $sql;
        return mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
    }

    public function execute($sql)
    {
        $this->_lastSql = $sql;
        return mysql_query($sql, $this->_link) or die(mysql_error($this->_link) . ' in SQL : ' . $sql);
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
        mysql_query('BEGIN', $this->_link) or die(mysql_error($this->_link));
    }

    public function commit()
    {
        mysql_query('COMMIT', $this->_link) or die(mysql_error($this->_link));
    }

    public function rollback()
    {
        mysql_query('ROLLBACK', $this->_link) or die(mysql_error($this->_link));
    }
}

?>