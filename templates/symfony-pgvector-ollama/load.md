# Commands to Load Database after building Docker Container
To load the database after building your Docker container, you can use the following commands:
```bash
./vendor/bin/mtdocker symfony d:d:c --env=test
./vendor/bin/mtdocker symfony d:m:m
./vendor/bin/mtdocker symfony d:m:m --env=test
./vendor/bin/mtdocker symfony d:f:l
```

## Download Models (replace mulertech-ollama with your container name)
docker exec mulertech-ollama ollama pull nomic-embed-text
docker exec mulertech-ollama ollama pull gemma3:4b

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

## To reload the database with vector extension
If you need to reload the database and ensure the vector extension is enabled, use the following command:
```bash
./vendor/bin/mtdocker symfony dbal:run-sql "CREATE EXTENSION IF NOT EXISTS vector" --env=test
```
