# Release Notes - Payment extension for Magento2 and Unzer Payment API (PAPI)
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.2.9](https://github.com/unzerdev/magento2/compare/3.2.8..3.2.9)
### Changed
*  Hide first name, last name and email fields for Invoice B2B payment method

## [3.2.8](https://github.com/unzerdev/magento2/compare/3.2.7..3.2.8)
### Fixed
* Rounding issue in case of total amount mismatch

## [3.2.7](https://github.com/unzerdev/magento2/compare/3.2.6..3.2.7)
### Added
* Client's IP address in every request sent to Unzer API

## [3.2.6](https://github.com/unzerdev/magento2/compare/3.2.5..3.2.6)
### Fixed
* Discount amount when taxes are applied

## [3.2.5](https://github.com/unzerdev/magento2/compare/3.2.4..3.2.5)
### Added
* Direct Bank Transfer

## [3.2.4](https://github.com/unzerdev/magento2/compare/3.2.3..3.2.4)
### Changed
* Updated CSP Whitelist
* Added deprecated warnings for Heidelpay / CSP

## [3.2.3](https://github.com/unzerdev/magento2/compare/3.2.2..3.2.3)
### Added
* ApplePay V2

## [3.2.2](https://github.com/unzerdev/magento2/compare/3.2.1..3.2.2)
### Fixed
* bank name not needed anymore for eps payment method

## [3.2.1](https://github.com/unzerdev/magento2/compare/3.2.0..3.2.1)
### Fixed
* missing mandatory cardholder field for cards payment method

## [3.2.0](https://github.com/unzerdev/magento2/compare/3.1.0..3.2.0)
### Added
* TWINT payment method
### Changed
* automatic fetching of Google Pay Gateway Merchant ID to manual fetching by adding a button for fetching of the ID
* Giropay to be deactivated in checkout and marked as deprecated 
### Removed
* Discover and JCB from available Google Pay card list

## [3.1.0](https://github.com/unzerdev/magento2/compare/3.0.0..3.1.0)
### Added
* Google Pay
### Changed
* Terms and Conditions are now included in the input check for the "Place Order" button to get activated. Additionally to all required input fields, all terms need to be checked, too. See the [terms-checked.js file]( view/frontend/web/js/model/checkout/terms-checked.js)
* system.xml to include separate files for each payment method instead of keeping everything in one file
* refactored Apple Pay "supported networks" to allow "supported networks" for Google Pay, too
### Fixed
* Threat Metrix data was not correctly handled for some payment methods

## [3.0.0](https://github.com/unzerdev/magento2/compare/2.5.0..3.0.0)
### Added
* Partial Charge for Credit Card, Paylater Invoice, Unzer Installment, Direct Debit and PayPal
* PHP 8.3 and Magento 2.4.7 compatibility
### Changed
* **(BIC!)** The module now always uses Magento's base currency for communication with the Unzer servers, otherwise partial charge would not be possible. Please make sure your Magento Installation is configured accordingly.

## [2.5.0](https://github.com/unzerdev/magento2/compare/2.4.1..2.5.0)
### Added 
* Direct Debit Secured payment method
### Fixed
* active logging automatically switched to sandbox urls

## [2.4.1](https://github.com/unzerdev/magento2/compare/2.4.0..2.4.1)
### Removed
* Payment Details on success page for Paylater Invoice payment methods. Details are send by Paylater via email.
### Fixed
* Order Confirmation Email sending for "pending payment" order status
* Apple Pay Certificate Upload for Multi Store Shops

## [2.4.0](https://github.com/unzerdev/magento2/compare/2.3.1..2.4.0)
### Added
* Installment payment method
### Changed
* the names (and defaults for the title settings) of the following payment methods:
    * English 
        * Unzer Invoice → (Deprecated) Unzer Invoice
        * Unzer Invoice secured (B2C) → (Deprecated) Unzer Invoice Secured (B2C)
        * Unzer Invoice secured (B2B) → (Deprecated) Unzer Invoice Secured (B2B)
        * Unzer Paylater Invoice (B2C) → Invoice (B2C)
        * Unzer Paylater Invoice (B2B) → Invoice (B2B)
    * German
        * Unzer Rechnungskauf → (Veraltet) Unzer Rechnungskauf
        * Unzer Rechnungskauf gesichert (B2C) → (Veraltet) Unzer Rechnungskauf Gesichert (B2C)
        * Unzer Rechnungskauf gesichert (B2B) → (Veraltet) Unzer Rechnungskauf Gesichert (B2B)
        * Unzer Paylater Rechnungskauf (B2C) → Rechnungskauf (B2C)
        * Unzer Paylater Rechnungskauf (B2B) → Rechnungskauf (B2B)
### Deprecated
* the following payment methods:
    * Unzer Invoice
    * Unzer Invoice secured (B2C)
    * Unzer Invoice secured (B2B)
### Fixed
* refund for orders with different currencies than Magento's configured base currency are refunding the correct amount now
* some of the Api interfaces and classes caused errors with Magento's Swagger page
* correction of the return value for the success message in Unzer Prepayment.
* missing ThreatMetrix CSP Whitelist policies
* Unzer uiComponents are now initialized using the current store locale
* Unzer uiComponents containing address data for Invoice and Installment are now updated, if billing or shipping addresses change 
### Removed
* license information from all code files. See LICENSE and NOTICE files now.

## [2.3.1](https://github.com/unzerdev/magento2/compare/2.3.0..2.3.1)

### Fixed
* removed old files, causing fatal errors

## [2.3.0](https://github.com/unzerdev/magento2/compare/2.2.0..2.3.0)

### Added
* Support for Magento 2.4.6 and PHP 8.2
* license files and missing licence texts in some php files

### Changed
* refactoring of old code to take advantage of newer PHP versions and to be compliant with Magento Coding Standards as far as possible

### Fixed
* "pending" order status with payment methods, which use redirects to external pages, like PayPal. The Status "pending_payment" is now set before the redirect happens, so Magento can cancel abandoned orders automatically
* problems with bundle products and how discounts are transferred to the Unzer servers. Previously discounts for cart items would have been transferred to the Unzer Servers per item. Now only the sum of all discounts for the whole cart is transferred, otherwise we would end up with rounding errors.

## [2.2.1](https://github.com/unzerdev/magento2/compare/2.2.0..2.2.1)
### Fixed
* bank name not needed anymore for eps payment method

## [2.2.0](https://github.com/unzerdev/magento2/compare/2.1.1..2.2.0)

### Added
* Magento Vault support to credit card and PayPal payment methods
### Changed
* requirement of Unzer PHP SDK to use their new 3-digit versioning
### Removed
* support for end-of-life PHP Versions 7.1, 7.2, 7.3

## [2.1.1](https://github.com/unzerdev/magento2/compare/2.1.0..2.1.1)

### Fixed
* Checkout Problems with Bundle Products and Discounts

## [2.1.0](https://github.com/unzerdev/magento2/compare/2.0.0..2.1.0)

### Added
* new Payment Method Apple Pay

## [2.0.0](https://github.com/unzerdev/magento2/compare/1.4.2..2.0.0)

### Added
* new Payment Methods Paylater Invoice B2C and Paylater Invoice B2B
* Payment Methods Paylater Invoice B2C/B2B have a new setting to override general API Keys and use separate ones
  * Attention! The changes we had to make here, might be backwards incompatible changes, affecting all payment methods, depending on your own extensions of this module.

### Fixed
* Cancel of authorization payment methods (credit card / paypal) not being send to unzer account, resulting in an "offline" Cancel. Now "Online" Cancel is possible.
* Void of authorization payment methods (credit card / paypal) is now possible
* Order Emails now being send for method Unzer Prepayment

## [1.4.2](https://github.com/unzerdev/magento2/compare/1.4.1..1.4.2)
### Fixed
* php short tag in backend order template
* totals update in backend order
* invoice email not send in backend order
* state not correct on backend order creation

## [1.4.1](https://github.com/unzerdev/magento2/compare/1.4.0..1.4.1)
### Fixed
* Prices of basket items not including tax  
* basket items missing tax percent and reference id

## [1.4.0](https://github.com/unzerdev/magento2/compare/1.3.0..1.4.0)
### Added
* Requirement for unzerdev/php-sdk 1.2.x
* Support for backend order creation to Unzer Invoice Secured payment method

### Changed
* Authorization and capture handling to use unzerdev/php-sdk 1.2.x

### Fixed
* multiple order emails being sent in some cases
* invoice or credit memo emails showing total amount or negative amount instead of due amount in some cases

## [1.3.0](https://github.com/unzerdev/magento2/compare/1.2.0..1.3.0)
### Added
* configuration setting to be able to switch between base currency or customer (storeview) currency for transfers to unzer servers
* Payment Method Alipay
* Payment Method Bancontact (only Belgium)
* Payment Method Przelewy 24 (only Poland)
* Payment Method Wechat
* Payment Method Unzer Prepayment

### Fixed 
* amount and currency not matching on multistore installations with multiple currencies
* Fix an issue where the customer form was not rendered in checkout sometimes. Invoice Secured B2C/B2B and Sepa Direct Debit B2C were affected by that.

## [1.2.0](https://github.com/unzerdev/magento2/compare/1.1.1..1.2.0)
### Changed
* PHP 8.1 Compatibility
 
## [1.1.1](https://github.com/unzerdev/magento2/compare/1.1.0..1.1.1)

### Changed
* If no, or invalid keys are configured payment methods are not active in checkout.
* Update broken documentation links in readme.
* Set minimum php-sdk version [1.1.4.2](https://github.com/unzerdev/php-sdk/releases/tag/1.1.4.2).
* Change translation keys of invoice payment methods to avoid translation conflicts with shop system.
* Display Module version in Backend configuration.
* Several minor improvements.

### Fix
* Empty public key causing an exception in checkout.

## [1.1.0](https://github.com/unzerdev/magento2/compare/1.0.0..1.1.0)
### Added
*   Payment method EPS.
*   Payment method Giropay.

### Changed
* Checkout will be aborted now if customer creation fails. The error message will be displayed in checkout.
* Allow configuration of booking mode on store level for "Credit Card / Debit Card" and "PayPal".
* If possible, display a more descriptive message to the customer if card submission fails.

## [1.0.0](https://github.com/unzerdev/magento2/compare/06675c1be6009ce9f4e4cc78f8eecfc8447b2f5d..1.0.0)
### Changed
* Rebranding of the Plugin.
* Remove preconfigured test keypair from config.
* Switch to Unzer PHP SDK.
* Switch to Unzer UI components.
* Fixed an issue regarding inconsistent dependency of `messageManager` used in `AbstractPaymentAction`.
* Controller uses already existing `_redirect()` method for redirects now.
* Added necessary sources to whitelist for content security policy.

### Fix
* `Sepa Direct Debit Secured` now uses the merchant name configured in this payment method for the sepa direct debit mandate text. Previously the merchant name configured in `Sepa Direct Debit` was used.
* Adjust payment method templates: checkout-agreements-block moved beneath the payment form to avoid css conflicts that can causing the checkbox being not clickable.

[1.0.0]: https://github.com/unzerdev/magento2/compare/06675c1be6009ce9f4e4cc78f8eecfc8447b2f5d..1.0.0
