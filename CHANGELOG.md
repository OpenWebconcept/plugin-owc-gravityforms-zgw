# Changelog

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
