<?php
/**
 * 
 * @author wanglm
 *
 */
class GiftLogModel extends CArModel
{
	
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'order_id'=>'order_id',
					'vip'=>'vip',
					'account'=>'account',
					'uid'=>'uid',
					'u_money'=>'u_money',
					'rebate'=>'rebate',
					'timeline'=>'timeline',
					'datetime'=>'datetime',
				),
				'columns'=>array(
					'plat'=>'int',
					'order_id'=>'char(100)',
					'u_money'=>'decimal(10,2)',
					'rebate'=>'decimal(10,2)',
					'timeline'=>'int(11)',
					'datetime'=>'date',
					'account'=>'varchar(50)',
					'uid'=>'int(11)',
					'vip'=>'smallint(4)',	
				),
				'pk'=>'`plat`,`datetime`,`order_id`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_gift_log[###]";
	}
}