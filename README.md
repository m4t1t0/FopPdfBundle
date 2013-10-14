# Apache FOP Symfony2 Integration Bundle

This bundle provides integration with Apache FOP (http://xmlgraphics.apache.org/fop/) for Symfony2

# Installation and configuration

## Get the bundle
Add to your composer.json

```
{
    "require": {
        "m4t1t0/fop-pdf-bundle": "dev-master"
    }
}
```

Use composer to download the new requirement

``` 
$ php composer.phar update egulias/listeners-debug-command-bundle
```

## Add FopPdfBundle to your application kernel

``` php
<?php

  // app/AppKernel.php
  public function registerBundles()
  {
    return array(
      // ...
      new m4t1t0\FopPdfBundle\FopPdfBundle(),      
      // ...
      );
  }
```

# Usage
