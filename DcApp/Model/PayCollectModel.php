<?php
/**
 * 
 * @author wanglm
 *
 */
class PayCollectModel extends CArModel
{
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'game_id'=>'game_id',
					'operator_id'=>'operator_id',
					'money_type'=>'money_type',
					'server_id'=>'server_id',
					'u_money'=>'u_money',
					'game_money'=>'game_money',
					'datetime'=>'datetime',
					'type'=>'type',
					'channel'=>'channel',
				),
				'columns'=>array(
					'plat'=>'int',
					'game_id'=>'smallint(5)',
					'operator_id'=>'int(11)',
					'money_type'=>'tinyint(4)',
					'server_id'=>'bigint(20)',
					'u_money'=>'decimal(10,2)',
					'game_money'=>'decimal(10,2)',
					'datetime'=>'date',
					'type'=>'tinyint(4)',
					'channel'=>'int(10)',
				),
				'pk'=>'`plat`,`datetime`,`type`,`game_id`,`operator_id`,`server_id`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_pay_collect[###]";
	}
}