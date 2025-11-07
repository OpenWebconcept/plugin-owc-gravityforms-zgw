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
- Generate a PDF and attach it to a "zaak" (requires [Gravity PDF](https://wordpress.org/plugins/gravity-forms-pdf-extended/))
- Track "zaaksysteem" transactions and their status

## Wiki

For detailed setup instructions and documentation, visit our [Wiki on GitHub](https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/wiki).

Below should be added to github wiki

## Encryption

To enable secure storage of sensitive data, you **must define an encryption key** in your `wp-config.php` file. This key is used to encrypt and decrypt the sensitive data and should be kept secret at all times.

Add the following line to your `wp-config.php`:

```php
// OWC | GravityForms ZGW – Encryption Key
define('OWC_GRAVITYFORMS_ZGW_ENCRYPTION_KEY', 'your-unique-32-character-key');
```

## Hooks

The access to the transactions overview page is restricted by capabilities. In order to give a custom role access the following filter can be used:

```php
add_filter('owc_zgw_transaction_roles_to_grant_capabilities', function (array $roles): array {
 $roles[] = 'superuser';
 return $roles;
}, 20);
```
