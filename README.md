# Schema class for Yii2 applications using SQL Server

This extension provides an optimized Schema class for Yii2 applications using SQL Server databases, 
and also fixes several bugs of the current core mssql implementation.

It achieves a better performance by using the stored procedure [sp_help](https://docs.microsoft.com/en-us/sql/relational-databases/system-stored-procedures/sp-help-transact-sql) instead of querying the system catalog for each table you're acessing. This cuts down the number of queries Yii executes to get information about your table from three to only **one**!

Requirements
------------

+ PHP >= 5.6
+ SQL Server >= 2008
+ PDO driver (pdo_dblib, pdo_sqlsrv, etc)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```bash
composer require "skiptirengu/yii2-mssql-schema:*"
```

or add 
```
"skiptirengu/yii2-mssql-schema": "*"
```

to the `require` section of your `composer.json` file.

Usage
-----

To use this extension, just change the database configuration of your application to match following
```php
// ...
'components' => [
    'db' => [
        'class' => 'yii\db\Connection',
        // ...
        'schemaMap' => [
            // if you're using freetds + dblib
            'dblib' => 'skiptirengu\mssql\Schema',
            // older MSSQL driver on MS Windows hosts
            'mssql' => 'skiptirengu\mssql\Schema',
            // new ms driver for SQL Server
            // https://github.com/Microsoft/msphpsql
            'sqlsrv' => 'skiptirengu\mssql\Schema',
        ]
    ]
]
```

and you're set!

License
-------

Licensed under the incredibly [permissive](http://en.wikipedia.org/wiki/Permissive_free_software_licence) [MIT license](http://creativecommons.org/licenses/MIT/)
