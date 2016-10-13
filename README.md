PhlexibleSuggestBundle
======================

The PhlexibleSuggestBundle adds support for suggest fields for element types and meta sets in phlexible.

Installation
------------

1. Download PhlexibleSuggestBundle using composer
2. Enable the Bundle
3. Import PhlexibleSuggestBundle routing
4. Update your database schema
5. Clear the symfony cache

### Step 1: Download PhlexibleSuggestBundle using composer

Add PhlexibleSuggestBundle by running the command:

``` bash
$ php composer.phar require phlexible/suggest-bundle "~1.0.0"
```

Composer will install the bundle to your project's `vendor/phlexible` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Phlexible\Bundle\SuggestBundle\PhlexibleSuggestBundle(),
    );
}
```

### Step 3: Import PhlexibleElementFinderBundle routing

Import the PhlexibleElementFinderBundle routing.

For administration backend:

``` yaml
# app/config/admin_routing.yml
phlexible_suggest:
    resource: "@PhlexibleSuggestBundle/Controller/"
    type:     annotation
```

### Step 4: Update your database schema

Now that the bundle is set up, the last thing you need to do is update your database schema because the element finder includes entities that need to be installed in your database.

For ORM run the following command.

``` bash
$ php app/console doctrine:schema:update --force
```

### Step 5: Clear the symfony cache

If you access your phlexible application with environment prod, clear the cache:

``` bash
$ php app/console cache:clear --env=prod
```
