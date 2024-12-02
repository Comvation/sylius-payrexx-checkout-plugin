# Comvation Sylius Payrexx Checkout Plugin

The Sylius Payrexx Checkout plugin gives you an integration of the "Payrexx payment platform" (https://www.payrexx.com/)
into Sylius by using Payrexx's Gateway mechanism.

## Install Dependencies

```bash
ddev composer install
```

## Add the Webhook Route and Controller

```
# config/routes/payrexx.yaml
comvation_sylius_payrexx_checkout_plugin_webhook:
  resource: '@ComvationSyliusPayrexxCheckoutPlugin/config/routes.yaml'
```

```
# config/services.yaml
services:
  # ...
  Comvation\SyliusPayrexxCheckoutPlugin\Controller\NotifyController:
    public: true # Required
    alias: comvation_sylius_payrexx_checkout.notifycontroller
```

## Local Setup for Development and Testing Payrexx integration

Make sure the Payrexx account is set up for test mode!

If not present already, create a "Payrexx Payment" method in Sylius:

https://<hostname>.ddev.site/admin/payment-methods/

Configure the proper instance, key, and API domain (e.g., payrexx.com).

Open the local shop app in your browser.

Place your order, use any of the Payrexx test credit card numbers; see

https://support.payrexx.com/de/support/solutions/articles/11000078221

## Quickstart Installation

TODO: The following steps are not tested yet and need review/setup.

From the plugin root directory, run the following commands:

```bash
$ (cd tests/Application && yarn install)
$ (cd tests/Application && yarn build)
$ (cd tests/Application && APP_ENV=test bin/console assets:install public)

$ (cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
$ (cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
```

To be able to setup a plugin's database, remember to configure you database credentials in `tests/Application/.env`
and `tests/Application/.env.test`.

## Running Tests

Mind that these are intended to run in

```
vendor/comvation/sylius-payrexx-checkout-plugin
```

if you are working in a project which includes this plugin.

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
