<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

function render_html_to_pdf(string $html, string $orientation = 'portrait', array $pdfOptions = []): string
{
    require_once __DIR__ . '/../vendor/autoload.php';

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', $pdfOptions['defaultFont'] ?? 'DejaVu Sans');
    $options->set('isHtml5ParserEnabled', true);

    $dompdfRoot = realpath(__DIR__ . '/../vendor/dompdf/dompdf');
    if ($dompdfRoot !== false && !empty($pdfOptions['chroot'])) {
        $chroot = [$dompdfRoot];
        foreach ((array) $pdfOptions['chroot'] as $dir) {
            $real = realpath((string) $dir);
            if ($real !== false) {
                $chroot[] = $real;
            }
        }
        $options->setChroot(array_values(array_unique($chroot)));
    }

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', $orientation === 'landscape' ? 'landscape' : 'portrait');
    $dompdf->render();

    return (string) $dompdf->output();
}
