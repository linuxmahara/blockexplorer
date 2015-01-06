<?php

if(!Cache::_mkdir(CACHE_DIR)){
	throw new CacheError("can't create CACHE_DIR=" . CACHE_DIR);
}
?>