# Comvation Sylius Payrexx Checkout Plugin

The Sylius Payrexx Checkout plugin gives you an integration of the "Payrexx payment platform" (https://www.payrexx.com/)
into Sylius by using Payrexx's Gateway mechanism.

## Quickstart Installation

TODO: The following steps are not tested yet and need review/setup.

1. From the plugin root directory, run the following commands:

    ```bash
    $ (cd tests/Application && yarn install)
    $ (cd tests/Application && yarn build)
    $ (cd tests/Application && APP_ENV=test bin/console assets:install public)
    
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
    ```

To be able to setup a plugin's database, remember to configure you database credentials in `tests/Application/.env`
and `tests/Application/.env.test`.

## Local Setup for Development and Testing Payrexx integration

Set up localtunnel according to: https://localtunnel.me/

Note:  Currently redirects to https://theboroer.github.io/localtunnel-www/

Run localtunnel locally

    lt -p 80 -s mflyshop

Use an alternative server, if the default loca.lt is down

    lt -h "http://serverless.social" -p 80 -s mflyshop

Copy the obtained domain name, e.g.

    your url is: https://mflyshop.loca.lt
    your url is: https://mflyshop.serverless.social

Configure the Payrexx account's webhook URL, e.g.

     https://mflyshop.loca.lt/payment/payrexx/webhook
     https://mflyshop.serverless.social/payment/payrexx/webhook

Make sure the Payrexx account is set up for test mode!

If not present already, create a "Payrexx Payment" method

    http://localhost/admin/payment-methods/

Configure the proper instance, key, and API domain (e.g., payrexx.com).

Open the shop in your browser using the domain name, e.g.

     https://mflyshop.loca.lt/
     https://mflyshop.serverless.social/

Place your order, use any of the Payrexx test credit card numbers; see

    https://support.payrexx.com/de/support/solutions/articles/11000078221

## Usage

### Running plugin tests

- PHPSpec

  ```bash
  vendor/bin/phpspec run
  ```

- Behat (non-JS scenarios)

  ```bash
  vendor/bin/behat --strict --tags="~@javascript"
  ```

- Behat (JS scenarios)

    1. [Install Symfony CLI command](https://symfony.com/download).

    2. Start Headless Chrome:

    ```bash
    google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
    ```

    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:

    ```bash
    symfony server:ca:install
    APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
    ```

    4. Run Behat:

    ```bash
    vendor/bin/behat --strict --tags="@javascript"
    ```

- Static Analysis

    - Psalm

      ```bash
      vendor/bin/psalm
      ```

    - PHPStan

      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```

- Coding Standard

  ```bash
  vendor/bin/ecs check src
  ```

### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=test bin/console server:run -d public)
    ```

- Using `dev` environment:

    ```bash
    (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
    ```
