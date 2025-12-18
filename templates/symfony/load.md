# Commands to Load Database after building Docker Container
To load the database after building your Docker container, you can use the following commands:
```bash
./vendor/bin/mtdocker symfony d:d:c --env=test
./vendor/bin/mtdocker symfony d:m:m
./vendor/bin/mtdocker symfony d:m:m --env=test
./vendor/bin/mtdocker symfony d:f:l
```
