<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="author" content="����������������(�������辰Ȫ)" />
<meta name="keywords" content="���Ž���,���ŷ���,Recived SMS,Send SMS,Free SMS" />
<meta name="description" content="�˳����ṩ���Ž��պͷ�����ڡ�" />
<title>���ź��� - SMSBox</title>
<?php

// Include the SMSifed class.
require 'smsified.class.php';

// SMSified Account settings.
$username = "�û���";
$password = "����";
$senderAddress = "���룬����������";
$js=$_POST["js"];
$nr=$_POST["nr"];
try {	
	
	// Create a new instance of the SMSified object.
	$sms = new SMSified($username, $password);
	
	// Send an SMS message and decode the JSON response from SMSified.
	$response = $sms->sendMessage($senderAddress, $js, $nr);
	$responseJson = json_decode($response);
  echo '���ύ�����<a href="index.php">����</a>�鿴״̬��';	
}

catch (SMSifiedException $ex) {
	echo "�ڲ�����!";
}



?>
</head>
</html>