<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="author" content="����������������(�������辰Ȫ)" />
<meta name="keywords" content="���Ž���,���ŷ���,Recived SMS,Send SMS,Free SMS" />
<meta name="description" content="�˳����ṩ���Ž��պͷ�����ڡ�" />
<title>���ź��� - SMSBox</title>
<body>
<h3>״̬�鿴</h3>
<?php
require 'smsified.class.php';
$username = "�û���";
$password = "����";
try {
	$sms = new SMSified($username, $password);
	@ $response = $sms->getMessages();
	$responseJson = json_decode($response);
 

  echo '<table border="1"><tr><td>Ψһ��ʶ</td><td>���պ���</td><td>���ͺ���</td><td>����</td><td>״̬</td><td>����</td><td>���ʱ��</td>';
    foreach($responseJson as $obj){
    echo "<tr>";
	if ($obj->status=="success"){
          $success="�ɹ�";}
          else{
	$success="ʧ��";
	};
        if ($obj->direction=="in"){
          $box="����";}
          else{
	$box="����";
	};
      echo "<td>".$obj->messageId."</td><td>".$obj->to."</td><td>".$obj->from."</td><td>".$obj->body."</td><td>".$success."</td><td>".$box."</td><td>".$obj->sent."</td><td>";
      echo "</tr>";
    }

echo "</table>";

}

catch (SMSifiedException $ex) {
	echo "�ڲ�����!";
}

?>

<h3>���ŷ���</h3>
<form action="send.php" method="post">
���պ���: <input type="text" name="js" />
��������: <input type="text" name="nr" />
<input type="submit" />
</form>

<h3>ʹ��˵��</h3>
<ul>
<li>���������SAE������������Voxeo�ṩ��</li>
<li>��������ʱ��ѿ��Ž��պͷ��͹��ܣ����벻Ҫ���á�</li>
<li>���򲻰�������ϸ��ϵͳ���벻Ҫ�Ҳ�����</li>
<li>���պ���Ŀǰ�ṩ+17177455056(����)��</li>
<li>��Ȩ������<a href="http://weibo.com/gdxkc">����������������</a>���С�</li>
</ul>
</body>
</head>
</html>