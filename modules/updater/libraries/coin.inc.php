<?php
function coin_validate_block(&$PAYMENT_GATEWAY, $block_hash, $conformations = 1000){
	$isGood = false;
	
	if($conformations <= 0){throw new Exception("Conformations must be greater than 0");}

	//Load Block from chain using block hash, if it's not found an error will be thrown by the payment gateway.
	try{
		//Load Block information using the solution
		$block_info = coin_get_blockinfo($PAYMENT_GATEWAY, $block_hash);
		if($block_info['confirmations'] > $conformations){$isGood = true;}
	}
	catch(Exception $e){
		$isGood = false;
	}

	return $isGood;
}

/* Throws Exception on RPC error */
function coin_get_blockinfo(&$PAYMENT_GATEWAY, $block_hash){
	try{
		$block_info = $PAYMENT_GATEWAY->getblock($block_hash);
	}
	catch(Exception $e){
		throw new Exception("Could not load block. " . $e->getMessage(), $e->getCode());
	}

	//Some coins don't have a 'conformations' field for whatever reason, lets add that in
	//using the coinbase transaction's conformation fields
	if(!array_key_exists('confirmations', $block_info)){
		echo "No conformations field, attempting to get from coinbase\n";
		try{
			$tx_info = $PAYMENT_GATEWAY->gettransaction($block_info['tx'][0]);
		}
		catch(Exception $e){
			throw new Exception("Could not load coinbase transaction. " . $e->getMessage(), $e->getCode());
		}
		$block_info['confirmations'] = $tx_info['confirmations'];
	}

	return $block_info;
}

function coin_get_full_block_info(&$PAYMENT_GATEWAY, $block_hash){
	$block_info = false;

	try{
		$block_info = coin_get_blockinfo($PAYMENT_GATEWAY, $block_hash);
		$tx_info = $PAYMENT_GATEWAY->gettransaction($block_info['tx'][0]);
		$coinbase_details = $tx_info['details'][0];
		
		//Check that the transacion ID's match
		if($tx_info['txid'] != $block_info['tx'][0]){
			throw new Exception("TX ID Not Match");
		}

		//Check that the block hash matches
		if($tx_info['blockhash'] != $block_hash){
			throw new Exception("Block Hash not Match");
		}

		//Check that it's a generated
		if ($coinbase_details['category'] != "generate"){
			throw new Exception("Not a generated Block transaction");
		}

		$block_info['reward'] = $coinbase_details['amount'];

	}
	catch(Exception $e){
		$block_info = false;
	}

	return $block_info;
}

function coin_validate_transaction(&$PAYMENT_GATEWAY, $txid, $conformations = 10){
	$isGood = false;

	//Load trasaction data, if it's not found an error will be thrown by the payment gateway
	try{
		$tx_info = $PAYMENT_GATEWAY->gettransaction($txid);
		//Just check the number of conformations (that is sufficient)
		//Verifying amounts, etc... is too much work for now! :-)
		if($tx_info['confirmations'] >= $confirmations){
			$isGood = true;
		}
		else{
			$isGood = false;
		}
	}
	catch(Exception $e){
		$isGood = false;
	}
	
	return $isGood;
}

function coin_getblock_bynumber(&$PAYMENT_GATEWAY, $num){
	do{
		set_time_limit(2);

		$block_hash = $PAYMENT_GATEWAY->getblockhash((int)$num);
		$data = coin_get_blockinfo($PAYMENT_GATEWAY, $block_hash);

		set_time_limit(0);
		if(!isset($data) || is_null($data)){
			echo "Error: retrying...\r\n" . var_dump($data);
			sleep(5);
		}
	}
	while(!isset($data) || is_null($data));

	return $data;
}

?>