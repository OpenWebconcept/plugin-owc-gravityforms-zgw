# OWC GravityForms ZGW

This plugin integrates GravityForms with ZGW using the [owc/zgw-api package](https://github.com/OpenWebconcept/owc-zgw-api).
To ensure the connection works properly, make sure the ZGW registers are configured on the ZGW API settings page.

For detailed setup instructions, refer to the [owc/zgw-api documentation](https://github.com/OpenWebconcept/owc-zgw-api/tree/main/docs).

## 🚨 Requirements

- WordPress 6.7 or higher
- GravityForms 2.5 or higher

## ⭐️ Features

- **Create** a zaak in a ZGW register
- **Upload documents** to the created zaak
- **Attach a submission PDF** to the zaak (requires [Gravity PDF](https://wordpress.org/plugins/gravity-forms-pdf-extended/))
- **Track zaak creation attempts** via a dedicated Transactions post-type that logs each attempt and its status (pending, success, or failed)

## 💡Wiki

For detailed setup instructions and documentation, visit our [Wiki on GitHub](https://github.com/OpenWebconcept/plugin-owc-gravityforms-zgw/wiki).

## Disabling SSL verification in local environments

The plugin disables SSL peer verification only when the environment value contains 'dev' (at the bare minimum).
To enable this behavior, you need to define the WP_ENVIRONMENT_TYPE constant in your wp-config.php.

```php
define( 'WP_ENVIRONMENT_TYPE', 'development' );
```

Local environments often lack valid SSL certificates, which can cause file_get_contents() or other HTTP requests to fail due to peer verification errors.
This configuration ensures smoother development without compromising production security.
