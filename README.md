Module for Magento 1.9
=====
#EN

Installation
----
Copy all files to `{site root directory}/app/`
Copy all image to `{site root directory}/skin/{template_name}`
>1. Login to admin interface

>2. Navigate to menu "System" -> "Configuration" 

>3. Open tab "Payment methods"

>4. Choose Fondy or Fondy on Page

>5. Enable module

>N.B.! Available statuses after order completion are Processing, On Hold 


Callback URL : `http://yoursite/Fondy/response`
Callback URL FondyOnPage : `http://yoursite/FondyOnPage/response`
Callback URL FondyBankWire : `http://yoursite/FondyBankWire/response`
-----

Example of "Fondy on Page" settings:

[1]: https://raw.githubusercontent.com/cloudipsp/magento/master/magentof.png
![Скриншот][1]

Example of "Fondy" settings:

[2]: https://raw.githubusercontent.com/cloudipsp/magento/master/magentor.png
![Скриншот][2]
