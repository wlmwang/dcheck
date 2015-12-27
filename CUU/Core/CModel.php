<?php
/**
 * 2014-03-06
 * @author wanglm
 *
 */
class CModel extends CDb
{
	public $_tableName = NULL;
	public $_partitionTable = NULL;
	public $_dbName = NULL;
	public $_partitionDb = NULL;
	public $_fields = array();
	
	protected function getMapping(){}//Must extends this function<getMapping>
	
	public function init()
	{
		$this->_dbName = $this->getDbName();
		$this->_tableName = $this->getTableName();
		$this->_fields = $this->getMapping();
	}
	
	public function __construct()
	{
		parent::__construct();
		
		$this->init();
	}
	
	/**
	 * TODO.
	 * @return mixed|NULL
	 */
	protected function getDbName()
	{
		if ($this->_dbName)
		{
			return $this->_dbName;
		}
		if ($this->_DbConf)
		{
			return $this->_DbConf['dbName'];
		}
	}
	
	protected function getTableName()
	{
		if ($this->_tableName)
		{
			return $this->_tableName;
		}
		$className = get_class($this);
		return substr($className, 0,strlen($className)-5);
	}
	
	protected function getRealTableName($oModel)
	{
		$reg = "/\[#{3}\]/";
		$tableName = $oModel->getTableName();
		if ($oModel->_partitionTable && preg_match($reg,$tableName))
		{
			return preg_replace($reg, $oModel->_partitionTable, $tableName);
		}
		return $oModel->_tableName;
	}
	
	protected function getRealDbName($oModel)
	{
		$reg = "/\[#{3}\]/";
		$dbName = $oModel->getDbName();
		if ($oModel->_partitionDb && preg_match($reg,$dbName))
		{
			return preg_replace($reg, $oModel->_partitionDb, $dbName);
		}
		return $oModel->_dbName;
	}
	
	public function getPk()
	{
		if (empty($this->_fields['pk']))
		{
			return array();
		}
		return explode(',', $this->_fields['pk']);
	}
	
	public function isPk($key)
	{
		if (empty($this->_fields['pk']))
		{
			return FALSE;
		}
		return in_array($key, explode(',', $this->_fields['pk']))? TRUE:FALSE;
	}
	
	public function array2Model($arr)
	{
		if ($arr)
		{
			foreach ($arr as $key=>$value)
			{
				$this->$key = $value;
			}
		}
		return $this;
	}
	
}

?>