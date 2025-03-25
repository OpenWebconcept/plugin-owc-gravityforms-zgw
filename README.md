# OWC GravityForms ZGW

> [!WARNING]  
> This plugin is under development, do not use in production environments.

## Disabling SSL verification in local environments

To prevent this, the plugin disables SSL peer verification only when the environment value contains 'dev' (at the bare minimum).
To enable this behavior, you need to define the WP_ENVIRONMENT_TYPE constant in your wp-config.php.

```php
define( 'WP_ENVIRONMENT_TYPE', 'development' ); 
```

Local environments often lack valid SSL certificates, which can cause file_get_contents() or other HTTP requests to fail due to peer verification errors.
This configuration ensures smoother development without compromising production security.
