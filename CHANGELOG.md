# Changelog

## [v1.12.0] - 2026-06-01

- Chore: update gravity-pdf plugin slug, plugin relocated from gravity-forms-pdf-extended to gravity-pdf
- Added: store and display zaak identificatie in entry meta and note

## [v1.11.1] - 2026-05-29

- Fix: persist encrypted BSN and KVK for pending payment transactions

## [v1.11.0] - 2026-05-20

- Change: add phpcs rule AlphabeticallySortedUses
- Chore: improved validations and logging
- Chore: update .pot file
- Chore: require gravity-forms-pdf-extended in plugin header
- Added: add zaak nummer to submission PDF with custom merge tag

## [v1.10.0] - 2026-05-19

- Added: auto-migrate saved zaaktype URL to latest version on form settings load
- Style: remove FunctionDeclarationArgumentSpacing exclusion from PHPCS config
- Change: sort form settings types and filter zaaktypen by version date
- Chore: add form settings tooltips

## [v1.9.4] - 2026-04-17

- Fix: payment hooks to prevent wrong failure handling and duplicate transactions wiping BSN/KVK

## [v1.9.3] - 2026-04-13

- Fix: overwriting of client http options
- Change: cast object attributes to string while fetching form settings

## [v1.9.2] - 2026-03-20

- Fix: per-supplier timeout applied to all suppliers

## [v1.9.1] - 2026-03-20

- Added: implement creation of address object

## [v1.9.0] - 2026-03-20

- Added: improve retry flow, partial DOM updates and admin column UX
- Added: delete zaken after failed payments with cron event
- Added: add default option to select elements in form settings
- Fix: set content-type header to text/html in transation overview mail

## [v1.8.2] - 2026-03-11

- Change: normalize roltype omschrijvingGeneriek to lowercase

## [v1.8.1] - 2026-03-11

- Change: validations of form settings adapters
- Fix: fallback to default option when mapped ZGW select value is invalid

## [v1.8.0] - 2026-03-05

- Added: map extra Rol creation args and reorganize ZGW tab content

## [v1.7.0] - 2026-03-02

- Added: make HTTP client options configurable via DI container

## [v1.6.1] - 2026-02-28

- Chore: use tagged version of zgw-api package + update deps

## [v1.6.0] - 2026-02-28

- Added: add configurable delay after zaak creation for supplier-specific post-processing

## [v1.5.3] - 2026-02-27

- Added: add clients Mozart & OpenWave

## [v1.5.2] - 2026-02-26

- Change: retrieve identification from transaction post to support Action Scheduler context

## [v1.5.1] - 2026-02-24

- Chore: remove addition of timestamp to value of date fields conditionally

## [v1.5.0] - 2026-02-24

- Added: implement action scheduler

## [v1.4.3] - 2026-02-18

- Fix: avoid caching request state (DigiD BSN) in container
- Fix: strict typing error

## [v1.4.2] - 2026-02-18

- Change: use 'now' when adding timestamps to date arguments

## [v1.4.1] - 2026-02-17

- Added: enrich creation date argument of informationobject with timestamp by setting

## [v1.4.0] - 2026-02-16

- Added: add timestamp to value of date fields conditionally
- Change: divide mapping options based on field type instead of all + new setting for date fields

## [v1.3.1] - 2026-02-13

- Fix: cast param given to FormUtils::get_link_to_form_entry() to int

## [v1.3.0] - 2026-02-06

- Add: ZGW field group and settings tab to form editor
- Add: branch number kvk to role
- Add: introduce DateTimeFormatService class
- Change: display transaction_datetime in localized format
- Change: enable strict types across the codebase

## [v1.2.1] - 2026-01-29

- Chore: support .png/.jpeg uploads

## [v1.2.0] - 2026-01-19

- Feat: implement kvk usage
- Fix: multi-input field handling for zaak creation arguments

## [v1.1.7] - 2026-01-07

- Fix: replace empty() with isset() in FieldSettings

## [v1.1.6] - 2026-01-06

- Fix: name attribute of manual supplier form settings

## [v1.1.5] - 2026-01-05

- Fix: fetching 'zaak' properties in form field settings

## [v1.1.4] - 2025-12-19

- Change: make transaction report recipient configurable

## [v1.1.3] - 2025-12-18

- Fix: restore default ZGW field mapping on field settings load in form editor

## [v1.1.2] - 2025-12-17

- Change: release github workflow

## [v1.1.1] - 2025-12-16

- Fix: missing class method time_to_execute in WPCronServiceProvider

## [v1.1.0] - 2025-12-16

- Added: retry mechanism
- Added: handle role capabilities of Transaction CPT via settings
- Added: dummy BSN form setting option
- Added: cron-job for retrieval of form settings ZGW types

## [v1.0.0] - 2025-10-31

- Initial release of OWC GravityForms ZGW plugin
