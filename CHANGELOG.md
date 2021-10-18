# Release Notes - Payment extension for Magento2 and Unzer Payment API (PAPI)
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.1.0](https://github.com/unzerdev/magento2/compare/1.0.0..1.1.0)
### Added
*   Payment method Giropay.

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
