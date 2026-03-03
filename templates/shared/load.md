# Commands to Load Database after building Docker Container
To load the database after building your Docker container, you can use the following commands:
```bash
./vendor/bin/mtdocker symfony d:m:m
./vendor/bin/mtdocker symfony d:m:m --env=test
./vendor/bin/mtdocker symfony d:f:l
```

## Start worker
To start the worker, use the command:
```bash
./vendor/bin/mtdocker symfony mes:con -vv
```

## Test Email Sending
To test email sending functionality, use the command:
```bash
./vendor/bin/mtdocker symfony mail:test someone@example.com
```