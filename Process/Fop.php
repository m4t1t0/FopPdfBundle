<?php

namespace m4t1t0\FopPdfBundle\Process;

use Symfony\Component\Process\Process;

/**
 * Execute Apache Fop process
 * @author Rafael Matito <rafa.matito@gmail.com>
 */
class Fop extends Process
{
    public function __construct($cacheDir, $fopExec, $tempFile)
    {
        $tmpFileName = $tempFile . '.pdf';
        $this->outputFile = $cacheDir . '/' . $tmpFileName;

        $command = sprintf($fopExec . ' ' . $cacheDir . '/' . $tempFile . '.fo -pdf ' . $this->outputFile);
        parent::__construct($command);
    }
}