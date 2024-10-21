# mod-magento_2

## Payfast module v2.5.1 for Magento v2.4.7

This is the Payfast module for Magento 2. Please feel free
to [contact the Payfast support team](https://payfast.io/contact/) should you require any assistance.

## Installation

1. Setup **ZAR** on your Magento site.
   In the admin panel navigate to **Stores**, and add **ZAR** under Currency Symbols and Rates.
2. Copy the Payfast app folder to your root Magento folder.
   This will not override any files on your system.
3. You will now need to run the following commands in the given order:

```
composer require payfast/payfast-common:v1.0.2
php ./bin/magento module:enable Payfast_Payfast
php ./bin/magento setup:di:compile
php ./bin/magento setup:static-content:deploy
php ./bin/magento cache:clean
```

4. Log into the admin panel and navigate to **Stores** -> **Configuration** -> **Sales** -> **Payment Method** and click
   on **Payfast**.
5. Enable the module and complete the Payfast settings as required.
6. Click **Save Config**.

Please [click here](https://payfast.io/integration/plugins/magento/) for more information concerning this
module.

## Collaboration

Please submit pull requests with any tweaks, features or fixes you would like to share.
