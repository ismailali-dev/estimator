<?php
// app/Helpers/DocumentConverter.php

namespace App\Helpers;

use PhpOffice\PhpWord\IOFactory;
use setasign\Fpdi\Fpdi;
use Dompdf\Dompdf;

class DocumentConverter
{
    /**
     * Convert DOC/DOCX to PDF content
     */
    public static function convertWordToPdf($filePath, $tempPdfPath = null)
    {
        try {
            // Load Word document
            $phpWord = IOFactory::load($filePath);
            
            // Create temp HTML file
            $tempHtmlPath = storage_path('app/temp/' . uniqid() . '.html');
            if (!file_exists(dirname($tempHtmlPath))) {
                mkdir(dirname($tempHtmlPath), 0755, true);
            }
            
            // Save as HTML
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $htmlWriter->save($tempHtmlPath);
            
            // Read HTML content
            $htmlContent = file_get_contents($tempHtmlPath);
            
            // Add CSS for better PDF rendering
            $htmlContent = self::addWordStyles($htmlContent);
            
            // Convert HTML to PDF using Dompdf
            $dompdf = new Dompdf();
            $dompdf->loadHtml($htmlContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Save PDF
            $pdfPath = $tempPdfPath ?: storage_path('app/temp/' . uniqid() . '.pdf');
            file_put_contents($pdfPath, $dompdf->output());
            
            // Clean up temp HTML file
            @unlink($tempHtmlPath);
            
            return $pdfPath;
            
        } catch (\Exception $e) {
            \Log::error('Word to PDF conversion failed: ' . $e->getMessage());
            throw new \Exception('Failed to convert Word document: ' . $e->getMessage());
        }
    }
    
    /**
     * Add CSS styles for better Word document rendering
     */
    private static function addWordStyles($htmlContent)
    {
        // PHPWord drops the original grid widths when exporting DOCX to HTML.
        // Mark the header and logo cell so Dompdf can reproduce the Word layout.
        // PHPWord can drop the decimal point from positioned paragraph margins
        // (for example, 2.478in becomes 2478in), moving content off the PDF page.
        $htmlContent = preg_replace_callback(
            '/margin-(left|right):\s*(\d{3,})in/i',
            function ($matches) {
                $inches = ((float) $matches[2]) / 1000;
                $value = rtrim(rtrim(number_format($inches, 3, '.', ''), '0'), '.');

                return 'margin-' . strtolower($matches[1]) . ': ' . $value . 'in';
            },
            $htmlContent
        );
        // PHPWord exports details and specifications in the same table. Split at
        // the Specifications row so earlier colspan rows cannot distort its grid.
        $htmlContent = preg_replace_callback(
            '/<table\b[^>]*>.*?<\/table>/is',
            function ($matches) {
                $table = $matches[0];

                if (stripos($table, 'Specifications:') === false) {
                    return $table;
                }

                $specificationTable = '</table>'
                    . '<table class="specification-table" style="table-layout: fixed; width: 100%;">'
                    . '<colgroup>'                    . '<col style="width: 3%;">'
                    . '<col style="width: 77%;">'
                    . '<col style="width: 10%;">'
                    . '<col style="width: 10%;">'
                    . '</colgroup>';

                $table = preg_replace(
                    '/(<tr>\s*<td colspan="5">\s*<p><span[^>]*>Specifications:<\/span>)/i',
                    $specificationTable . '$1',
                    $table,
                    1
                );

                $splitAt = strpos($table, '<table class="specification-table"');
                if ($splitAt === false) {
                    return $table;
                }

                $beforeSpecifications = substr($table, 0, $splitAt);
                $specifications = substr($table, $splitAt);
                $specifications = str_replace('colspan="5"', 'colspan="4"', $specifications);
                $specifications = str_replace(' colspan="2"', '', $specifications);
                $specifications = preg_replace_callback(
                    '/<tr>\s*(<td[^>]*>\s*<p>\s*\d+\.\s*<\/p>\s*<\/td>(?:\s*<td[^>]*>.*?<\/td>){3})\s*<\/tr>/is',
                    function ($rowMatches) {
                        preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $rowMatches[1], $cellMatches);
                        $cells = $cellMatches[1] ?? [];

                        if (count($cells) !== 4) {
                            return $rowMatches[0];
                        }

                        return '<tr><td colspan="4" style="padding: 0;">'
                            . '<div class="specification-item" style="width: 744px; overflow: hidden;">'
                            . '<div style="float: left; width: 24px;">' . $cells[0] . '</div>'
                            . '<div style="float: left; width: 595px;">' . $cells[1] . '</div>'
                            . '<div style="float: left; width: 70px; text-align: center;">' . $cells[2] . '</div>'
                            . '<div style="float: left; width: 55px; text-align: center;">' . $cells[3] . '</div>'
                            . '<div style="clear: both;"></div>'
                            . '</div></td></tr>';
                    },                    $specifications
                );

                return $beforeSpecifications . $specifications;
            },
            $htmlContent
        );
        $htmlContent = preg_replace_callback(
            '/<table\b[^>]*>.*?<\/table>/is',
            function ($matches) {
                preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $matches[0], $cellMatches);
                $cells = $cellMatches[1] ?? [];

                if (count($cells) !== 2 || stripos($cells[0], '<img') === false) {
                    return $matches[0];
                }

                return '<div class="word-header-wrap">'
                    . '<div class="word-header-inner">'
                    . '<div class="word-header-logo">' . $cells[0] . '</div>'
                    . '<div class="word-header-company">' . $cells[1] . '</div>'
                    . '</div></div>';
            },
            $htmlContent,
            1
        );

