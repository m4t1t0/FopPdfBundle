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
$ php composer.phar update m4t1t0/fop-pdf-bundle
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

Inside a controller file, create an action, use the Pdf annotation

``` php
<?php
    /**
     * @Route("/download")
     * @Pdf()   
     */
    public function downloadAction()
    {        
        return array(
            'name' => 'Rafa',
        );
    }
```

Create the view download.fo.twig inside your Resources directory

```
    {% extends "FopPdfBundle::basea4.fo.twig" %}

    {% block content %}
        <fo:block>
            Hello {{ name }}!
        </fo:block>
    {% endblock %}
```

# Options

The Pdf annotation suport these options:

- output: the name of the output file. You can use the PHP strftime function format, more info: http://es1.php.net/manual/en/function.strftime.php
- template: Indicates the template to render instead the guesser one.

If you need more conrol over the output file name, you can use "_pdf_output" variable in your return

``` php
<?php
    /**
     * @Route("/download")
     * @Pdf()   
     */
    public function downloadAction()
    {        
        return array(
            'name' => 'Rafa',
            '_pdf_output' => 'custom_pdf_output_filename.pdf',
        );
    }
```
