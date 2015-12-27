<?php
/**
 * 
 * @author wanglm
 *
 */
class CheckExchangeLogModel extends CArModel
{
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'game_id'=>'game_id',
					'operator_id'=>'operator_id',
					'type'=>'type',
					'server_id'=>'server_id',
					'u_money'=>'u_money',
					'game_money'=>'game_money',
					'datetime'=>'datetime',
				),
				'columns'=>array(
					'plat'=>'int',
					'game_id'=>'smallint(5)',
					'operator_id'=>'int(11)',
					'type'=>'tinyint(4)',
					'server_id'=>'bigint(20)',
					'u_money'=>'decimal(10,2)',
					'game_money'=>'decimal(10,2)',
					'datetime'=>'date',
				),
				'pk'=>'`plat`,`datetime`,`operator_id`,`game_id`,`server_id`,`type`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_ckeck_exchange_log[###]";
	}
}