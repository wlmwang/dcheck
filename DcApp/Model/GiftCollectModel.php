<?php
/**
 * 
 * @author wanglm
 *
 */
class GiftCollectModel extends CArModel
{
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'datetime'=>'datetime',
					'u_money'=>'u_money',
					'rebate'=>'rebate',
				),
				'columns'=>array(
					'plat'=>'int',
					'u_money'=>'decimal(10,2)',
					'rebate'=>'decimal(10,2)',
					'datetime'=>'date',
				),
				'pk'=>'`plat`,`datetime`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_gift_collect[###]";
	}
}