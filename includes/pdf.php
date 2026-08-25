<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

function render_html_to_pdf(string $html, string $orientation = 'portrait'): string
{
    require_once __DIR__ . '/../vendor/autoload.php';

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', $orientation === 'landscape' ? 'landscape' : 'portrait');
    $dompdf->render();

    return (string) $dompdf->output();
}
