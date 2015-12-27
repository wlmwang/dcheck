<?php
/**
 * 
 * @author wanglm
 *
 */
class ExchangeLogModel extends CArModel
{
	
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'order_id'=>'order_id',
					'game_id'=>'game_id',
					'operator_id'=>'operator_id',
					'type'=>'type',
					'money_type'=>'money_type',
					'server_id'=>'server_id',
					'u_money'=>'u_money',
					'game_money'=>'game_money',
					'timeline'=>'timeline',
					'datetime'=>'datetime',
					'account'=>'account',
					'extra'=>'extra',
					'test_account'=>'test_account',
				),
				'columns'=>array(
					'plat'=>'int',
					'order_id'=>'char(100)',
					'game_id'=>'smallint(5)',
					'operator_id'=>'int(11)',
					'type'=>'tinyint(4)',//(是否直充) type=1 直冲 ， type=2 兑换 ,type=0 未知平台
					'money_type'=>'smallint(4)',//货币种类 currency_id 1 人民币 2 美元
					'server_id'=>'bigint(20)',
					'u_money'=>'decimal(10,2)',
					'game_money'=>'decimal(10,2)',
					'timeline'=>'int(11)',
					'datetime'=>'date',
					'account'=>'varchar(50)',
					'extra'=>'varchar(200)',
					'test_account'=>'smallint(1)',	
				),
				'pk'=>'`plat`,`datetime`,`order_id`,`operator_id`',
				'partitionKey'=>"datetime|substr,'###',0,10",
				'extra'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
	}
	
	protected function getTableName()
	{
		return "t_exchange_log[###]";
	}
}