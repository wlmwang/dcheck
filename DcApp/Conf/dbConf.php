<?php
$conf = array(
	'dev'=> 
		array( 
			'192.168.1.40'
		),
	'qa'=>
		array(
			'122.226.64.245',
			'10.0.1.219'
		),
/* 	'rc'=> 
		array(
			'61.174.11.30',
			'10.0.20.21'
		), */
);

$connectionString = array(
		'dev'=>
		array(
			'tcheck'=>array(
					'lib'	=>'mysql',//mysqli|pdo
					'host'	=> '127.0.0.1',
					'port'	=> '3306',
					'user'	=> 'datacenter',
					'password'=> 'UvCBRyJa2XsGPck4',
					'dbName'=> 't_check',
					'charset'=>'utf8',
					'auto'=>TRUE,//自动建库
			),
		),
		'qa'=>
		array(
			'tcheck'=>array(
					'lib'	=>'mysql',//mysqli|pdo
					'host'	=> '127.0.0.1',
					'port'	=> '3306',
					'user'	=> 'datacenter',
					'password'=> '3rmvVPFe13fTEkeC',
					'dbName'=> 't_check',
					'charset'=>'utf8',
					'auto'=>TRUE,//自动建库
			),
		),
/* 		'rc'=>
		array(
			'tcheck'=>array(
					'lib'	=>'mysql',//mysqli|pdo
					'host'	=> '127.0.0.1',
					'port'	=> '3306',
					'user'	=> 'datacenter',
					'password'=> 'UVXuwsF68rl4Ldz7',
					'dbName'=> 't_check',
					'charset'=>'utf8',
					'auto'=>TRUE,//自动建库
			),
		), */
);

$env = 'dev';
foreach ($conf as $key=>$aIplist)
{
	if (empty($_SERVER['SERVER_ADDR'])) 
	{
		$ss = exec('/sbin/ifconfig eth1 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'',$arr);
		$_SERVER['SERVER_ADDR'] = $arr[0];
	}
	if (in_array($_SERVER['SERVER_ADDR'], $aIplist)) 
	{
		$env = $key; 
		break;
	}
}

return $connectionString[$env];