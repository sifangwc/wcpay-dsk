<?php
include_once "vendor/autoload.php";
$app_key = ''; // 请于对接群中copy
$app_secret = ''; // 请于对接群中copy
$gateway = ''; // 不带path的网址比如http://api.xxx.com
$notify_url = ""; // 异步通知地址
$pay = new \Laowin\WcpaySdk\WcPay($app_key, $app_secret, $gateway);
$pay->SetCode("ali_pay");
$pay->SetOtn($otn = md5(time()));
$pay->SetPrice(100.00);
$pay->SetNotifyUrl($notify_url);
$order_info = $pay->GenerateOrder();
var_dump($order_info);
$query_info = $pay->QueryOrder($otn);
var_dump($query_info);

//$pay->CheckNotify($_REQUEST); // 使用这个方法进行验签，请注意有的反代配置可能改写请求参数导致验签失败