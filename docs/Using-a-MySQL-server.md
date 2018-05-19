# SQL Server Configuration

By default, RompЯ uses SQLite for its database. This option requires no setup and will work for most people.

If you would like to use a MySQL server instead - perhaps because you're already running one - then you can do so.

## Create mysql database

You must create the RompЯ database before you start. You will need your mysql root password.

    mysql -uroot -p
    CREATE DATABASE romprdb CHARACTER SET utf8 COLLATE utf8_unicode_ci;
    USE romprdb;
    GRANT ALL ON romprdb.* TO rompr@localhost IDENTIFIED BY 'romprdbpass';
    FLUSH PRIVILEGES;
    quit;

Those commands set up the RompЯ database using a default username and password. Note that any usernames and passwords you put in will be stored by RompЯ in plain text, so don't use anything important.

## Tweak MySQL

We also want to set some configuration values for mysql to increase performance. Create another file somewhere, called rompr-tweaks.cnf (note it MUST end in .cnf or it will be ignored). Put the following in it

    [mysqld]
    query_cache_limit       = 16M
    query_cache_size        = 64M
    innodb_buffer_pool_size = 64M
    innodb_flush_log_at_trx_commit = 0

And now link this file so mysql can find it

    sudo ln -s /PATH/TO/ROMPR-TWEAKS /etc/mysql/conf.d/rompr-tweaks.cnf
    sudo ln -s /PATH/TO/ROMPR-TWEAKS /etc/mysql/mysql.conf.d/rompr-tweaks.cnf

Note that the default MySQL settings I've encountered on several distributions make MySQL significantly slower than SQLite for RompЯ, unless you have an extremely large music collection (hundreds of thousands of tracks). Setting these parameters restores the balance. Somewhat.

## Configure RompЯ

Point your web browser at

    http://your.rompr.installation/?setup
    
and choose the Full Database option, entering the usernames and passwords as appropriate.

![](images/collectionsetup.png)
