# WHMCS PayRequest Gateway Module #

Compatible with all WHMCS versions that are [supported by WHMCS](https://docs.whmcs.com/Long_Term_Support#WHMCS_Version_.26_LTS_Schedule).

## Installation ##

- Download the [latest release](#).
- Log in to your (s)FTP.
- Extract the .zip on the server or on your computer.
- Upload both directory's to your WHMCS root folder.


## Mail Variable Usage ##

1. Login to your WHMCS panel
2. Navigate to `System Settings`
3. Open `Email Templates`
4. Open one of the following templates
    1. Third Invoice Overdue Notice
    2. Second Invoice Overdue Notice
    3. Invoice Payment Reminder
    4. Invoice Created
    5. First Invoice Overdue Notice
5. Add one of the [available payment links](#mail-variables)
6. Click Save changes

## Mail variables ##

The following variables are added with this plugin and can be used in the templates mentioned in [Usage](#mail-variable-usage).

| Variable                          | Description                                                                |
|-----------------------------------|----------------------------------------------------------------------------|  
| `${$payrequest_payment_link}`     | Create a special paymentlink for your customer to pay without loggin in.   |
| `${$payrequest_payment_link_tag}` | A copy of the above only wrap it in a direct link `<a href="link">link</a>` |
| `${payrequest_qr_image}`          | Return a QR code of the above created paymentlink url                      |
| `${payrequest_qr_image_tag}`      | A copy of the above only wrap it in a img tag `<img src="qrimg"/>`         |

## Support ##

We give basic support through our website [payrequest.io](https://payrequest.io) and customisations can be requested by mail info@payrequest.io.

## Minimum Requirements ##

- WHMCS [supported version](https://docs.whmcs.com/Long_Term_Support#WHMCS_Version_.26_LTS_Schedule)
- PHP 7.4+
