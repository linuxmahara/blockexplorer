<?php

function logconflict($log){
	$file = fopen($CONFLICT_LOG, "a");
	fwrite($file, $log . "\n");
	fclose($file);
}

function simplifyscript($script){
	$script = preg_replace("/[0-9a-f]+ OP_DROP ?/", "", $script);
	$script = preg_replace("/OP_NOP ?/", "", $script);
	return trim($script);
}

function updater_getrpcblockcount(&$COIN_RPC){
	set_time_limit(2);
	$data = $COIN_RPC->getblockcount();
	set_time_limit(0);

	if(!isset($data) || is_null($data) || !is_int($data)){
		echo "Can't get block count\r\n";
		die();
	}

	return $data;
}

function updater_getdbblockheight(&$DB){
	$result = $DB->query("SELECT MAX(number) AS max FROM blocks");
	return (int)$result->fetch_object()->max;
}

function updater_processblock(&$DB, &$COIN_RPC, $num) {
	$block = coin_getblock_bynumber($COIN_RPC, $num);
	$blockhash = $block['hash'];
	$prevblock = @$block['previousblockhash'];
	$root = $block['merkleroot'];
	$timestamp = $block['time'];
	$bits = '0x' . $block['bits'];
	$nonce = $block['nonce']; //float
	$txcount = count($block['tx']);
	$rawblock = updater_indent(json_encode($block));
	$transactions = $block['tx'];
	$blocksize = $block['size'];

	echo "\nBLOCK\n";
	echo "Num: " . $num . "$\n";
	echo "Hash: " . $blockhash . "$\n";
	echo "Prev: ".$prevblock . "$\n";
	echo "Root: " . $root . "$\n";
	echo "Bits: " . $bits . "$\n";
	echo "Nonce: " . $nonce . "$\n";
	echo "Timestamp: " . $timestamp . "$\n";
	echo "Size: " . $blocksize . "$\n";

	$query = sprintf("SELECT ENCODE(`hash`, 'hex') AS oldhash 
						FROM `blocks`
						WHERE `number` = %s", 
						$num);
	$oldhash_result = $DB->query($query);

	$oldhash_array = $oldhash_result->fetch_assoc();
	$oldhash = $oldhash_array["oldhash"];

	if(!$oldhash) {
		$totalvalue = "0";
		$transactioncount = 0;

		$DB->autocommit(FALSE);

		$query = sprintf("INSERT INTO blocks(`hash`, `prev`, `number`, `root`, `bits`, `nonce`, `raw`, `time`, `size`) 
							VALUES (DECODE('%s', 'hex'), DECODE('%s', 'hex'), %s, DECODE('%s', 'hex'), %s, %s, '%s', FROM_UNIXTIME(%s), %s)",
							$blockhash,
							$prevblock,
							$num,
							$root,
							$bits,
							$nonce,
							$rawblock,
							$timestamp,
							$blocksize);
		$DB->query($query);

		if($num > 0){
			foreach($transactions as $tx){
				$transactioncount++;
				$txvalue = processtransaction($DB, $COIN_RPC, $tx, $blockhash, $num);
				$totalvalue = bcadd($txvalue, $totalvalue, 8);
			}
		}

		$query = sprintf("UPDATE `blocks` SET transactions = %s, totalvalue = %s 
							WHERE `hash` = DECODE('%s', 'hex')",
							$transactioncount,
							$totalvalue,
							$blockhash);
		$DB->query($query);

		echo "Total value: $totalvalue$\n";
		echo "Transactions: $transactioncount$\n";

		$DB->commit();
		return 1;
	}
	else if($oldhash == $blockhash) {
		echo "Already have this block";
		return 2;
	}
	else {
		echo "***Deleting conflicting block***";
		logconflict(date("r") . ": block $num replaced");
		sleep(10);

		$DB->autocommit(FALSE);

		$query = sprintf("DELETE FROM `blocks` WHERE `number` = %s", $num);
		$DB->query($query);

		$totalvalue = "0";
		$transactioncount = 0;

		$query = sprintf("INSERT INTO blocks(`hash`, `prev`, `number`, `root`, `bits`, `nonce`, `raw`, `time`, `size`) 
							VALUES (DECODE('%s', 'hex'), DECODE('%s', 'hex'), %s, DECODE('%s', 'hex'), %s, %s, '%s', FROM_UNIXTIME(%s), %s)",
							$blockhash,
							$prevblock,
							$num,
							$root,
							$bits,
							$nonce,
							$rawblock,
							$timestamp,
							$blocksize);
		$DB->query($query);

		if($num > 0){
			foreach($transactions as $tx){
				$transactioncount++;
				$txvalue = processtransaction($DB, $COIN_RPC, $tx, $blockhash, $num);
				$totalvalue = bcadd($txvalue, $totalvalue, 8);
			}
		}

		$query = sprinf("UPDATE `blocks` SET transactions = %s, totalvalue = %s 
							WHERE `hash` = DECODE('%s', 'hex')",
							$transactioncount,
							$totalvalue,
							$blockhash);
		$DB->query($query);

		echo "Total value: $totalvalue$\n";
		echo "Transactions: $transactioncount$\n";
		$DB->commit();
		return 3;
	}
}

