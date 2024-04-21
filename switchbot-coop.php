<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">	
<link rel="icon" type="image/x-icon" href="chicken.ico">
<style>
div#toggle, div#laststatus {
	padding-bottom: 20px;
}
 .toggle-control {
	 display: block;
	 position: relative;
	 padding-left: 100px;
	 margin-bottom: 12px;
	 cursor: pointer;
	 font-size: 22px;
	 user-select: none;
}
 .toggle-control input {
	 position: absolute;
	 opacity: 0;
	 cursor: pointer;
	 height: 0;
	 width: 0;
}
 .toggle-control input:checked ~ .control {
	 background-color: dodgerblue;
}
 .toggle-control input:checked ~ .control:after {
	 left: 55px;
}
 .toggle-control .control {
	 position: absolute;
	 top: 0;
	 left: 0;
	 height: 50px;
	 width: 100px;
	 border-radius: 25px;
	 background-color: darkgray;
	 transition: background-color 0.15s ease-in;
}
 .toggle-control .control:after {
	 content: "";
	 position: absolute;
	 left: 5px;
	 top: 5px;
	 width: 40px;
	 height: 40px;
	 border-radius: 25px;
	 background: white;
	 transition: left 0.15s ease-in;
}
/* Center the control */
body {
 display: flex;
 justify-content: center;
 align-items: center;
 min-height: 100vh;
 background-color: #222229;
 color: white;
}

.logs::after{
    content: "\a";
    white-space: pre;
}


.logs {
    height: 50px;
    width: 100%;
    grid-area: inner-div;
}
 

</style>
</head>
<body>
<?php

if($_GET['click'] == 'true'){
	//find your token here
	//https://support.switch-bot.com/hc/en-us/articles/12822710195351-How-to-obtain-a-Token
	$token = 'XXXXXXX';
	$secret = 'XXXXXXXX';
	$nonce = guidv4();
	$t = time() * 1000;
	$data = ($token . $t . $nonce);
	$sign = hash_hmac('sha256', $data, $secret,true);
	$sign = strtoupper(base64_encode($sign));

	$url = "https://api.switch-bot.com/v1.1/devices";

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
	    "Content-Type:application/json",
	    "Authorization:" . $token,
	    "sign:" . $sign,
	    "nonce:" . $nonce,
	    "t:" . $t
	);

	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$response = curl_exec($curl);
	curl_close($curl);

	$obj = json_decode($response, true);

	// this may have to change if you have more than one push bot. When writing this I only had one device.
	$deviceId = $obj["body"]["deviceList"][0]["deviceId"];

	$nonce = guidv4();
	$t = time() * 1000;
	$data = ($token . $t . $nonce);
	$sign = hash_hmac('sha256', $data, $secret,true);
	$sign = strtoupper(base64_encode($sign));

	$url = "https://api.switch-bot.com/v1.1/devices/".$deviceId."/commands";

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
	    "Content-Type:application/json",
	    "Authorization:" . $token,
	    "sign:" . $sign,
	    "nonce:" . $nonce,
	    "t:" . $t
	);

	$data = array(
	    'commandType' => 'command',
	    'command' => 'press'
	);

	$payload = json_encode($data);
	//echo $payload;

	curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$response = curl_exec($curl);
	curl_close($curl);

	//echo $response;

	date_default_timezone_set("America/Phoenix");
	$txt = "\n".$_GET['status']."|".date("Y-m-d h:i:sa", $d)."";

	$fp = fopen('log.txt', 'a');//opens file in append mode  
	fwrite($fp,$txt);  
	fclose($fp);

}

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$fp = fopen("log.txt", "r");
fseek($fp, -1, SEEK_END); 
$pos = ftell($fp);
$LastLine = "";
// Loop backword util "\n" is found.
while((($C = fgetc($fp)) != "\n") && ($pos > 0)) {
    $LastLine = $C.$LastLine;
    fseek($fp, $pos--);
}
fclose($fp);

$pieces = explode("|", $LastLine);

echo "<div class='container'><div id='laststatus'class='logs'>Last Status: " . $pieces[0] . " at " . $pieces[1] . "</div>";
?>


<div id="toggle" class="logs">
	<label class="toggle-control">
	  <input id="checkbox" type="checkbox" onchange="changeStatus()" checked="checked">
	  <span class="control"></span>
	</label>
</div>
<div id="status" class="logs">Status Log</div>
<?php 

$file = file("log.txt");
$file = array_reverse($file);

for($i =0;$i<count($file);$i++){
   echo "<div class='logs'>" . ($file[$i]) . "</div>";
}
echo '</div>';

?>
<script>
	<?php
		if($pieces[0] == "Closed"){
	?>
			document.getElementById("checkbox").checked = false;
	<?php
		}
	?>

	function changeStatus() {
	    var decider = document.getElementById('checkbox');
	    if(decider.checked){
	       document.getElementById("checkbox").checked = true;
	       document.location.href='?status=Opened&click=true';
	    } else {
	      document.getElementById("checkbox").checked = false;
	      document.location.href='?status=Closed&click=true';
	    }
	}

</script>

</body>
</html>