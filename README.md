# Comvation Sylius Payrexx Checkout Plugin

Integrates the Payrexx payment gateway interface into Sylius.

## Add the Dependency

```bash
composer require comvation/sylius-payrexx-checkout-plugin
```

## Install Dependencies

```bash
composer install
```

## Add the Webhook Route and Controller

Extend your Sylius configuration files:
```yaml
# config/routes/payrexx.yaml
comvation_sylius_payrexx_checkout_plugin_webhook:
  resource: '@ComvationSyliusPayrexxCheckoutPlugin/config/routes.yaml'
```
```yaml
# config/services.yaml
services:
  # ...
  Comvation\SyliusPayrexxCheckoutPlugin\Controller\NotifyController:
    public: true # Required
    alias: comvation_sylius_payrexx_checkout.notifycontroller
```

## Configure the Webhook URL at Payrexx

Add or edit a webhook in the Payrexx admin panel:
```
https://&lt;instance&gt;.payrexx.com/cadmin/index.php?cmd=checkout&act=api
```

* Choose a sensible name
* Set the URL to your domain, with the proper path appended:
  ```
  https://<my-domain>/payment/payrexx/webhook
  ```
* Activate "Transaction" events only
* Enable "retry on error"
* Select type "normal (PHP-Post)" and the latest version

## Add a Payment Method

If not present already, create a "Payrexx Payment" method in Sylius:
```
https://<my-domain>/admin/payment-methods/
```
Configure the proper instance, key, and API domain (e.g., payrexx.com).

## Local Setup with DDEV for Development and Testing

**Make sure the Payrexx account is set up for test mode!**

Add an extra domain for the localtunnel in the DDEV config.
Choose a unique host name:
```yaml
# .ddev/config.yaml
# [...]
additional_fqdns:
  - my-payrexx.loca.lt # localtunnel for Payrexx
```
Open the tunnel:
```bash
lt -p 80 -s my-payrexx
> your url is: https://my-payrexx.loca.lt
```
The webhook URL set at Payrexx (see above) must of course match your tunnel:
```
https://my-payrexx.loca.lt/payment/payrexx/webhook
```

## Running Unit Tests

These are intended to run in the plugin folder.
```bash
cd vendor/comvation/sylius-payrexx-checkout-plugin
```
Run the unit tests with
```bash
phpunit
```
