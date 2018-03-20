 <?php

 
/*

Copyright (c) 2017 Angry Eggplant (https://github.com/colental/ae)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

    ,adPPYYba, 8b,dPPYba,   ,adPPYb,d8 88,dPPYba,   aa       aa
    ""     `Y8 88P'   `"8a a8"    `Y88 88P'   `"8a  88       88
    ,adPPPPP88 88       88 8b       88 88           8b       88
    88,    ,88 88       88 "8a,   ,d88 88           "8a,   ,d88
    `"8bbdP"Y8 88       88  `"YbbdP"Y8 88            `"YbbdP"Y8
                            aa,    ,88               aa,    ,88
                             "Y8bbdP"                 "Y8bbdP'

                                                    88                          ,d
                                                    88                          88
     ,adPPYba,  ,adPPYb,d8  ,adPPYb,d8 8b,dPPYba,   88 ,adPPYYba, 8b,dPPYba,    88
    a8P     88 a8"    `Y88 a8"    `Y88 88P'    "8a  88 ""     `Y8 88P'   `"8a MM88MMM
    8PP8888888 8b       88 8b       88 88       d8  88 ,adPPPPP88 88       88   88
    "8b,   ,aa "8a,   ,d88 "8a,   ,d88 88b,   ,a8"  88 88,    ,88 88       88   88
     `"Ybbd8"'  `"YbbdP"Y8  `"YbbdP"Y8 88`YbbdP"'   88 `"8bbdP"Y8 88       88   88,
                aa,    ,88  aa,    ,88 88                                       "Y888
                 "Y8bbdP"    "Y8bbdP"  88


*/


function obfuscate_key($data) {
	$prime = array();
	$block = dechex(rand(0,15)) . dechex(rand(0,15));
	for ($i=2; strlen($data)>0; $i++) {
		$is_mul = false;
		foreach ($prime as $p) {
			if ($i % $p === 0) {
				$is_mul = true;
				$block .= dechex(rand(0,15));
				break;
			}
		}
		if ($is_mul === false) {
			array_push($prime, $i);
			$block .= $data[0];
			$data = substr($data, 1);
		}
	}
	if ((strlen($block) % 2 === 0) === false) {
		$block .= dechex(rand(0,15));
	}
	return base64_encode($block);
}

function deobfuscate_key($data) {
	$prime = array();
	$data = base64_decode($data);
	$hex = '';
	for ($i=2; $i<=strlen($data); $i++) {
		$is_mul = false;
		foreach ($prime as $p) {
			if ($i % $p === 0) {
				$is_mul = true;
				break;
			}
		}
		if ($is_mul === false) {
			array_push($prime, $i);
		}
	}
	foreach ($prime as $p) {
		$hex .= $data[$p];
	}
	if ((strlen($hex) % 2 === 0) === false)  {
		$hex .= hexdec(rand(0,15));
	}
	return $hex;
}

function diffiehellman($public_b) {
	$generator = gmp_init('0x2');
	$modulus   = gmp_init('0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF');
	$private_a = gmp_init('0x' . bin2hex(openssl_random_pseudo_bytes(32)));
	$public_a  = bcpowmod($generator, $private_a, $modulus);
	echo $public_a;
	$public_b  = gmp_init($public_b);
	$session   = gmp_init(bcpowmod($public_b, $private_a, $modulus));
//	echo "DB Private:\n" . $private_a . "\nDB Public:\n" . $public_a . "\nServer Public:\n" . $public_b . "\nShared Secret:\n" . $session;
	return hash('sha256', $session, false);
}


try {
	extract($_POST);
	if (!isset($id)) {
		die("Error: unauthorized request");
	}
	$server		= '0000000000000000000000000000000000000000000000000000000000000000';
	$session_id	= ($id !== $server) ? hash('sha256', $id . $_SERVER['REQUEST_TIME'], false): $id;
	echo $session_id;
	$connection	= mysqli_connect("server227.web-hosting.com","snappwfm_gmail", "8mtV2tatEf7", "snappwfm_gmail");
	if ($connection === false) {
		die("Error: connection failed: " . mysqli_connect_error());
	}
	if ((isset($public_key)) && ($id == $server)) {
		$session_key = diffiehellman($public_key);
	}		
	if ($exists	 = mysqli_query($connection, "SELECT * FROM sessions WHERE client='".$id."'")) {
		$session = 0;
		if (mysqli_num_rows($exists) > 0) {
			while ($row = mysqli_fetch_assoc($exists)) {
				$n = (int) $row['session'];
				if ($n > $session) {
					$session = (int) $row['session'];
				}
			}
			$session = $session + 1;
			if ($id == $server) {
				mysqli_query($connection, "UPDATE sessions SET session=".$session.", session_key='".$session_key."' WHERE client='".$id."'");
			} else {
				mysqli_query($connection, "UPDATE sessions SET id='".$session_id."', session=".$session." WHERE client='".$id."' AND session=0");
			}
		} else {
			$session = $session + 1;
			if ($id == $server) {
				mysqli_query($connection, "INSERT INTO sessions(id, client, session, session_key) VALUES ('".$session_id."', '".$id."', ".$session.", '".$session_key."')");
			} else {
				mysqli_query($connection, "INSERT INTO sessions(id, client, session) VALUES ('".$session_id."', '".$id."', ".$session.")");
			}
		}
	} 
} catch (Exception $e) {
	file_put_contents('error_log', $e);
}

?>
