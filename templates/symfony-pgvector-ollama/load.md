# Commands to Load Database after building Docker Container
To load the database after building your Docker container, you can use the following commands:
```bash
mt s d:d:c --env=test
mt s d:m:m
mt s d:m:m --env=test
mt s d:f:l
```

## Start worker
To start the worker, use the command:
```bash
mt s mes:con -vv
```

## Test Email Sending
To test email sending functionality, use the command:
```bash
mt s mail:test someone@example.com
```
