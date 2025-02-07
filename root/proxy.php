<?php
define('THIS_SCRIPT', 'proxy.php');
if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}
$ipaddress = $_SERVER['REMOTE_ADDR'];
?>
<!DOCTYPE html>
<html>
<head>
<title>VPN/Proxy Detected</title>
</head>
<body bgcolor="#202020">
<script>
function copUP(){
var ip = prompt("CTRL+C to copy", "<?php echo $ipaddress; ?>");
}
</script>
<style>
body{
font-family:'Trebuchet MS';
}
.bg{
background-color:#DA2C2C;
color:#060C17;
border-radius:10px;
width:60%;
margin:auto;
top:30px;
padding:10px;
}
a:link,a:visited {
color: White;
text-decoration: none;
target-new: none;
}
a:hover {
color: Gray;
background-color: DA2C2C;
text-decoration: none;
target-new: none;
}
</style>
</style>
<br/><br/>
<div align="center" class="bg">
<br/>
<h1>Whoa there!</h1>
<h3>Our software has determined that you are connecting to our site via a VPN or Proxy network. These networks hide information which lets us know you're not trying to break any of our rules. We ask that to use our site, you disable any running VPNs or Proxies.
<br/></h3>
<h3>If this error is persisting and you're sure you're not on a VPN/Proxy, please open a ticket on our <a href="https://v3rm.rip/discord">discord</a> server and include your current IP address, which is: <a href="javascript:copUP()"><?php echo $ipaddress; ?></a>.</h3>
<h3><a href="javascript:history.go(-1);">Click here</a> to go back.</h3></h3>
<br/>
</div>
</html>