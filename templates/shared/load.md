# Commands to run after building the Docker container
To bring the application up after building your Docker container, you can use the following commands:
```bash
./vendor/bin/mtdocker symfony d:m:m
./vendor/bin/mtdocker symfony d:m:m --env=test
./vendor/bin/mtdocker symfony d:f:l
./vendor/bin/mtdocker symfony importmap:install
```

`importmap:install` downloads the vendor assets into `assets/vendor`, which is git-ignored and therefore missing from a fresh clone or a new worktree. Without it, every page calling `importmap()` answers 500: functional tests then fail in bulk, and may exhaust the memory limit before the end of the suite. The reason appears only inside the HTTP response body, never in the test summary.

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
