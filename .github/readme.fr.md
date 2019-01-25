# WP2Static

Plugin WordPress pour générer une copie statique de votre site et la déployer sur GitHub Pages, S3, Netlify, etc. Améliore la sécurité, offre un meilleur temps de chargement des pages et propose plusieurs options d'hébergement. Connectez WordPress à votre chaîne d'intégration et de déploiement continu.

[English ![English](docs/images/flags/greatbritain.png)](readme.md) |
[日本語 ![日本語](docs/images/flags/japan.png)](readme.jp.md) |
[Français ![Français](docs/images/flags/france.png)](readme.fr.md)

## WordPress comme générateur de site statique

[Regardez la présentation de Leon Stafford](http://www.youtube.com/watch?v=HPc4JjBvkrU) lors du WordCamp Brisbane 2018

[![WordPress comme générateur de site statique](http://img.youtube.com/vi/HPc4JjBvkrU/0.jpg)](http://www.youtube.com/watch?v=HPc4JjBvkrU)

## Table des matières

* [Ressources externes](#ressources-externes)
* [Logiciel pragmatique](#un-logiciel-partial)
* [Installation](#installation)
* [Commandes WP-CLI](#commandes-wp-cli)
* [Hooks](#hooks)
  * [Modifier la liste d'URLs à traiter](#modifier-la-liste-des-urls-à-traiter)
  * [Hook post-deploiement](#hook-post-déploiement)
* [Développement](#développement)
* [Localisation / traductions](#localisation--traductions)
* [Support](#support)
* [Notes](#notes)
* [Partanariat / soutien open source](#partenariat--soutien-open-source)

## Ressources externes

 - [La page du plugin sur WordPress.org](https://wordpress.org/plugins/static-html-output-plugin)
 - [Site Web](https://wp2static.com)
 - [Documentation](https://docs.wp2static.com)
 - [Forum](https://forum.wp2static.com)
 - [Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk)
 - [Twitter](https://twitter.com/wp2static)
 - [CircleCI](https://circleci.com/gh/leonstafford/wp2static) *master* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master) *develop* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/develop.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/develop)

## Un logiciel partial

 - un code d'abord performant avant d'être beau
 - un code lisible par un être humain plutôt que des noms de variables courts
 - un code maison plutôt que de multiplier les dépendances à des bibliothèques
 - un code testé en pratique plutôt que théorique (performance)
 - moins de clics == une meilleure expérience utilisateur
 - des options configurables par l'utilisateur plutôt que décidées par le développeur


## Commandes WP-CLI

 - `wp wp2static options --help`

```
NOM

  wp wp2static options

DESCRIPTION

  Read / write plugin options

SYNOPSIS

  wp wp2static options

OPTIONS

  <list> [--reveal-sensitive-values]

  Get all option names and values (explicitly reveal sensitive values)

  <get> <option-name>

  Get or set a specific option via name

  <set> <option-name> <value>

  Set a specific option via name


EXEMPLES

  Lister toutes les options

    wp wp2static options list

  Lister toutes les options (afficher les valeurs sensibles)

    wp wp2static options list --reveal_sensitive_values

  Afficher une option

    wp wp2static options get selected_deployment_option

  Définir une option

    wp wp2static options set baseUrl 'https://mystaticsite.com'
```

 - `wp wp2static generate`

```
Génération d'une copie statique du site WordPress
Terminé : Génération de l'archive statique du site en 00:00:04
```

 - `wp wp2static deploy --test`
 - `wp wp2static deploy`
 - `wp wp2static generate`

```
Génération d'une copie statique du site WordPress
Terminé : Génération de l'archive statique du site en 00:00:04
```

 - `wp wp2static deploy --test`
 - `wp wp2static deploy`

```
Déploiement du site statiqe via : zip
Terminé: Déployé via: zip en 00:00:01
Envoi de l'email de confirmation…
```

## Hooks

### Modifier la liste des URLs à traiter

 - `wp2static_modify_initial_crawl_list`
 - Filter hook

*Signature de la fonction*

```php
apply_filters(
    'wp2static_modify_initial_crawl_list',
    $url_queue
);
```

*Exemple d'utilisation*

```php
function add_additional_urls( $url_queue ) {
    $additional_urls = array(
        'http://mydomain.com/custom_link_1/',
        'http://mydomain.com/custom_link_2/',
    );

    $url_queue = array_merge(
        $url_queue,
        $additional_urls
    );

    return $url_queue;
}

add_filter( 'wp2static_modify_initial_crawl_list', 'add_additional_urls' );
```
### hook post-déploiement

 - `wp2static_post_deploy_trigger`
 - Action hook

*Signature de la fonction*

```php
do_action(
  'wp2static_post_deploy_trigger',
  $archive
);
```

*Exemple d'utilisation*

```php
function printArchiveInfo( $archive ) {
    error_log( print_r( $archive, true ) );
}

add_filter( 'wp2static_post_deploy_trigger', 'printArchiveInfo' );
```

*Exemple de réponse*

```
Archive Object
(
    [settings] => Array
        (
            [selected_deployment_option] => github
            [baseUrl] => https://leonstafford.github.io/demo-site-wordpress-static-html-output-plugin/
            [wp_site_url] => http://example.test/
            [wp_site_path] => /srv/www/example.com/current/web/wp/
            [wp_uploads_path] => /srv/www/example.com/current/web/app/uploads
            [wp_uploads_url] => http://example.test/app/uploads
            [wp_active_theme] => /wp/wp-content/themes/twentyseventeen
            [wp_themes] => /srv/www/example.com/current/web/app/themes
            [wp_uploads] => /srv/www/example.com/current/web/app/uploads
            [wp_plugins] => /srv/www/example.com/current/web/app/plugins
            [wp_content] => /srv/www/example.com/current/web/app
            [wp_inc] => /wp-includes
            [crawl_increment] => 1
        )

    [path] => /srv/www/example.com/current/web/app/uploads/wp-static-html-output-1547668758/
    [name] => wp-static-html-output-1547668758
    [crawl_list] =>
    [export_log] =>
)

```

## Développement


Ce dépôt contient le code en cours de développement, que vous pouvez cloner ou télécharger pour obtenir la version la plus récente, sinon installez l'extension via [la page officielle du plugin WordPress](https://wordpress.org/plugins/static-html-output-plugin/).

Si vous souhaitez contibuer, merci de suivre [le flow habituel de GitHub](https://guides.github.com/introduction/flow/) (créer une issue, forker le dépôt, soumettre une Pull Request). Si vous rencontrez des difficultés, demandez moi et je serai ravi de vous aider.

Afin de faciliter au maximum le développement et les contributions, nous essaierons de réduire les pré-requis au minimum. Si vous préférez utiliser Docker, Valet, Bedrock, Linux, BSD ou Mac, pas de problème. C'est un plugin WordPress, partout où vous pouvez faire tourner WordPress, vous pouvez développer ce plugin.

### Localisation / traductions

La localisation du projet a pris du retard. Toutes les personnes qui peuvent amener leur expertise dans ce domaine et m'aider à faciliter les traductions sont les bienvenues.

Voir notre [page officielle de traduction sur wordpress.org](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin).


## Support

Merci d'[ouvrir une issue](https://github.com/leonstafford/wp2static/issues/new) sur GitHub ou sur le [forum de support](https://forum.wp2static.com).

Un [groupe Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk) est à votre disposition pour échanger avec les utilisateurs de la communauté.

## Notes

Si vous clonez le dépôt pour utiliser le plugin, nommez le dossier comme le slug officiel du plugin WordPress `static-html-output-plugin`, ça vous rendra la vie plus simple.

## Partenariat / soutien open source

Je suis très attaché à ce que ce logiciel open source reste gratuit et ne vende aucune donnée à une tierce partie. À partir de la version 6, Freemius n'est plus utilisé pour cette raison. Nous acceptons les paiements avec Snipcart + Stripe, mais ces services ignorent tout de votre site WordPress ou de tout ce qui n'est pas relatif à votre paiement. Le seul mouchard restant est la vidéo youTube insérée sur notre site web, qui sera bientôt remplacée par une image pour éviter cela. J'utilise OpenBSD sur mon poste de travail et de plus en plus sur les serveurs car c'est un projet open source de très bonne facture.

Il n'y a pas de grand groupe derrière ce logiciel, si ce n'est une personne propriétaire en son nom, de manière à ce que je sois en conformité avec la loi fiscale en tant que résident Australien.

Aidez-moi à continuer à faire ce que j'aime : développer et maintenir ce logiciel.

 - [Acheter le PowerPack](https://wp2static.com)
 - [Me soutenir sur Patreon](https://www.patreon.com/leonstafford)
 - [Donner via PayPal](https://www.paypal.me/leonjstafford)

Leon

[leon@wp2static.com](mailto:leon@wp2static.com)
