# mod-magento_2

PayFast Magento Module v1.1.0 for Magento v2.0 - 2.1.3
-----------------------------------------------------------------------------
Copyright (c) 2016 PayFast (Pty) Ltd

LICENSE:
 
This payment module is free software; you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published
by the Free Software Foundation; either version 3 of the License, or (at
your option) any later version.

This payment module is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
License for more details.

Please see http://www.opensource.org/licenses/ for a copy of the GNU Lesser
General Public License.


******************************************************************************
*                                                                            *
*    Please see the URL below for all information concerning this module:    *
*                                                                            *
*             https://www.payfast.co.za/shopping-carts/magento/              *
*                                                                            *
******************************************************************************

In order to use PayFast with Magento 2.*.3 you will need a working Magento 2.*.3 installation. To install PayFast follow the below instructions:

1. Setup ZAR on your Magento site.
    In the admin panel navigate to 'Stores', and add ZAR under currency Symbols and Rates.
2. Copy the PayFast app folder to your root Magento folder.
    This will not override any files on your system.
3. You will now need to run the following commands in the given order:
    php ./bin/magento module:enable PayFast_Payfast
    php ./bin/magento setup:di:compile
    php ./bin/magento setup:static-content:deploy 
    php ./bin/magento cache:clean
4. Log into the admin panel and navigate to 'Stores'>'Configuration'>'Sales'>'Payment Method' and click on Payfast
5. Enable the module, as well as debugging. To test in sandbox insert 'test' in the 'server' field and use the following credentials:
    Merchant ID: 10000100
    Merchant Key: 46f0cd694581a
   Leave the passphrase blank and setup the other options as required.
6. Click 'Save Config', you are now ready to test in sandbox, click 'Save Config'.
7. Once you are ready to go live, insert 'live' into the 'server' field and input your PayFast credentials. Set debug log to 'No', and the other options as required.
8. Click 'Save Config', you are now ready to process live transactions via PayFast.
