<?php
include('tools/producttypes.php');


$activation = (array_key_exists('activation-info-base64', $_POST))
			  ? base64_decode($_POST['activation-info-base64']) 
			  :(array_key_exists('activation-info', $_POST)) ? ($_POST['activation-info-base64'])
	

if(!isset($activation) || empty($activation)) { exit('Activation info not found!'); }


$encodedrequest = new DOMDocument;
$encodedrequest->loadXML($activation);
$activationDecoded= base64_decode($encodedrequest->getElementsByTagName('data')->item(0)->nodeValue);

$decodedrequest = new DOMDocument;
$decodedrequest->loadXML($activationDecoded);
$nodes = $decodedrequest->getElementsByTagName('dict')->item(0)->getElementsByTagName('*');

for ($i = 0; $i < $nodes->length - 1; $i=$i+2)
{

	switch ($nodes->item($i)->nodeValue)
	{
		case "ActivationRandomness": $activationRandomness = $nodes->item($i + 1)->nodeValue; break;
		case "DeviceClass": $deviceClass = $nodes->item($i + 1)->nodeValue; break;
		case "SerialNumber": $serialNumber = $nodes->item($i + 1)->nodeValue; break;
		case "UniqueDeviceID": $uniqueDiviceID = $nodes->item($i + 1)->nodeValue; break;
		case "MobileEquipmentIdentifier": $meid = $nodes->item($i + 1)->nodeValue; break;
		case "InternationalMobileEquipmentIdentity": $imei = $nodes->item($i + 1)->nodeValue; break;
		case "ActivationState": $activationState = $nodes->item($i + 1)->nodeValue; break;
		case "ProductVersion": $productVersion = $nodes->item($i + 1)->nodeValue; break;
	}
}


$snLength = strlen($serialNumber);

if($snLength > 12){
	echo "Hmmm something isn't right don't ya think?";
	exit();
}
if($snLength < 11){
	echo "Hmmm something isn't right dont ya think?";
	exit();
}

$udidLength = strlen($uniqueDiviceID);
if($udidLength < 40){
	echo "Hmmm something isn't right don't ya think?";
	exit();
}
if($udidLength > 40){
	echo "Hmmm something isn't right don't ya think?";
	exit();
}


$devicefolder = $deviceClass.'/'.$serialNumber.'/';
if (!file_exists($deviceClass.'/')) mkdir($deviceClass.'/', 0777, true);
if (!file_exists($devicefolder))  mkdir($devicefolder, 0777, true);
$decodedrequest->save($devicefolder.'device-request-decoded.xml');

# -------------------------------------------- Sign account token -----------------------------------------

