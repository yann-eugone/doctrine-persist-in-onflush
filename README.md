Install application
```bash
composer install
bin/console doctrine:database:create
bin/console doctrine:schema:create
bin/console fixtures
```

Test listener with persist event strategy: `SQL syntax error`.
```bash
bin/console test persist
```

Test listener with record event strategy: `No event recorded`.
```bash
bin/console test record
```
