<?php
/**
 * UB存量
 * @author wanglm
 *
 */
class MoneyStockModel extends CArModel
{
	
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'datetime'=>'datetime',
					'recordtime'=>'recordtime',
					'stock'=>'stock',
					'pay'=>'pay',
					'exchange'=>'exchange',
					'gift'=>'gift',
				),
				'columns'=>array(
					'plat'=>'int',
					'datetime'=>'date',
					'recordtime'=>'datetime',//汇总时间
					'stock'=> 'decimal(10,2)',//U币剩余
					'pay'=>'decimal(10,2)',//U币充值
					'exchange'=>'decimal(10,2)',//U币兑换
					'gift'=>'decimal(10,2)',//U币赠送
					
				),
				'pk'=>'`plat`,`datetime`',
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return 't_money_stock';
	}
}