Setup
- > composer require --dev symfony/test-pack
- add a test database declaration to .env.test
- run
- > php bin/console --env=test doctrine:database:create
- > php bin/console --env=test doctrine:schema:create
- > chown console_user:web_user var/data_test.db   (eg john:www-data)
- > chmod 664 var/data_test.db

Using setup and teardown methods to manage transactions rather than another package.
Using a trait to manage session state.