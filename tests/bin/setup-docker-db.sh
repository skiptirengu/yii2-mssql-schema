#!/usr/bin/env bash

docker pull microsoft/mssql-server-linux:latest

docker run -d \
        --name "yii2-mssql-schema" \
        -p 1433:1433 \
        -e "ACCEPT_EULA=Y" \
        -e "SA_PASSWORD=Admin1234!" \
        microsoft/mssql-server-linux:latest \
        && sleep 15

docker exec yii2-mssql-schema \
        /opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P "Admin1234!" -Q "CREATE DATABASE testdb"