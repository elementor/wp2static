Freemius WordPress SDK
======================

[Monetization](https://freemius.com/wordpress/), [analytics](https://freemius.com/wordpress/insights/), and marketing automation platform for plugin & theme developers. Freemius empower developers to create prosperous subscription based businesses.

You can see some of the WordPress.org plugins & themes that are utilizing the power of Freemius here:

https://includewp.com/freemius/#focus

If you are a WordPress plugin or theme developer and you are interested to monetize with Freemius you can [sign-up here for free](https://dashboard.freemius.com/register/):

https://dashboard.freemius.com/register/

**Below you'll find the integration instructions for our WordPress SDK.**

## Code Documentation

You can find the SDK's PHP-Doc documentation here:
https://codedoc.pub/freemius/wordpress-sdk/master/

## Initializing the SDK

Copy the code below and paste it into the top of your main plugin's PHP file, right after the plugin's header comment:

```php
<?php
    // Create a helper function for easy SDK access.
    function my_prefix_fs() {
        global $my_prefix_fs;
        if ( ! isset( $my_prefix_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';
    
            $my_prefix_fs = fs_dynamic_init( array(
                'id'                => '1234',
                'slug'              => 'my-plugin-slug',
                'menu_slug'         => 'my_menu_slug', // You can also use __FILE__
                'public_key'        => 'pk_MY_PUBLIC_KEY',
                'is_live'           => true,
                'is_premium'        => true,
                'has_addons'        => false,
                'has_paid_plans'    => false,
                // Set the SDK to work in a sandbox mode (for development & testing).
                // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                'secret_key'  => 'sk_MY_SECRET_KEY',
            ) );
        }
    
        return $my_prefix_fs;
    }
    
    // Init Freemius.
    my_prefix_fs();
?>
```

- **1234** - Replace with your plugin's ID.
- **pk_MY_PUBLIC_KEY** - Replace with your plugin's public key.
- **sk_MY_SECRET_KEY** - Replace with your plugin's secret key.
- **my-plugin-slug** - Replace with your plugin's WordPress.org slug.
- **my_menu_slug** - Replace with your admin dashboard settings menu slug.


## Usage example

You can call the SDK by using the shortcode function:

```php
<?php my_prefix_fs()->get_upgrade_url(); ?>
```

Or when calling Freemius multiple times in a scope, it's recommended to use it with the global variable:

```php
<?php
    global $my_prefix_fs;
    $my_prefix_fs->get_account_url();
?>
```

## Adding license based logic examples

Add marketing content to encourage your users to upgrade for your paid version:

```php
<?php
    if ( my_prefix_fs()->is_not_paying() ) {
        echo '<section><h1>' . esc_html__('Awesome Premium Features', 'my-plugin-slug') . '</h1>';
        echo '<a href="' . my_prefix_fs()->get_upgrade_url() . '">' .
            esc_html__('Upgrade Now!', 'my-plugin-slug') .
            '</a>';
        echo '</section>';
    }
?>
```

Add logic which will only be available in your premium plugin version:

```php
<?php
    // This "if" block will be auto removed from the Free version.
    if ( my_prefix_fs()->is__premium_only() ) {
    
        // ... premium only logic ...
        
    }
?>
```

To add a function which will only be available in your premium plugin version, simply add __premium_only as the suffix of the function name. Just make sure that all lines that call that method directly or by hooks, are also wrapped in premium only logic:

```php
<?php
    class My_Plugin {
        function init() {
            ...

            // This "if" block will be auto removed from the free version.
            if ( my_prefix_fs()->is__premium_only() ) {
                // Init premium version.
                $this->admin_init__premium_only();

                add_action( 'admin_init', array( &$this, 'admin_init_hook__premium_only' );
            }

            ...
        }

        // This method will be only included in the premium version.
        function admin_init__premium_only() {
            ...
        }

        // This method will be only included in the premium version.
        function admin_init_hook__premium_only() {
            ...
        }
    }
?>
```

Add logic which will only be executed for customers in your 'professional' plan:

```php
<?php
    if ( my_prefix_fs()->is_plan('professional', true) ) {
        // .. logic related to Professional plan only ...
    }
?>
```

Add logic which will only be executed for customers in your 'professional' plan or higher plans:

```php
<?php
    if ( my_prefix_fs()->is_plan('professional') ) {
        // ... logic related to Professional plan and higher plans ...
    }
?>
```

Add logic which will only be available in your premium plugin version AND will only be executed for customers in your 'professional' plan (and higher plans):

```php
<?php
    // This "if" block will be auto removed from the Free version.
    if ( my_prefix_fs()->is_plan__premium_only('professional') ) {
        // ... logic related to Professional plan and higher plans ...
    }
?>
```

Add logic only for users in trial:

```php
<?php
    if ( my_prefix_fs()->is_trial() ) {
        // ... logic for users in trial ...
    }
?>
```

Add logic for specified paid plan:

```php
<?php
    // This "if" block will be auto removed from the Free version.
    if ( my_prefix_fs()->is__premium_only() ) {
        if ( my_prefix_fs()->is_plan( 'professional', true ) ) {

            // ... logic related to Professional plan only ...

        } else if ( my_prefix_fs()->is_plan( 'business' ) ) {

            // ... logic related to Business plan and higher plans ...

        }
    }
?>
```

## Excluding files and folders from the free plugin version
There are two ways to exclude files from your free version. 

1. Add `__premium_only` just before the file extension. For example, functions__premium_only.php will be only included in the premium plugin version. This works for all type of files, not only PHP.
2. Add `@fs_premium_only` a sepcial meta tag to the plugin's main PHP file header. Example:
```php
<?php
	/**
	 * Plugin Name: My Very Awesome Plugin
	 * Plugin URI:  http://my-awesome-plugin.com
	 * Description: Create and manage Awesomeness right in WordPress.
	 * Version:     1.0.0
	 * Author:      Awesomattic
	 * Author URI:  http://my-awesome-plugin.com/me/
	 * License:     GPLv2
	 * Text Domain: myplugin
	 * Domain Path: /langs
	 *
	 * @fs_premium_only /lib/functions.php, /premium-files/
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
    
    // ... my code ...
?>
```
The file `/lib/functions.php` and the directory `/premium-files/` will be removed from the free plugin version.

# WordPress.org Compliance
Based on [WordPress.org Guidelines](https://wordpress.org/plugins/about/guidelines/) you are not allowed to submit a plugin that has premium code in it:
> All code hosted by WordPress.org servers must be free and fully-functional. If you want to sell advanced features for a plugin (such as a "pro" version), then you must sell and serve that code from your own site, we will not host it on our servers.

Therefore, if you want to deploy your free plugin's version to WordPress.org, make sure you wrap all your premium code with `if ( my_prefix_fs()->{{ method }}__premium_only() )` or the other methods provided to exclude premium features & files from the free version.

## Deployment
Zip your plugin's root folder and upload it in the Deployment section in the *Freemius Developer's Dashboard*. 
The plugin will be scanned and processed by a custom developed *PHP Processor* which will auto-generate two versions of your plugin:

1. **Premium version**: Identical to your uploaded version, including all code (except your `secret_key`). Will be enabled for download ONLY for your paying or in trial customers.
2. **Free version**: The code stripped from all your paid features (based on the logic added wrapped in `{ method }__premium_only()`). 

The free version is the one that you should give your users to download. Therefore, download the free generated version and upload to your site. Or, if your plugin was WordPress.org complaint and you made sure to exclude all your premium code with the different provided techniques, you can deploy the downloaded free version to the .org repo.

## Reporting Bugs
Email dev [at] freemius [dot] com

## FAQ

## Copyright
Freemius, Inc.
