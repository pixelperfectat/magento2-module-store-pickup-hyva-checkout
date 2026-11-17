# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Pickup address sync when the shipping method changes in Hyvä Checkout. Selecting a pickup rate applies the location through the core module; switching back restores the customer's address.
- Location card in place of the shipping address form while a pickup rate is selected, re-rendered through a `pp_pickup_changed` listener.
- The billing address stays the customer's own: Hyvä's "billing same as shipping" copy is skipped for pickup quotes.
- Payment step: the "same address" checkbox is hidden for pickup, with an invoice hint under the billing title.
- Pickup banner above the billing address with a "Change" link back to the shipping step.
- Pickup card in the order summary instead of "Ship To" and "Shipping Method"; delivery quotes render Hyvä's summary unchanged.
- Location details under the selected pickup rate.
- Pickup icon (map pin) on the pickup rates, shared with the payment step banner and the summary card. Configurable through the `iconPath` argument of the `PickupIcon` ViewModel.
- Translations for `en_US`, `de_DE` and `it_IT`.
- README with requirements, installation, customising, known limits and screenshots.
