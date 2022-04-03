# WP2Static

A WordPress plugin for static site generation and deployment.

**Latest: WP2Static joins Strattic, the leading WordPress to headless and static site end-to-end publishing platform!**

Strattic is generously keeping the WP2Static plugin available and maintained for open source users!

[Read Announcement](https://www.strattic.com/wp2static-joins-strattic/)

## Installation options

 - from this source code `git clone https://github.com/leonstafford/wp2static.git` (run `composer install` afterwards)
 - via [Composer](https://github.com/composer/composer) `composer require leonstafford/wp2static`
 - get installer zip from [wp2static.com](https://wp2static.com/download/)
 - [compile your own installer zip from source code](https://wp2static.com/compiling-from-source/)


## [Docs](https://wp2static.com)

## [Support Forum](https://staticword.press/c/wordpress-static-site-generators/wp2static/)

### Contributing

[See `CONTRIBUTING.md`](./CONTRIBUTING.md)

### Testing

WP2Static includes various types of code quality and functionality tests.

Tests are defined as Composer scripts within the `composer.json` file.

`composer run-script test` will run the main linting, static analysis and unit tests. It will not run code coverage by default. To run code coverage, use `composer run-script coverage`, this will require XDebug installed.

`composer run-script test-integration` will run end to end tests. This requires that you have the `nix-shell` command available from [NixOS](https://nixos.org/download.html). More info on the intgration tests can be found in the README within the `integration-tests` directory.

You can run individual test stages by specifying any of the defined scripts within `composer.json` with a command like `composer run-script phpunit`. You can pass arguments, such as to skip slow external request making phpunit tests, run `composer run-script phpunit -- --exclude-group ExternalRequests`.

Continuous Integration is provided by GitHub Actions, which run code quality, unit and end to end tests.