$accountToken2=
'{'.(isset($imei) ? "\n\t".'"InternationalMobileEquipmentIdentity" = "'.$imei.'";' : '').'
   '.(isset($meid) ? "\n\t".'"MobileEquipmentIdentifier" = "'.$meid.'";' : '').
	"\n\t".'"ActivityURL" = "https://albert.apple.com/deviceservices/activity";'.
	"\n\t".'"ActivationRandomness" = "'.$activationRandomness.'";'.
	"\n\t".'"UniqueDeviceID" = "'.$uniqueDiviceID.'";'.
	"\n\t".'"SerialNumber" = "'.$serialNumber.'";'.
	"\n\t".'"PhoneNumberNotificationURL" = "https://albert.apple.com/deviceservices/phoneHome";'.
	"\n\t".'"ProposedTicket" = "MIICrQIBATALBgkqhkiG9w0BAQsxc58/BAlHBhyfQAThEJIAn0sUvG5JQmYihSfOvFjWPs6Ye2lZXpqfh2sHNTJRB5YmWJ+HbQc1MlEHliZYn5c9DAAAAADu7u7u7u7u75+XPgQBAAAAn5c/BAEAAACfl0AEAQAAAJ+XQQQBAAAAn5dMBAAAAAAEggEAhPe9FZryyKZp90dePpHjCcF6t1322T1pqGBmvmivo6XaqkLfjSKjPpnoE8NRPn+D85EvHgVeB190fQbQsT2JH3H+EEXElugS+3+gxrT8YVeTtP+w4eo64GZNU91mjHbputYDmbFOJAVyC3tVpuHdEaHiPkV81sBOpDnc4eWd4HLzJfdQeFxUA/A+TbcyapAHZeEMbjXmqngg7a9Unz12tgnmk6eCsTXWU0GaayGj+kossLOPVNtuIg3dVcuZRzruCMMmzxIDIRVU60XK72CwWX7VSvgIUiUjaZSKTiAc3zmEtkM11WJ/eUnkZiLq261rcRWd3T3WYYN+E+fUaQ4BFaOCASAwCwYJKoZIhvcNAQEBA4IBDwAwggEKAoIBAQCskU9F2dz8TtWBq2D8AdsqcYS51H66DxZmCHEw6U9p3d8vjaEcBdF5VFwETmWJBcTJo/SiPLezdAmG40RfAsxg4sIok0CPhKsTp1mon0JBqai68SdmN0L+AsEbmNK4AjjMX6GM5t7w5mdXpgZyigRtGQDnV2P7HnOZj69PS9r/D4Q50CJNaLrGJZ1UVBNcKkJNTMD2pxrHnxdSLTj51xVITBU71Tdl7KghSskP8WagOONk6J0IcOCwIaWct9A/+Aso4yk5/PDh1YUhbUiIO+z1TL5TdiHLITgc8NXHagB/yiOEEzOx2pcZVXXjwfSZlKRHj66VlWVHgT+bEHZl0/sdAgMBAAE=";'.
	"\n\t".'"FactoryActivated" = "True";'.
	"\n".
 '}';
$accountToken=
'{'.(isset($imei) ? "\n\t".'"InternationalMobileEquipmentIdentity" = "'.$imei.'";' : '').'
   '.(isset($meid) ? "\n\t".'"MobileEquipmentIdentifier" = "'.$meid.'";' : '').
	"\n\t".'"ActivityURL" = "https://albert.apple.com/deviceservices/activity";'.
	"\n\t".'"ActivationRandomness" = "'.$activationRandomness.'";'.
	"\n\t".'"UniqueDeviceID" = "'.$uniqueDiviceID.'";'.
	"\n\t".'"SerialNumber" = "'.$serialNumber.'";'.
	"\n\t".'"FactoryActivated" = "True";'.
	"\n".
 '}';

$accountTokenBase64=base64_encode($accountToken);
$accountTokenBase642=base64_encode($accountToken2);

/* 

The RSA Private Key that is required for iPhone Activation on iOS 7.1.2 must be one of the following:

1) iPhoneActivation.pem 
2) FactoryActivation.pem
3) RaptorActivation.pem
4) Self-Signed iPhoneActivation.cer & iPhoneActivation_private.pem

The following RSA key is a working example of option 4 above.

*/

