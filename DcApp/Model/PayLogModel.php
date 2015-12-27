<?php
/**
 * 
 * @author wanglm
 *
 */
class PayLogModel extends CArModel
{
	
	public function getMapping()
	{
		return array(
				'label'=>array(
					'plat'=>'plat',
					'order_id'=>'order_id',
					'game_id'=>'game_id',
					'operator_id'=>'operator_id', 
					'money_type'=>'money_type',
					'type'=>'type',
					'server_id'=>'server_id',
					'u_money'=>'u_money',
					'game_money'=>'game_money',
					'timeline'=>'timeline',
					'datetime'=>'datetime',
					'account'=>'account',
					'channel'=>'channel',
					'is_channel'=>'is_channel',
					'extra'=>'extra',
					'test_account'=>'test_account',
				),
				'columns'=>array(
					'plat'=>'int',
					'order_id'=>'char(100)',
					'game_id'=>'smallint(5)',
					'operator_id'=>'int(11)',//运营商 平台ID
					'money_type'=>'smallint(4)',//货币种类 currency_id 1 人民币 2 美元
					'type'=>'tinyint(4)',//(是否直充) type=1 直冲 ， type=2 兑换 ,type=0 未知平台
					'server_id'=>'bigint(20)',
					'u_money'=>'decimal(10,2)',
					'game_money'=>'decimal(10,2)',
					'timeline'=>'int(11)',
					'datetime'=>'date',
					'account'=>'varchar(50)',
					'channel'=>'smallint(5)',//渠道
					'is_channel'=>'int(5)',//获取充值方式
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
		return "t_pay_log[###]";
	}
}