# Commerce Demo JSON API

This extends the [Commerce Demo](https://www.drupal.org/project/commerce_demo) to provide [JSON:API](https://www.drupal.org/project/jsonapi) integration and demonstrations.

## Features:

* Marks non-essential entities as internal for a scope Store API delivered over JSON:API
* Provides a subscriber to the `commerce_product.filter_variations` event that filters out SKUS which do not match the value in a `Commerce-Filter-Variations` header.

## Try it out!

There is a [Postman](https://www.getpostman.com/) collection in the `resources` directory. This has some example API calls. 

## Notes:

* Variations are properly filtered on the `/variations` relationship route, but not on the variations field value itself.
