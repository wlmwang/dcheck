<?php
/**
 * 
 * @author wanglm
 *
 */
class CheckPayLogModel extends CArModel
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
					'channel'=>'channel',
				),
				'columns'=>array(
					'plat'=>'int',
					'game_id'=>'smallint(5)',
					'operator_id'=>'int(11)',
					'money_type'=>'smallint(5)',
					'server_id'=>'bigint(20)',
					'u_money'=>'decimal(10,2)',
					'game_money'=>'decimal(10,2)',
					'datetime'=>'date',
					'channel'=>'tinyint(4)',
				),
				'pk'=>'`plat`,`datetime`,`operator_id`,`game_id`,`server_id`,`channel`,`money_type`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_ckeck_pay_log[###]";
	}
}