function updateKeys(&$DB, $hash160, $pubkey, $blockhash) {
	$address = hash160ToAddress($hash160);
	$query = sprintf("SELECT pubkey, ENCODE(hash160, 'hex') AS hash160 
						FROM `keys` 
						WHERE hash160 = DECODE('%s', 'hex')",
						$hash160);
	$result_result = $DB->query($query);
	$result = $result_result->fetch_assoc();

	if(!$result && !is_null($pubkey)){
		$query = sprintf("INSERT INTO `keys`
							VALUES (DECODE('%s', 'hex'), '%s', DECODE('%s', 'hex'), DECODE('%s', 'hex'))",
							$hash160,
							$address,
							$pubkey,
							$blockhash);
		$DB->query($query);
	}
	else if(!$result){
		$query = sprintf("INSERT INTO `keys`(hash160, address, firstseen) 
							VALUES (DECODE('%s','hex'), '%s', DECODE('%s','hex'))",
							$hash160,
							$address,
							$blockhash);
		$DB->query($query);
	}
	else if($result && !is_null($pubkey) && is_null($result["pubkey"])){
		if($result["hash160"] != strtolower(hash160($pubkey))){
			sleep(10);
			die("Hashes don't match");
		}
		$query = sprintf("UPDATE `keys` 
						SET pubkey = DECODE('%s','hex') 
						WHERE hash160 = DECODE('%s','hex')",
						$pubkey,
						$hash160);
		$DB->query($query);
	}
}