$private = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDB6D3uBkBTq3ANlIjWUBfjRMbYXMdr6oQeX1uWGWAVvzMO3yEy
mbBOB2P1fUol39VGtT+K8yv6Yf888V3+0vIxlShDXk+HXXNbf77fZ5Qzpwuzv9d0
KUSZwZKYI84xZ4uQ9H5fmgq40fSwF7GSInC0J7tzjppnw0xxzrdbqi4PpQIDAQAB
AoGATbVc3D71GJLj3Q1hqUF/0TyG076azMy3FdTxRz30G8L8G0GgdD7TQPIFRSRo
yrThK+0HAhBh133eY/X2zWCMXk9dBPvlSy8fuEH17gCb1BiQ3yc7ujKtVUt4k8hg
sutREh3zknDAwJx9i0lB6nlQUNug3a5Fvcpo6xmZCl0ZGgECQQDxJ68dReE+0Xj2
uxL005CJdzEUjeQMxFNftNyFWZcY1SQtBMCYB41kE6WPqmbtxNqNPtc0p5ZusOJc
bjv6ZNo1AkEAzdf9C/2mO4igUOOELWmlfXGoQNHuesfH0Skk9Qt0/r8u893ldRN0
IY6fAxqMfXmcgjgOSOqDMhODkb+IbbSNsQJBAIHwwSHD0o/XrRc9TASRrvLzP4X0
wqnCa65JNP3BfXIK/vgm9GO2xg/jqjUUO2vow16SOsGLf7pbI01stHLCPvUCQQCF
U74akyul6gP1ALjvdTt0ujaB7bgrHNXHG4BNnCMmkgzGdlaWc4hH6AoEx6Bx8WA3
VDmkbwmVWOBieg3TCRyxAkBKfDVXdUGBs+EpzQ4kdTyJtxRMOXLnXsQRDmtW8BTn
LlRnlBF5VKlYlTyiQRTsfkSUKmDZzHobxR0c/uDXy5ba
-----END RSA PRIVATE KEY-----';

$pkeyid = openssl_pkey_get_private($private);
$pkeyid2 = openssl_pkey_get_private($private);

openssl_sign($accountToken, $signature, $pkeyid);
openssl_free_key($pkeyid);

openssl_sign($accountToken2, $signature2, $pkeyid2);
openssl_free_key($pkeyid2);
# -------------------------------------------------------------------------------------------------


