<?php
/**
 * 2014-03-09
 * @author wanglm
 *
 */
class CArModel extends CModel
{
	protected $_select = array();
	protected $_where = array();
	protected $_limit = array();
	protected $_orderby = array();
	protected $_groupby = array();
	protected $_data = array();
	
	private   $_operator = ''; 
	
	public function getMapping()
	{
		if (!$this->_fields)
		{
			if ($this->_tableName!='CAr')
			{
				$res = $this->setOperator('mapping')->run();
				if (!empty($res)) 
				{
					foreach ($res as $key=>$value)
					{
						$fields['columns'][$key]	= $key;
						$fields['label'][$key]	= $key;
					}
					return $fields;
				}
			}
		}
		return $this->_fields;
	}

	public function db($dbName)
	{
		if ($dbName)
		{
			$this->_dbName = $dbName;
		}
		return $this;
	}
	
	public function table($tableName)
	{
		if ($tableName)
		{
			$this->_tableName = $tableName;
			$this->init();
		}
		return $this;
	}
	
	public function reset()
	{
		$this->_select = array();
		$this->_where = array();
		$this->_limit = array();
		$this->_orderby = array();
		$this->_groupby = array();
		$this->_data = array();
		$this->_operator = '';
		return $this;
	}
	
	public function select($aSelect=array())
	{
		$this->_select = $aSelect;
		return $this;
	}
	
	public function groupby($aGroupby=array())
	{
		$this->_groupby = $aGroupby;
		return $this;
	}
	
	public function limit($aLimit=array())
	{
		$this->_limit = $aLimit;
		return $this;
	}
	
	public function orderby($aOrderby=array())
	{
		$this->_orderby = $aOrderby;
		return $this;
	}
	/**
	 * Mix
	 * @see CDb::execute()
	 */
	public function execute($sql)
	{
		return $this->setOperator('execute')->run($sql);
	}
	
	/**
	 * Mix
	 * @see CDb::execute()
	 */
	public function query($sql)
	{
		return $this->setOperator('query')->run($sql);
	}
	
	/**
	 * @return array
	 * @see CDb::find()
	 */
	public function find($appendWhere=NULL)
	{
		if(!!($res = $this->setOperator('find')->where($appendWhere)->run()))
		{
			$this->array2Model($res);
		}
		return $res;
	}
	
	/**
	 * @return array
	 * @see CDb::findAll()
	 */
	public function findAll($appendWhere=NULL)
	{
		if(!!($tmp = $res = $this->setOperator('findall')->where($appendWhere)->run()))
		{
			$this->array2Model(array_shift($tmp));
		}
		return $res;
	}
	
	public function count($appendWhere=NULL)
	{
		return $this->setOperator('count')->where($appendWhere)->run();
	}
	/**
	 * @return int
	 * @see CDb::insert()
	 */
	public function insert($appendData=NULL)
	{
		$this->array2Model($appendData);
		foreach ($this as $key=>$value)
		{
			if (array_key_exists($key,$this->_fields['columns']))
			{
				$this->_data[$key] = $value;
			}
		}
		if(($res = $this->setOperator('insert')->run())!==FALSE)
		{
			$pk = $this->getPk();
			$res && $pk && $this->find(array($pk[0]=>$res));
		}
		return $res;
	}
	
	/**
	 * 会且只会新增、更新一条记录
	 * 主键（联合主键时，必须全部提供值）作为依据，判断 新增 或 更新记录
	 * @see CDb::save()
	 */
	public function save($appendData=NULL)
	{
		$this->array2Model($appendData);
		foreach ($this as $key=>$value)
		{
			if (array_key_exists($key,$this->_fields['columns']))
			{
				if ($this->isPk($key))
				{
					$this->_where[$key] = $value;
				}
				else
				{
					$this->_data[$key] = $value;
				}
			}
		}
		//会且只会新增、更新一条记录
		if (count($this->getPk())!=count($this->_where)) 
		{
			$this->_data = array_merge($this->_data,$this->_where);
		}
		elseif (!empty($this->_where))
		{
			if($this->setOperator('find')->run())
			{
				$res =  $this->setOperator('update')->run();
				
				$this->array2Model($this->setOperator('find')->run());
				return $res;
			}
			else
			{
				$this->_data = array_merge($this->_data,$this->_where);
			}
		}
		$res = $this->setOperator('insert')->run();
		
		$pk = $this->getPk();
		$res && $pk && $this->find(array($pk[0]=>$res));
		return $res;
	}
	
