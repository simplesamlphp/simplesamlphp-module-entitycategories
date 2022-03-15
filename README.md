![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-entitycategories/workflows/CI/badge.svg?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-entitycategories/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-entitycategories)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-entitycategories/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-entitycategories/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-entitycategories/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-entitycategories)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-entitycategories/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-entitycategories)

Entity Categories
=================

This is a SimpleSAMLphp module to create attribute release policies based on entity categories. It allows the modification _on the fly_ of the attributes requested by a service (both removing and adding attributes) depending on the entity category or categories that the service is declared to belong to.

Please note that **this module is not a replacement for the _core:AttributeLimit_ authentication processing filter**. It will only modify the attributes requested by a service, and therefore it should be used together with the aforementioned _core:AttributeLimit_ filter or any other filter that provides a similar functionality.

Installation
------------

Once you have installed SimpleSAMLphp, installing this module is very simple. Just execute the following
command in the root of your SimpleSAMLphp installation:

```shell
composer.phar require simplesamlphp/simplesamlphp-module-entitycategories:dev-master
```

where `dev-master` instructs Composer to install the `master` (**development**) branch from the Git repository. See the [releases](https://github.com/simplesamlphp/simplesamlphp-module-entitycategories/releases) available if you want to use a stable version of the module.

Configuration
-------------

This module includes an authentication processing filter that can be configured as any other filter. Please read [the documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc) for more general information about authentication processing filters.

You can define your own entity categories, and assign the attributes allowed for each of them. It accepts the following boolean configuration options:

* `default` (defaults to `false`): when set to `true` it indicates that the attributes defined for each category should be considered the minimum set for those service providers not specifying any required attributes.
* `strict` (defaults to `false`): when this option is `true` and the a service provider has none of the listed entity categories, the module will zero out the list of releasable attributes to it. If set to `false`, the releasable attribute list will remain unchanged for non-matching service providers.
* `allowRequestedAttributes` (defaults to `false`): if set to `true` the list of requested attributes in the SP metadata will be added to the list of allowed attributes in the entity category configuration. If `false`, the attribute requirements from the metadata (ie. RequestedAttributes) are ignored during the attribute release for the service providers that match one of the entity categories.

The rest of the configuration would be `category => attributes` pairs, where *category* is the identifier of the entity category, and *attributes* is an array containing a list of attributes allowed for that category.

For example, to allow all the services in your domain to receive *eduPersonPrincipalName* as an identifier of the user, tag them all with a custom category, and define the following filter:
```php
    50 => [
        'class' => 'entitycategories:EntityCategory',
        'default' => true,
        'urn:something:local_service' => [
            'eduPersonPrincipalName',
        ],
    ],
```

Now, all the services with the following fragment in their metadata are guaranteed to receive *eduPersonPrincipalName* in case they ask for it or they don't ask for any attributes at all:

```php
    'EntityAttributes' => [
        'http://macedir.org/entity-category' => [
            'urn:something:local_service'
        ],
    ],
```

Please note that if the service asks for other attributes, not including *eduPersonPrincipalName*, **that attribute will not be sent**. If the service asks for some attributes but not *eduPersonPrincipalName*, **no attributes** will be sent. Also remember that this filter must be used together with `core:AttributeLimit` or a similar filter. Therefore, after configuring the `entitycategories:EntityCategory` filter, you should also configure the former:

```php
    51 => [
        'class' => 'core:AttributeLimit',
        'default' => true,
    ],
```

This will deny all attributes by default, but let the configuration of each service to override that limitation. Notice the indexes used for each filter. Filters are evaluated in order based on their indexes, so the filters defined in this module should have a lower index than the one assigned to `core:AttributeLimit`.

Now, if you just want to allow certain attributes to be sent to a service of a specific category, but don't want to send them in case the service doesn't ask for them, skip the `default` configuration option or set it to `false`:

```php
    50 => [
        'class' => 'entitycategories:EntityCategory',
        'urn:something:local_service' => [
            'eduPersonPrincipalName',
        ],
    ],
```

Now, if a service belonging to the `urn:something:local_service` category requests the *eduPersonPrincipalName* attribute in the `attributes` array on its metadata, it is guaranteed to get it. If it doesn't request it (no matter whether it requests other attributes or not), it won't get it.

The following example will release the attribute bundle defined in [Research and Scholarship Entity Category](https://refeds.org/category/research-and-scholarship) for SP's having the R&S entity category, but also the released set may be extended by additional attributes. For non-matching SP's, the the release rules are controlled by the metadata.

```php
    50  => [
         'class' => 'entitycategories:EntityCategory',
         'default' => true,
         'strict' => false,
         'allowRequestedAttributes' => true,
         'http://refeds.org/category/research-and-scholarship' => [
             'urn:oid:2.16.840.1.113730.3.1.241', #displayName
             'urn:oid:2.5.4.4', #sn
             'urn:oid:2.5.4.42', #givenName
             'urn:oid:0.9.2342.19200300.100.1.3', #mail
             'urn:oid:1.3.6.1.4.1.5923.1.1.1.6', #eduPersonPrincipalName
             'urn:oid:1.3.6.1.4.1.5923.1.1.1.9', #eduPersonScopedAffiliation
         ],
    ],
```

The following example implements the following logic:

1. Attributes requested in metadata are released to SP's having the `urn:x-myfederation:entities` and [GÉANT Data Protection Code of Conduct](http://www.geant.net/uri/dataprotection-code-of-conduct/v1) entity categories.
2. The Research & Scholarship entity category attribute bundle is released to R&S SP's, but the list of attributes can be extended, if the SP has additional attribute requirements in metadata.
3. No attributes are released to any other SP's.

```php
    50  => [
         'class' => 'entitycategories:EntityCategory',
         'default' => true,
         'strict' => true,
         'allowRequestedAttributes' => true,
         'urn:x-myfederation:entities' => [],
         'http://www.geant.net/uri/dataprotection-code-of-conduct/v1' => [],
         'http://refeds.org/category/research-and-scholarship' => [
             'urn:oid:2.16.840.1.113730.3.1.241', #displayName
             'urn:oid:2.5.4.4', #sn
             'urn:oid:2.5.4.42', #givenName
             'urn:oid:0.9.2342.19200300.100.1.3', #mail
             'urn:oid:1.3.6.1.4.1.5923.1.1.1.6', #eduPersonPrincipalName
             'urn:oid:1.3.6.1.4.1.5923.1.1.1.9', #eduPersonScopedAffiliation
         ],
    ],
```

You may want to release some attributes to SP's based on bilateral agreements rather than metadata. There is a [modified version of core:AttributeLimit](https://github.com/NIIF/simplesamlphp-module-attributelimit) module available that makes it possible to *add* certain attributes to some listed SP's, as presented in the next example:

```php
    51 => [
        'class' => 'niif:AttributeLimit',
        'default' => true,
        'bilateralSPs' => [
            'google.com' => ['mail'],
            'urn:federation:MicrosoftOnline' => ['IDPEmail', 'ImmutableID'],
         ],
    ],
```