$accountTokenSignature= base64_encode($signature);
$accountTokenSignature2= base64_encode($signature2);
$accountTokenCertificateBase64 = 'LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSURaekNDQWsrZ0F3SUJBZ0lCQWpBTkJna3Foa2lHOXcwQkFRVUZBREI1TVFzd0NRWURWUVFHRXdKVlV6RVQKTUJFR0ExVUVDaE1LUVhCd2JHVWdTVzVqTGpFbU1DUUdBMVVFQ3hNZFFYQndiR1VnUTJWeWRHbG1hV05oZEdsdgpiaUJCZFhSb2IzSnBkSGt4TFRBckJnTlZCQU1USkVGd2NHeGxJR2xRYUc5dVpTQkRaWEowYVdacFkyRjBhVzl1CklFRjFkR2h2Y21sMGVUQWVGdzB3TnpBME1UWXlNalUxTURKYUZ3MHhOREEwTVRZeU1qVTFNREphTUZzeEN6QUoKQmdOVkJBWVRBbFZUTVJNd0VRWURWUVFLRXdwQmNIQnNaU0JKYm1NdU1SVXdFd1lEVlFRTEV3eEJjSEJzWlNCcApVR2h2Ym1VeElEQWVCZ05WQkFNVEYwRndjR3hsSUdsUWFHOXVaU0JCWTNScGRtRjBhVzl1TUlHZk1BMEdDU3FHClNJYjNEUUVCQVFVQUE0R05BRENCaVFLQmdRREZBWHpSSW1Bcm1vaUhmYlMyb1BjcUFmYkV2MGQxams3R2JuWDcKKzRZVWx5SWZwcnpCVmRsbXoySkhZdjErMDRJekp0TDdjTDk3VUk3ZmswaTBPTVkwYWw4YStKUFFhNFVnNjExVApicUV0K25qQW1Ba2dlM0hYV0RCZEFYRDlNaGtDN1QvOW83N3pPUTFvbGk0Y1VkemxuWVdmem1XMFBkdU94dXZlCkFlWVk0d0lEQVFBQm80R2JNSUdZTUE0R0ExVWREd0VCL3dRRUF3SUhnREFNQmdOVkhSTUJBZjhFQWpBQU1CMEcKQTFVZERnUVdCQlNob05MK3Q3UnovcHNVYXEvTlBYTlBIKy9XbERBZkJnTlZIU01FR0RBV2dCVG5OQ291SXQ0NQpZR3UwbE01M2cyRXZNYUI4TlRBNEJnTlZIUjhFTVRBdk1DMmdLNkFwaGlkb2RIUndPaTh2ZDNkM0xtRndjR3hsCkxtTnZiUzloY0hCc1pXTmhMMmx3YUc5dVpTNWpjbXd3RFFZSktvWklodmNOQVFFRkJRQURnZ0VCQUY5cW1yVU4KZEErRlJPWUdQN3BXY1lUQUsrcEx5T2Y5ek9hRTdhZVZJODg1VjhZL0JLSGhsd0FvK3pFa2lPVTNGYkVQQ1M5Vgp0UzE4WkJjd0QvK2Q1WlFUTUZrbmhjVUp3ZFBxcWpubTlMcVRmSC94NHB3OE9OSFJEenhIZHA5NmdPVjNBNCs4CmFia29BU2ZjWXF2SVJ5cFhuYnVyM2JSUmhUekFzNFZJTFM2alR5Rll5bVplU2V3dEJ1Ym1taWdvMWtDUWlaR2MKNzZjNWZlREF5SGIyYnpFcXR2eDNXcHJsanRTNDZRVDVDUjZZZWxpblpuaW8zMmpBelJZVHh0UzZyM0pzdlpEaQpKMDcrRUhjbWZHZHB4d2dPKzdidFcxcEZhcjBaakY5L2pZS0tuT1lOeXZDcndzemhhZmJTWXd6QUc1RUpvWEZCCjRkK3BpV0hVRGNQeHRjYz0KLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLQo=';
$fairPlayKeyData = 'AAEAAdRSxnR7z2USK+Aej4F8uLcHKewTv2/HrMDhsVEeXFclB3MdFE4LRwfYMbjH
IBAWNQuL9ehsFVJo3lq0YXk593qmux7071N9fOLyNiG74yG+1lzXf4PPVvcgIYwz
l3aNSXvRJKehCfsoQRQlhP7kwQsZ0uCyrc8MJf39xtxl0w3U3+6I5pbtaM9lqxG1
mBm6F89tivTZLlyM4e79pXSKBI/ea3Mr0FOgIoivhWfkCdSq+FL1drNGuQbfwd+Y
nvo/6ELFDGyPNOTmiLZqf5gmihfZuJNXNwFSDY8Gjol+0skb2o4cW4pHU242Zr8X
GB62X1ec3gkBYZIy7C6hHNxLs64XiM3zcVawSAx1LtHZuOESBrjqVLbDVqu0QCDj
l70wpR+QuiUHigxiRT8oDdPGbd/NDgTDjuAWLcae2jIVGJS4YuhPDfyBEbeQbenS
8fhvQPHsgAGPo6/5RdTN/sUuIz6B9zDS67i94ZHaPdkTCgAKdJbjqfiA8HswKDk4
k8955zFe7biwBMNE0MxQ8YUdOqiGhpcXyANS5UN8+UbchsnoHZv64HWvNySsYRwL
OIjqAACtcmdb6sfh0cotSTS5iFpky8QdzZeb68bmGzwdrV1FQ2b5ptvwGiA5kv/N
IeT0am0QdlahhhayFWKi2gYTXdZdazpRxCfzycZkYu4a+WZgMmuqtnwWy1Cd4u8B
bpwtGzZzvIhlAVd6We2LwEFGYS2CoQakG0+XEw68GFANcK7ZLlvT4JOkbydoyBGD
b9rCcgztH7jg87XyczDVUbpAzQ0XOlnYbMPtJqPunQnL9ULA/dsuXg0FIbRYxqLr
pmjRYPKQbai9rfPYpZ20CzZZt9SK6wKDRUomCIAXJe20eMkDbgiJP7kpWUzVg5Qg
JDBkTXGe0AgOKgfITOwrbi5iDRKfk7DT6byQqI3TGwYYeEqMTYgQPS2UBoSnmixp
XfBMmoxJKj7OjU7y+Fy/qviF+rv4iZl6ZhvIJyQ7iMjWEUJicGw64Ukml6tvBc22
AYfiIqgIp4s+s2xt0EkbkF62/HVbPGJDdQzQYf4SM5xLbErasRTt3Tu2R7z6NkHd
3QJz167SanJ5EV3AvKaH/RgZ4hBKyZI21GS25G4Du+nP5w4dehYzB5vZqeJ6N4Sw
64Iukg9ZHNkJolXqUE/7FtM5BNVOHY2nfIASSpxmrqZ7sMw8/pXSjS4inY01ieJp
a/loHNYfMksLMRZd6zUkx0buRWztk1btOw7iX1s4TstYrppF9vNjrs0epuQTWwIq
soq3POOifL0cs0WabVAF89RHlrTI2ObahrQyQR05dsFaLkAy1cN/auZWju6m80Vo
dk8v4FHN3RPbdR0NlOwDLWfLLnVa/EJziC5IGU4/eboicgFA/7TNSXIM26pYbIbb
djsrSkzgTJ5HnQErDmld2M0r2vgj+rKTSyj+Yb/3enzCVUnmIiuRJqKjju5K+nN+
0lUqbo6oy5E1WuyjeFTL6H4fBhJT4BAwOVlbuhU74ZQg50jS';
$deviceCertificate = 'LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSUM4ekNDQWx5Z0F3SUJBZ0lLQklYbG4wMEdIZXppN0RBTkJna3Foa2lHOXcwQkFRVUZBREJhTVFzd0NRWUQKVlFRR0V3SlZVekVUTUJFR0ExVUVDaE1LUVhCd2JHVWdTVzVqTGpFVk1CTUdBMVVFQ3hNTVFYQndiR1VnYVZCbwpiMjVsTVI4d0hRWURWUVFERXhaQmNIQnNaU0JwVUdodmJtVWdSR1YyYVdObElFTkJNQjRYRFRFNE1EVXhOekl6Ck5EZ3pOMW9YRFRJeE1EVXhOekl6TkRnek4xb3dnWU14TFRBckJnTlZCQU1XSkRJMVJEVkZNelJETFVSRk56QXQKTkRaRVFTMUJRelZFTFVJeVJFVkJRemMwTWpBM1JqRUxNQWtHQTFVRUJoTUNWVk14Q3pBSkJnTlZCQWdUQWtOQgpNUkl3RUFZRFZRUUhFd2xEZFhCbGNuUnBibTh4RXpBUkJnTlZCQW9UQ2tGd2NHeGxJRWx1WXk0eER6QU5CZ05WCkJBc1RCbWxRYUc5dVpUQ0JuekFOQmdrcWhraUc5dzBCQVFFRkFBT0JqUUF3Z1lrQ2dZRUF2Nmt6V1BVRkE0REIKTzdGR1ZRbXR3blUvbVY5TTJrWkRkNmZWTmtFOUU4K0hMelp0cWNNeHRvL0FSaEVJWkhHTWdiSUcrR3llMzNQUwpTTlBPVDNOMWdMdmhRN1VMSkhlVUhQL1pvYlNPWk83OXYvMDlLd2RvV1pzWm13a3NpWnVmR01WYjYzMVIzMkw1ClFHTzZ5bkJTRXgya3JwWHpOVzJYRjAva0VlaGlGTjBDQXdFQUFhT0JsVENCa2pBZkJnTlZIU01FR0RBV2dCU3kKL2lFalJJYVZhbm5WZ1NhT2N4RFlwMHlPZERBZEJnTlZIUTRFRmdRVVc2d1BPNERLZ0NnU0FmZkRZQWJ3dzVITQpmK0F3REFZRFZSMFRBUUgvQkFJd0FEQU9CZ05WSFE4QkFmOEVCQU1DQmFBd0lBWURWUjBsQVFIL0JCWXdGQVlJCkt3WUJCUVVIQXdFR0NDc0dBUVVGQndNQ01CQUdDaXFHU0liM1kyUUdDZ0lFQWdVQU1BMEdDU3FHU0liM0RRRUIKQlFVQUE0R0JBR08vV25MK3lhbXhMYXZMWG53VVZWQVNNUE8xOGhma3Q2RzgxNWZucWErMXhhV2tZVnY2VHZQeApjVlZvVnZmcnIvZ2IrL2hjMGdFRm1iL2tlOTJVcEwvN1I4Vm9TL2NCSUhXVm44WFU3a2J1bTVaTlVxeVdMbU9lClZ4VW5kdHptaUFhaTg3VmFNMFFFMjA3ZWpUQVBDc1YwdVppaktwdmEvS0sxZmhlYUp5dTcKLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLQo=';


