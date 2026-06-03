# OWC GravityForms ZGW

This plugin integrates GravityForms with ZGW using the [owc/zgw-api package](https://github.com/OpenWebconcept/owc-zgw-api).
To ensure the connection works properly, make sure the ZGW registers are configured on the ZGW API settings page.

## Requirements

- PHP >= 8.1
- WordPress >= 6.7
- GravityForms >= 2.5

## Features

- Connect a form to a ZGW "zaaktype"
- Submit a form to create a zaak in a connected ZGW "zaaksysteem"
- Upload and attach documents to the created "zaak"
- Generate a PDF and attach it to a "zaak" (requires [Gravity PDF](https://gravitypdf.com/))
- Track "zaaksysteem" transactions and their status

## SSL Verification

SSL verification is enabled by default and only disabled when `WP_ENVIRONMENT_TYPE` is explicitly set to a non-production value (`staging`, `development`, or `local`). This accommodates environments where SSL certificates may not be valid.

When `WP_ENVIRONMENT_TYPE` is not defined or set to an unrecognized value, WordPress defaults to `production`, so SSL verification remains enabled.

## Wiki

For detailed setup instructions and documentation, visit our [Wiki on GitHub](https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/wiki).
