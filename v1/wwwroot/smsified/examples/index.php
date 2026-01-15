<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="author" content="淡淡清香弥漫世界(粤工程黎景泉)" />
<meta name="keywords" content="短信接收,短信发送,Recived SMS,Send SMS,Free SMS" />
<meta name="description" content="此程序提供短信接收和发送入口。" />
<title>短信盒子 - SMSBox</title>
<body>
<h3>状态查看</h3>
<?php
require 'smsified.class.php';
$username = "用户名";
$password = "密码";
try {
	$sms = new SMSified($username, $password);
	@ $response = $sms->getMessages();
	$responseJson = json_decode($response);
 

  echo '<table border="1"><tr><td>唯一标识</td><td>接收号码</td><td>发送号码</td><td>内容</td><td>状态</td><td>方向</td><td>完成时间</td>';
    foreach($responseJson as $obj){
    echo "<tr>";
	if ($obj->status=="success"){
          $success="成功";}
          else{
	$success="失败";
	};
        if ($obj->direction=="in"){
          $box="接收";}
          else{
	$box="发送";
	};
      echo "<td>".$obj->messageId."</td><td>".$obj->to."</td><td>".$obj->from."</td><td>".$obj->body."</td><td>".$success."</td><td>".$box."</td><td>".$obj->sent."</td><td>";
      echo "</tr>";
    }

echo "</table>";

}

catch (SMSifiedException $ex) {
	echo "内部错误!";
}

?>

<h3>短信发送</h3>
<form action="send.php" method="post">
接收号码: <input type="text" name="js" />
短信内容: <input type="text" name="nr" />
<input type="submit" />
</form>

<h3>使用说明</h3>
<ul>
<li>程序核心由SAE驱动，数据由Voxeo提供。</li>
<li>本程序暂时免费开放接收和发送功能，但请不要滥用。</li>
<li>程序不包含错误细分系统，请不要乱操作。</li>
<li>接收号码目前提供+17177455056(美国)。</li>
<li>版权归作者<a href="http://weibo.com/gdxkc">淡淡清香弥漫世界</a>所有。</li>
</ul>
</body>
</head>
</html>