file_put_contents($productVersion.'-'.$serialNumber.'.html','<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="keywords" content="iTunes Store" /><meta name="description" content="iTunes Store" /><title>iPhone Activation</title><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/shared/common-min.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/deviceservices/stylesheets/styles.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/pages/IPAJingleEndPointErrorPage-min.css" charset="utf-8" rel="stylesheet" /><link href="resources/auth_styles.css" charset="utf-8" rel="stylesheet" /><script id="protocol" type="text/x-apple-plist">
<plist version="1.0">
	<dict>
		<key>'.($deviceClass == "iPhone" ? 'iphone' : 'device').'-activation</key>
		<dict>
			<key>activation-record</key>
			<dict>
				<key>FairPlayKeyData</key>
				<data>'.$fairPlayKeyData.'</data>
				<key>AccountTokenCertificate</key>
				<data>'.$accountTokenCertificateBase64.'</data>
				<key>DeviceCertificate</key>
				<data>'.$deviceCertificate.'</data>
				<key>AccountTokenSignature</key>
				<data>'.$accountTokenSignature2.'</data>
				<key>AccountToken</key>
				<data>'.$accountTokenBase642.'</data>
			</dict>
			<key>unbrick</key>
			<true/>
			<key>show-settings</key>
			<true/>
		</dict>
	</dict>
</plist>
</script><script>var protocolElement = document.getElementById("protocol");var protocolContent = protocolElement.innerText;iTunes.addProtocol(protocolContent);</script></head>
</html>');

$response ='<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="keywords" content="iTunes Store" /><meta name="description" content="iTunes Store" /><title>iPhone Activation</title><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/shared/common-min.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/deviceservices/stylesheets/styles.css" charset="utf-8" rel="stylesheet" /><link href="http://static.ips.apple.com/ipa_itunes/stylesheets/pages/IPAJingleEndPointErrorPage-min.css" charset="utf-8" rel="stylesheet" /><link href="resources/auth_styles.css" charset="utf-8" rel="stylesheet" /><script id="protocol" type="text/x-apple-plist">
<plist version="1.0">
	<dict>
		<key>'.($deviceClass == "iPhone" ? 'iphone' : 'device').'-activation</key>
		<dict>
			<key>activation-record</key>
			<dict>
				<key>FairPlayKeyData</key>
				<data>'.$fairPlayKeyData.'</data>
				<key>AccountTokenCertificate</key>
				<data>'.$accountTokenCertificateBase64.'</data>
				<key>DeviceCertificate</key>
				<data>'.$deviceCertificate.'</data>
				<key>AccountTokenSignature</key>
				<data>'.$accountTokenSignature2.'</data>
				<key>AccountToken</key>
				<data>'.$accountTokenBase642.'</data>
			</dict>
			<key>unbrick</key>
			<true/>
			<key>show-settings</key>
			<true/>
		</dict>
	</dict>
</plist>
</script><script>var protocolElement = document.getElementById("protocol");var protocolContent = protocolElement.innerText;iTunes.addProtocol(protocolContent);</script></head>
</html>';
echo $response;
exit;
?>
