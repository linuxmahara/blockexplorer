Complete Rewrite
================

This is a complete rewrite of the PHP block explorer/crawler that powers blockexplorer.com.  There are several changes in progress and completed:

- Switch to MySQL.
- Refactor to use the Enchilada 3.0 Libraries & Application Framework.
- No longer requires a patched 'bitcoind'.
- No requirement for txindex=1
- Written in pure PHP with some shell scripts.

### Installing

At the moment there is no installer, configuration and setup will need to be done manually:

1. Create *conf/coin.conf.php* and *conf/db.conf.php* using samples.
2. Create a database and import schema.sql

### Testing

Currently, the backend is complete.  The front end is non-existant at this point.  The only thing there is to test is the 'update.php' script to begin indexing the blockchain into the MySQL database.

	php update.php

I recomend you enable MySQL compression to reduce the size of the data.  Since at the moment the information is being 'duplicated' due to the fact that it's also keeping a 'raw' version of the block data.  This feature may be removed or set as optional in future versions.

Compression must be enabled before-hand in order to realize the space savings.  See:
http://dev.mysql.com/doc/refman/5.6/en/innodb-compression-usage.html

FOr testing and development purposes there is also a 'clear.php' script to truncate the current indexed block chain information in the database.

	php clear.php

This is still a work in progress.  The front end and API features are currently being worked on.