	/**
	 * 更新
	 * @see CDb::update()
	 */
	public function update($appendData=NULL,$appendWhere=NULL)
	{
		$this->array2Model($appendData);
		$this->where($appendWhere);
		foreach ($this as $key=>$value)
		{
			if (array_key_exists($key,$this->_fields['columns']))
			{
				if ($this->isPk($key))
				{
					$this->_where[$key] = $value;
				}
				else
				{
					$this->_data[$key] = $value;
				}
			}
		}
		if (!empty($this->_where))
		{
			if($this->setOperator('find')->run())
			{
				$res = $this->setOperator('update')->run();
				$this->array2Model($this->setOperator('find')->run());
				return $res;
			}
		}
		return FALSE;
	}
	
	/**
	 * @return array
	 * @see CDb::delete()
	 */
	public function delete($appendWhere=NULL)
	{
		return $this->setOperator('delete')->where($appendWhere)->run();
	}
	
	//**************private function***********************
	/**
	 * 1.id='1' and name='xxx'
	 * 2.array('id'=>1,'name'=>'xxx');
	 * 3.array('id'=>1,'name'=>'xxx','_logic_'=>'or');
	 * 4.array(array(array('id'=>1,'name'=>'xxx','_logic_'=>'or'),'type'=>'2','_logic_'=>'and'),'game'=>'sss');
	 * 5.array('name'=>array('like','xxx'))
	 * 6.array('name'=>array(array('=','xxx'),array('like','zzz'),'_logic_'=>'and'))
	 * @param string $appendWhere
	 * @return CArModel
	 */
	private function where($appendWhere=NULL)
	{
		foreach ($this as $key=>$value)
		{
			if (!empty($this->_fields['columns']) && array_key_exists($key,$this->_fields['columns']))
			{
				$this->_where[$key] = $value;
			}
		}
		
		if ($appendWhere && is_string($appendWhere))
		{
			$this->_where = $appendWhere;//覆盖所有条件
		}
		elseif($appendWhere && is_array($appendWhere))
		{
			$this->_where['_append_'] = $appendWhere;
		}
		
		return $this;
	}
	
	/**
	 * @param string $operator
	 * @return CArModel
	 */
	private function setOperator($operator)
	{
		$this->_operator = $operator;
		return $this;
	}
	
	private function getOperator()
	{
		return $this->_operator;
	}
	
	private function run($sql=NULL)
	{
		$_dbName	=  $this->_dbName;
		$_tableName	=  $this->_tableName;
		
		//分库预留
		if (method_exists($this,'getRealDbName'))
		{
			$_dbName = $this->getRealDbName($this);
		}
		//分表预留
		if (method_exists($this,'getRealTableName'))
		{
			$_tableName = $this->getRealTableName($this);
		}
		
		$_DT_ = "`$_dbName`.`$_tableName`";
		
		//自动建库建库表
		$this->autoCreateDb($_dbName);
		$this->autoCreateTable($_DT_,$this->_fields);
		
		$aOption = array(
				'select'=>$this->_select,
				'groupby'=>$this->_groupby,
				'limit'=>$this->_limit,
				'orderby'=>$this->_orderby,
		);
		//执行方法
		switch ($this->_operator)
		{
			case 'find':
				$res = parent::find($_DT_,$this->_where,$aOption);
				break;
			case 'findall':
				$res = parent::findAll($_DT_,$this->_where,$aOption);
				break;
			case 'insert':
				$res = parent::insert($_DT_,$this->_data);
				break;
			case 'delete':
				$res = parent::delete($_DT_,$this->_where,$aOption);
				break;
			case 'update':
				$res = parent::update($_DT_,$this->_data,$this->_where,$aOption);
				break;
			case 'execute':
				$res = parent::execute($sql);
				break;
			case 'query':
				$res = parent::query($sql);
				break;
			case 'count':
				$res = parent::count($_DT_,$this->_where,$aOption);
				break;
			case 'mapping':
				$res = parent::find($_DT_,$this->_where,$aOption);
				break;
		}
		
		return $res;
	}
	
}

?>