function processtransaction(&$DB, &$COIN_RPC, $txhash, $blockhash, $blocknum) {
	$tx = $COIN_RPC->getrawtransaction($txhash, 1);
	$txsize = @$tx['size'] ?: 0;
	$rawtx = updater_indent(json_encode($tx));

	$query = sprintf("SELECT `hash` 
						FROM `transactions` 
						WHERE `hash` = DECODE('%s','hex')",
						$txhash);
	$tx_db_count = $DB->query($query);

	if($tx_db_count->num_rows === 0){
		$query = sprintf("INSERT INTO `transactions`(`hash`, `block`, `raw`, `size`) 
							VALUES (DECODE('%s', 'hex'), DECODE('%s', 'hex'), '%s', %s)",
							$txhash,
							$blockhash,
							$rawtx,
							$txsize);
		$DB->query($query);
	}
	else{
		$query = sprintf("SELECT `hash` 
							FROM `transactions` 
							WHERE `hash` = DECODE('%s','hex') 
							AND `block` <> DECODE('%s','hex')",
							$txhash,
							$blockhash);
		$tx_db_count = $DB->query($query);

		if($tx_db_count->num_rows == 1) {
			echo "***Duplicate transaction: adding special record***";
			sleep(30);

			$query = sprintf("INSERT INTO `special` 
								VALUES (DECODE('%s', 'hex'), DECODE('%s', 'hex'), 'Duplicate')",
								$txhash,
								$blockhash);
			$DB->query($query);
			return "0";
		}
		else{
			die("Can't insert tx");
		}
	}

	foreach($tx['vin'] as $input){
		$type = NULL;
		$prev = NULL;
		$previndex = NULL;
		$hash160 = NULL;
		$scriptsig = NULL;
		$index = NULL;
		$value = NULL;

		echo "INPUT\n";

		if(isset($input['coinbase'])){
			$type = "Generation";
			//////////THIS IS WRONG!/////
			$value = bcdiv("50", floor(pow(2, floor($blocknum / 210000))), 8);
			/////////////////////////////
			$scriptsig = $input['coinbase'];
		}
		else {
			$prev = $input['txid'];
			$index = $input['vout'];
			$scriptsig = $input['scriptSig']['asm'];
			$simplescriptsig = simplifyscript($scriptsig);

			echo "Simplescriptsig: ".$simplescriptsig."$\n";

			$query = sprintf("SELECT `value`, `type`, ENCODE(hash160, 'hex') AS hash160 
											FROM `outputs` 
											WHERE `index` = %s 
											AND tx = DECODE('%s', 'hex')",
											$index,
											$prev);
			$prevtx_result = $DB->query($query);
			$prevtx = $prevtx_result->fetch_assoc();

			if(!$prevtx){
				//var_dump(shell_exec("crontab -r"));
				die("Error: Failed getting prev tx...");
			}

			$value = $prevtx["value"];
			$type = $prevtx["type"];
			$hash160 = $prevtx["hash160"];

			if($type == "Address"){
				if(preg_match("/^[0-9a-f]+ [0-9a-f]{66,130}$/", $simplescriptsig)){
					$pubkey = preg_replace("/^[0-9a-f]+ ([0-9a-f]{66,130})$/", "$1", $simplescriptsig);
					$hash160 = strtolower(hash160($pubkey));
					updateKeys($DB, $hash160, $pubkey, $blockhash);
				}
			}

			if(is_null($type)){
				//var_dump(shell_exec("fcrontab -r"));
				die("Error: No input type");
			}
		}

		$query = sprintf("INSERT INTO `inputs` (`tx`, `prev`, `index`, `value`, `scriptsig`, hash160, `type`, `block`) 
							VALUES (DECODE('%s','hex'), DECODE('%s','hex'), %s, %s, '%s', DECODE('%s','hex'), '%s', DECODE('%s', 'hex'))",
							$txhash,
							$prev,
							$index,
							$value,
							$scriptsig,
							$hash160,
							$type,
							$blockhash);
		$DB->query($query);

		echo "Type: " . $type . "$\n";
		echo "Value: " . $value . "$\n";
		echo "Prev: " . $prev . "$\n";
		echo "TxHash: " . $txhash . "$\n";
		echo "Index: " . $index . "$\n";
		echo "ScriptSig: " . $scriptsig . "$\n";
		echo "Hash160: " . $hash160 . "$\n";
	}

	$index = -1;
	$txvalue = "0";

	foreach($tx['vout'] as $output){
		$hash160 = NULL;
		$type = NULL;
		$index++;

		echo "OUTPUT\n";

		$value = $output['value'];
		$txvalue = bcadd($txvalue, $value, 8);
		$scriptpubkey = $output['scriptPubKey']['asm'];
		$simplescriptpk = simplifyscript($scriptpubkey);

		echo "Simplescriptpubkey: ".$simplescriptpk."$\n";

		//To pubkey
		if(preg_match("/^[0-9a-f]{66,130} OP_CHECKSIG$/", $simplescriptpk)){
			$type = "Pubkey";
			$pubkey = preg_replace("/^([0-9a-f]{66,130}) OP_CHECKSIG$/", "$1", $simplescriptpk);
			$hash160 = strtolower(hash160($pubkey));
			updateKeys($DB, $hash160, $pubkey, $blockhash);
		}

		//To BC address
		if(preg_match("/^OP_DUP OP_HASH160 [0-9a-f]{40} OP_EQUALVERIFY OP_CHECKSIG$/", $simplescriptpk)){
			$type = "Address";
			$hash160 = preg_replace("/^OP_DUP OP_HASH160 ([0-9a-f]{40}) OP_EQUALVERIFY OP_CHECKSIG$/", "$1", $simplescriptpk);
			updateKeys($DB, $hash160, NULL, $blockhash);
		}

		if(is_null($type)){
			$type = "Strange";
		}

		$query = sprintf("INSERT INTO `outputs` (`tx`, `index`, `value`, `scriptpubkey`, `hash160`, `type`, `block`) 
							VALUES (DECODE('%s', 'hex'), %s, '%s', '%s', DECODE('%s', 'hex'), '%s', DECODE('%s', 'hex'))",
							$txhash,
							$index,
							$value,
							$scriptpubkey,
							$hash160,
							$type,
							$blockhash);
		$DB->query($query);

		echo "Hash160: " . $hash160 . "$\n";
		echo "Type: " . $type . "$\n";
		echo "Index: " . $index . "$\n";
		echo "Value: " . $value . "$\n";
		echo "Scriptpubkey: " . $scriptpubkey . "$\n";
	}

	$query = sprintf("UPDATE `transactions` 
						SET fee = (SELECT (SELECT SUM(`value`) FROM `inputs` WHERE tx = DECODE('%s', 'hex'))-(SELECT SUM(`value`) FROM `outputs` WHERE tx = DECODE('%s', 'hex'))) 
						WHERE `hash` = DECODE('%s','hex')",
						$txhash,
						$txhash,
						$txhash);
	$DB->query($query);
	return $txvalue;
}

function updater_indent($json) {

    $result    = '';
    $pos       = 0;
    $strLen    = strlen($json);
    $indentStr = '  ';
    $newLine   = "\n";

    for($i = 0; $i <= $strLen; $i++) {
        
        // Grab the next character in the string
        $char = substr($json, $i, 1);
        
        // If this character is the end of an element, 
        // output a new line and indent the next line
        if($char == '}' || $char == ']') {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line
        if ($char == ',' || $char == '{' || $char == '[') {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
    }
	
    return $result;
}

function updater_clear_all(&$DB){
	$queries[] = 'TRUNCATE `blocks`';
	$queries[] = 'TRUNCATE `inputs`';
	$queries[] = 'TRUNCATE `keys`';
	$queries[] = 'TRUNCATE `outputs`';
	$queries[] = 'TRUNCATE `special`';
	$queries[] = 'TRUNCATE `transactions`';

	foreach($queries as $query){
		$DB->query($query);
	}
}

?>