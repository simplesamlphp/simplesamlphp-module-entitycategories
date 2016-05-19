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
