Entity Categories
=================

This is a SimpleSAMLphp module to create attribute release policies based on entity categories. It allows the
modification _on the fly_ of the attributes requested by a service (both removing and adding attributes) depending on
the entity category or categories that the service is declared to belong to.

Please note that **this module is not a replacement for the _core:AttributeLimit_ authentication processing filter**. It
will only modify the attributes requested by a service, and therefore it should be used together with the aforementioned
_core:AttributeLimit_ filter or any other filter that provides a similar functionality.

Installation
------------

Once you have installed SimpleSAMLphp, installing this module is very simple. Just execute the following
command in the root of your SimpleSAMLphp installation:

```
composer.phar require simplesamlphp/simplesamlphp-module-entitycategories:dev-master
```

where `dev-master` instructs Composer to install the `master` (**development**) branch from the Git repository. See the
[releases](https://github.com/simplesamlphp/simplesamlphp-module-entitycategories/releases) available if you
want to use a stable version of the module.

Configuration
-------------

This module includes several authentication processing filters that can be configured as any other filter. Please read
[the documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc) for more general information about
authentication processing filters.

#### 1. Entity Category

This filter allows you to define your own entity categories, and assign the attributes allowed for each of them. It
accepts one single boolean configuration option named `default` to indicate that the attributes defined for each
category should be considered the minimum set for those service providers not specifying any required attributes. The
rest of the configuration would be `category => attributes` pairs, where *category* is the identifier of the entity
category, and *attributes* is an array containing a list of attributes allowed for that category.

For example, to allow all the services in your domain to receive *eduPersonPrincipalName* as an identifier of the user,
tag them all with a custom category, and define the following filter:

```
    50 => array(
        'class' => 'entitycategories:EntityCategory',
        'default' => true,
        'urn:something:local_service' => array(
            'eduPersonPrincipalName',
        ),
    ),
```

Now, all the services with the following fragment in their metadata are guaranteed to receive *eduPersonPrincipalName*
in case they ask for it or they don't ask for any attributes at all:

```
    'EntityAttributes' => array(
        'http://macedir.org/entity-category' => array(
            'urn:something:local_service'
        ),
    ),
```

Please note that if the service asks for other attributes, not including *eduPersonPrincipalName*, **that attribute will
not be sent**. Remember also that this filter must be used together with `core:AttributeLimit` or a similar filter.
Therefore, after configuring the `entitycategories:EntityCategory` filter, you should also configure the former:

```
    51 => array(
        'class' => 'core:AttributeLimit',
        'default' => true,
    ),
```

This will deny all attributes by default, but let the configuration of each service to override that limitation. Notice
the indexes used for each filter. Filters are evaluated in order based on their indexes, so the filters defined in this
module should have a lower index than the one assigned to `core:AttributeLimit`.

Now, if you just want to allow certain attributes to be sent to a service of a specific category, but don't want to send
them in case the service doesn't ask for them, skip the `default` configuration option or set it to `false`:

```
    50 => array(
        'class' => 'entitycategories:EntityCategory',
        'urn:something:local_service' => array(
            'eduPersonPrincipalName',
        ),
    ),
```

Now, if a service belonging to the _urn:something:local_service_ category requests the *eduPersonPrincipalName*
attribute in the `attributes` array on its metadata, it is guaranteed to get it. If it doesn't request it (no matter
whether it requests other attributes or not), it won't get it.
