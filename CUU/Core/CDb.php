<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CDb
{	
	protected $_Dbs = array();
	protected $_Db = NULL;
	protected $_DbConf = array();

    public function __construct()
    {
    	$_dbConf = CApp::app()->getConf('Db');
    	$_Dbs	= array();
    	if (!$_dbConf)
    	{
    		return NULL;
    	}
		foreach ($_dbConf as $identify => $conf)
		{
			switch (!empty($conf['lib']))
			{
				case 1:
					$LibClass = 'C'.ucfirst(strtolower($conf['lib']));
					$_Dbs[$identify] = $this->_Dbs[$identify] = $LibClass::createDb($conf);
				default:
					$_Dbs[$identify] = $this->_Dbs[$identify] = CMysql::createDb($conf);
					break;
			}
		}
		$this->_DbConf = array_shift($_dbConf);
		return !empty($this->_Dbs)? ($this->_Db = array_shift($_Dbs)): NULL;
    }

    public function switchDb($identify=NULL)
    {
    	if ($identify)
    	{
    		if ($this->_Dbs && array_key_exists($identify, $this->_Dbs))
    		{
    			$this->_Db = $this->_Dbs[$identify];
    		}
    		else 
    		{
    			$msg = "ERROR DB switch!";
    			return NULL;
    		}
    	}
    	return $this->_Db;
    }
    
    public function find($table,$condition,$aOption=NULL)
    {
    	return $this->_Db->find($table,$condition,$aOption);
    }
    
    public function findAll($table,$condition,$aOption=NULL)
    {
    	return $this->_Db->findAll($table,$condition,$aOption);
    }
    
    public function count($table,$condition,$aOption=NULL)
    {
    	return $this->_Db->count($table,$condition);
    }

    public function replace($table,$_data,$_where)
    {
    	return $this->_Db->replace($table,array_merge($_data,$_where));
    }

    public function insert($table,$data)
    {
    	return $this->_Db->insert($table,$data);
    }

    public function update($table,$data, $where,$aOption=NULL)
    {
    	return $this->_Db->update($table,$data,$where);
    }
    
    public function autoCreateDb($_dbName)
    {
    	return $this->_Db->create($_dbName);
    }
    
    public function autoCreateTable($_tablename,$fields)
    {
    	return $this->_Db->createTable($_tablename,$fields);
    }
    
    public function delete($table, $where,$aOption=NULL)
    {
    	return $this->_Db->delete($table,$where);
    }

    public function execute($sql)
    {
    	return $this->_Db->execute($sql);
    }
    
    public function query($sql)
    {
    	return $this->_Db->query($sql);
    }

    public function getLastSql()
    {
    	return $this->_Db->getLastSql();
    }

    public function close()
    {
    	return $this->_Db->close();
    }

    public function begin()
    {
    	return $this->_Db->begin();
    }

    public function commit()
    {
    	return $this->_Db->commit();
    }

    public function rollback()
    {
    	return $this->_Db->rollback();
    }
}

?>