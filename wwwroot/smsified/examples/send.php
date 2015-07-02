<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="author" content="淡淡清香弥漫世界(粤工程黎景泉)" />
<meta name="keywords" content="短信接收,短信发送,Recived SMS,Send SMS,Free SMS" />
<meta name="description" content="此程序提供短信接收和发送入口。" />
<title>短信盒子 - SMSBox</title>
<?php

// Include the SMSifed class.
require 'smsified.class.php';

// SMSified Account settings.
$username = "用户名";
$password = "密码";
$senderAddress = "号码，带国际区号";
$js=$_POST["js"];
$nr=$_POST["nr"];
try {	
	
	// Create a new instance of the SMSified object.
	$sms = new SMSified($username, $password);
	
	// Send an SMS message and decode the JSON response from SMSified.
	$response = $sms->sendMessage($senderAddress, $js, $nr);
	$responseJson = json_decode($response);
  echo '已提交，点击<a href="index.php">这里</a>查看状态。';	
}

catch (SMSifiedException $ex) {
	echo "内部错误!";
}



?>
</head>
</html>