        $styles = '
        <style>
            @page {
                margin: 0.25in;
            }
            @page page1 {
                margin: 0.25in;
            }
            body {
                font-family: "Times New Roman", "DejaVu Serif", serif;
                font-size: 12pt;
                line-height: normal;
                margin: 0;
            }
            p, .Normal {
                margin-top: 0;
                margin-bottom: 8pt;
                line-height: normal;
            }
            table {
                width: 100%;
                border: 0 !important;
                border-collapse: collapse;
                border-spacing: 0;
            }
            td, th {
                border: 0 !important;
                padding: 0;
                vertical-align: top;
            }
            .specification-table td[bgcolor] {
                height: 32px;
                vertical-align: middle !important;
            }
            .specification-table td[bgcolor] p {
                height: 32px;
                margin: 0 !important;
                padding: 0 !important;
                line-height: 32px !important;
                text-align: center !important;
            }
            .specification-table td[bgcolor] p span {
                position: relative;
                top: -5px;
            }
            .word-header-wrap {
                width: 100%;
                text-align: center;
            }
            .word-header-inner {
                position: relative;
                left: 90px;
                display: inline-block;
                width: 500px;
                text-align: left;
                white-space: nowrap;
            }
            .word-header-logo {
                display: inline-block;
                width: 76px;
                vertical-align: top;
            }
            .word-header-logo img {
                width: 70px !important;
                height: 70px !important;
            }
            .word-header-company {
                display: inline-block;
                vertical-align: top;
                width: 412px;
                margin-left: 12px;
                white-space: nowrap;
            }
            .word-header-company p {
                margin: 0 0 2pt;
                line-height: 1.05;
                white-space: nowrap;
            }
            .word-header-company p:first-child span {
                font-size: 16pt !important;
            }
            .word-header-company p:not(:first-child) span {
                font-size: 13pt !important;
            }            .page-break {
                page-break-before: always;
            }
        </style>
        ';
        
        // Insert styles into HTML head
        if (strpos($htmlContent, '<head>') !== false) {
            $htmlContent = str_replace('</head>', $styles . '</head>', $htmlContent);
        } else {
            $htmlContent = '<head>' . $styles . '</head>' . $htmlContent;
        }
        
        return $htmlContent;
    }
    
    /**
     * Get page count of Word document
     */
    public static function getWordPageCount($filePath)
    {
        // This is approximate - Word doesn't easily give page count without rendering
        // You can estimate based on content length or always return 1
        return 1;
    }
}
