<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestContoller extends Controller
{
    private function bottom($mpdf)
    {

        // Bottom-aligned watermark settings
        $pageHeight = $mpdf->h; // Get page height
        $watermarkText = 'Clientttttttttttttttttttttttttttttttttttttttt:';
        $textHeight = 15; // Approximate height of your text (adjust as needed)
        $bottomMargin = 20; // Space from bottom

        // Calculate starting Y position at bottom
        $startY = $pageHeight - $bottomMargin - $textHeight;

        // Set position at bottom (adjust X as needed)
        $mpdf->SetXY(170, $startY);
        $mpdf->Cell(0, 10, $watermarkText, 0, 1, 'R');

        $mpdf->SetXY(170, 10);
        $mpdf->Cell(0, 10, 'Clientttttttttttttttttttttttttttttttttttttttt:', 0, 1, 'C');
    }
}
