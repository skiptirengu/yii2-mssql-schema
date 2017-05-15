Running tests
-------------

You can run the standalone tests with
```bash
vendor/bin/phpunit --exclude=integration
```

or run all the tests, including the integration tests (the latter requires an active database connection).
```bash
vendor/bin/phpunit
```

Integration tests
-----------------

You can change the default database settings by creating a file named "local.env" on the "tests" folder, eg:
```
CON_STRING=dblib:host=localhost;database=testdb
CON_DBUSER=sa
CON_PASSWD=Admin1234!
```

If you don't have a local database "tests/bin/setup-docker-db.sh" will setup a docker container with one for you.
```
sh tests/bin/setup-docker-db.sh
```

TODO
----

+ Integration tests running on CI
