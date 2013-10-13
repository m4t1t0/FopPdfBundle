<?php

namespace m4t1t0\FopPdfBundle\Tests\EventListener\Fixture\Controller;

use m4t1t0\FopPdfBundle\Annotation\Pdf;

class FooControllerPDFAtMethod
{
    /**
     * @Pdf(output="output_%Y-%m-%d.pdf", template="FooBundle:Bar:testpdf.fo.twig")
     */
    public function barAction()
    {
    }
}