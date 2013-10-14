<?php

namespace m4t1t0\FopPdfBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\Container;
use m4t1t0\FopPdfBundle\Annotation\Pdf;
use m4t1t0\FopPdfBundle\Process\Fop;

/**
 *
 * @author Rafael Matito <rafa.matito@gmail.com>
 */
class AnnotationReader
{
    private $reader;
    private $kernel;
    private $container;
    private $templating;
    private $fopExec;

    public function __construct(Reader $reader, KernelInterface $kernel, Container $container)
    {
        $this->reader = $reader;
        $this->kernel = $kernel;
        $this->container = $container;
        $this->templating = $container->get('templating');
        $this->fopExec = $container->getParameter('fop_pdf.fop');
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        list($object, $method) = $event->getController();

        $class = ClassUtils::getClass($object);

        $reflectionClass = new \ReflectionClass($class);
        $reflectionMethod = $reflectionClass->getMethod($method);

        $annotations = $this->reader->getMethodAnnotations($reflectionMethod);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Pdf) {
                $request = $event->getRequest();
                $controller = $event->getController();
                $request->attributes->set(
                    '_pdf',
                    true
                );

                $request->attributes->set(
                    '_pdf_output',
                    strftime($annotation->output)
                );
                
                //Set the template to render
                if ($annotation->template) {
                    $request->attributes->set(
                        '_pdf_template',
                        $annotation->template
                    );
                }
                else {
                    //TODO: Add suport for PHP templates
                    $guesser = $this->container->get('sensio_framework_extra.view.guesser');
                    $template = $guesser->guessTemplateName($controller, $request, 'twig');
                    $template = str_replace('html.twig', 'fo.twig', $template);

                    $request->attributes->set(
                        '_pdf_template',
                        $template
                    );
                }

                //Set the temp file for this request
                $request->attributes->set(
                    '_pdf_temp_file',
                    uniqid()
                );
            }
        }
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        if (! $request->attributes->get('_pdf')) {
            return;
        }

        //Get output file name
        if ($request->attributes->get('_pdf_output')) {
            $output = $request->attributes->get('_pdf_output');
        }        
        else {
            $output = 'output.pdf';
        }

        $data = $event->getControllerResult();

        //Check for special _pdf_output variable
        if (isset($data['_pdf_output'])) {
            $output = $data['_pdf_output'];
            unset($data['_pdf_output']);
        }

        $cacheDir = $this->kernel->getCacheDir() . '/fop';
        $tempFile = $request->attributes->get('_pdf_temp_file');

        if (! file_exists($cacheDir) && ! is_dir($cacheDir)) {
            mkdir($cacheDir);
        }

        //Render the template to a temp file
        $foFile = $cacheDir . '/' . $tempFile . '.fo';
        $template = $request->attributes->get('_pdf_template');
        file_put_contents($foFile, $this->templating->render($template, $data));
        $request->attributes->set('_pdf_fo_file', $foFile);

        $fop = new Fop($cacheDir, $this->fopExec, $tempFile);
        $fop->run();
        if (! $fop->isSuccessful()) {
            throw new \RuntimeException($fop->getErrorOutput());
        }

        $pdfFile = $cacheDir . '/' . $tempFile . '.pdf';
        $request->attributes->set('_pdf_output_file', $pdfFile);

        $response = new BinaryFileResponse($pdfFile);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $output);

        $event->setResponse($response);
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();

        if (! $request->attributes->get('_pdf_output_file')) {
            return;
        }

        unlink($request->attributes->get('_pdf_output_file'));
        unlink($request->attributes->get('_pdf_fo_file'));
    }

}
