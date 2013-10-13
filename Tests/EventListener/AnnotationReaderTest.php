<?php

namespace m4t1t0\FopPdfBundle\Tests\EventListener;

use m4t1t0\FopPdfBundle\EventListener\AnnotationReader;
use m4t1t0\FopPdfBundle\Tests\EventListener\Fixture\Controller\FooControllerPDFAtMethod;
use m4t1t0\FopPdfBundle\Tests\EventListener\Fixture\Controller\FooControllerPDFAtMethodGuessTemplateController;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Symfony\Component\HttpKernel\AppKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Sensio\Bundle\FrameworkExtraBundle\Templating\TemplateGuesser;

class AnnotationReaderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        //Mock objects
        $this->kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')->disableOriginalConstructor()->getMock();
        $this->kernel->expects($this->any())
            ->method('getCacheDir')
            ->will($this->returnValue('/tmp'));

        $twigEngine = $this->getMockBuilder('Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine')->disableOriginalConstructor()->getMock();
        $templateString = file_get_contents(__DIR__ . '/Fixture/pdf.fo.twig');
        $twigEngine->expects($this->any())
            ->method('render')
            ->will($this->returnValue($templateString))
        ;

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')->disableOriginalConstructor()->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->with('templating')
            ->will($this->returnValue($twigEngine))
        ;

        $this->container->expects($this->any())
            ->method('getParameter')
            ->with('fop_pdf.fop')
            ->will($this->returnValue('/usr/local/bin/fop'))
        ;
        
        $this->listener = new AnnotationReader(new DoctrineAnnotationReader(), $this->kernel, 
            $this->container);
    }

    public function testPdfAnnotationAtMethod()
    {
        $request = new Request;

        $controller = new FooControllerPDFAtMethod();

        $this->event = $this->getFilterControllerEvent(array($controller, 'barAction'), $request);
        $this->listener->onKernelController($this->event);

        //Asserts
        $this->assertNotNull($this->getReadedPdf($request));
        
        $this->assertNotNull($this->getReadedPdfOutput($request));        
        $this->assertEquals(strftime('output_%Y-%m-%d.pdf'), $this->getReadedPdfOutput($request));

        $this->assertNotNull($this->getReadedPdfTemplate($request));
        $this->assertEquals(strftime('FooBundle:Bar:testpdf.fo.twig'), $this->getReadedPdfTemplate($request));

        $this->assertNotNull($this->getReadedPdfTempFile($request));
        $this->assertSame(13, strlen($this->getReadedPdfTempFile($request)));
    }

    public function testPdfOutputAtMethod()
    {
        $request = new Request;

        $controller = new FooControllerPDFAtMethod();
        $this->event = $this->getFilterControllerEvent(array($controller, 'barAction'), $request);
        $this->listener->onKernelController($this->event);

        //TODO, get the controller result from bazAction of a fixture controller
        $controllerResult = array(
            '_pdf_output' => 'foobar.pdf',
        );

        $this->event = $this->getGetResponseForControllerResultEvent($request, $controllerResult);
        $this->listener->onKernelView($this->event);

        $response = $this->event->getResponse();

        //Asserts
        $this->assertEquals('Symfony\Component\HttpFoundation\BinaryFileResponse', get_class($response));
        $this->assertEquals($response->getFile()->getFileName(), $this->getReadedPdfTempFile($request) . '.pdf');
    }

    protected function getFilterControllerEvent($controller, Request $request)
    {
        return new FilterControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::MASTER_REQUEST);
    }

    protected function getGetResponseForControllerResultEvent(Request $request, $controllerResult)
    {
        return new GetResponseForControllerResultEvent($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST, $controllerResult);
    }

    protected function getReadedPdf(Request $request)
    {
        return $request->attributes->get('_pdf');
    }

    protected function getReadedPdfOutput(Request $request)
    {
        return $request->attributes->get('_pdf_output');
    }

    protected function getReadedPdfTemplate(Request $request)
    {
        return $request->attributes->get('_pdf_template');
    }

    protected function getReadedPdfTempFile(Request $request)
    {
        return $request->attributes->get('_pdf_temp_file');
    }
}