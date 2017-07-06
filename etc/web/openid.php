<?php

/*
    Ophidia Server
    Copyright (C) 2012-2017 CMCC Foundation

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

	include('env.php');
	if (empty($oph_openid_endpoint))
		header('Location: '.$oph_web_server_secure);
	if (empty($_SERVER['HTTPS']))
		header('Location: '.$oph_web_server_secure.'/openid.php');
	else 
	{
		session_start();
		$error = '';
		$message = 'Login';
		if (isset($_GET['error']))
		{
			$error = $_GET['error_description'];
			unset($_SESSION['code']);
			session_destroy();
		}
		if (!isset($_SESSION['code']) && (isset($_GET['code']) || isset($_POST['code'])))
		{
			if (isset($_GET['code'])) $code = $_GET['code'];
			else if (isset($_POST['code'])) $code = $_POST['code'];
			$_SESSION['code'] = $code;
		}
		if (isset($_SESSION['code']))
		{
			$message = 'Get token';
			if (isset($code) || (isset($_POST['submit']) && ($_POST['submit'] == $message)))
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $oph_openid_endpoint.'/token');
				curl_setopt($ch, CURLOPT_USERPWD, $oph_openid_client_id.':'.$oph_openid_client_secret);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&code='.$_SESSION['code'].'&redirect_uri='.$oph_web_server_secure.'/openid.php');
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$json = curl_exec($ch);
				curl_close($ch);
				$output = json_decode($json, 1);
				if (isset($output['error']))
				{
					$error = $output['error_description'];
					$message = 'Login';
				}
				else
					$token = $output['access_token'];
				unset($_SESSION['code']);
				session_destroy();
			}
		}
		else if (isset($_GET['submit']) || isset($_POST['submit']))
		{
			$continue = true;
			$nonce = openssl_random_pseudo_bytes(10);
			header('Location: '.$oph_openid_endpoint.'/authorize?response_type=code&client_id='.$oph_openid_client_id.'&scope=openid+profile+email&redirect_uri='.$oph_web_server_secure.'/openid.php&nonce=123');
		}
	}
	if (!isset($continue))
	{
?>
<!--
    Ophidia Server
    Copyright (C) 2012-2017 CMCC Foundation

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->
<!DOCTYPE HTML>
<HTML>
	<HEAD>
		<TITLE>Ophidia Server</TITLE>
		<LINK href="style.css" rel="stylesheet" type="text/css" />
		<SCRIPT type="text/javascript">
			function show_wait() {
				var error_label = document.getElementById("error");
				error_label.style.color = "green";
				error_label.textContent = "Wait for the request to be processed";
            }
		</SCRIPT>
	</HEAD>
	<BODY>
		<DIV id="main">
			<DIV id="login">
				<H2>OpenId Connect</H2>
				<FORM action="openid.php" method="post" onsubmit="show_wait()">
					<INPUT name="submit" type="submit" value="<?php echo $message; ?>"/>
					<SPAN id="error"><?php echo $error; ?></SPAN>
				</FORM>
			</DIV>
		</DIV>
<?php
		if (isset($token)) {
?>
		<DIV id="token">
			<H5>Access token</H5>
			<TEXTAREA rows="4" cols="133" onclick="this.focus();this.select();" readonly="readonly"><?php echo $token; ?></TEXTAREA>
		</DIV>
<?php
		}
?>
	</BODY>
</HTML>
<?php
	}
?>
