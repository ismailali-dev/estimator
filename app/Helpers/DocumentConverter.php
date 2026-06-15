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
        $styles = '
        <style>
            body {
                font-family: "DejaVu Sans", "Helvetica", "Arial", sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                margin: 2cm;
            }
            h1 { font-size: 24pt; margin-top: 20px; margin-bottom: 10px; }
            h2 { font-size: 18pt; margin-top: 15px; margin-bottom: 8px; }
            h3 { font-size: 14pt; margin-top: 12px; margin-bottom: 6px; }
            p { margin-bottom: 8px; }
            table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 10px;
            }
            td, th {
                border: 1px solid #ddd;
                padding: 8px;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            img {
                max-width: 100%;
                height: auto;
            }
            .page-break {
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