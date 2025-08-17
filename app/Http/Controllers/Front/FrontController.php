<?php

namespace App\Http\Controllers\Front;

use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Models\Order;
use App\Traits\Salla;
use App\Models\Setting;
use Illuminate\Support\Str;
use App\Models\SallaSetting;
use Illuminate\Http\Request;
use Mpdf\Output\Destination;
use Telegram\Bot\Methods\Update;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;


class FrontController extends Controller
{
    use Salla;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //     return($this->refreshToken());

        //    return $this->createToken();

        // return $this->listOrders();

        return view('front.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function callback()
    {
        $setting = SallaSetting::first();
        if (!$setting || !$setting->client_id || !$setting->client_secret) {
            Log::error('Salla settings (client_id or client_secret) are missing.');
            return false;
        }

        $data = [
            'client_id'     => $setting->client_id,
            'client_secret' => $setting->client_secret,
            'code'          => request('code'),
            'grant_type'    => 'authorization_code',
            'scope'         => 'offline_access',
            'redirect_uri'  => config('salla.callback_url'),
            'state'         => request('state'),
        ];

        $response = Http::asForm()->post(config('salla.token_url'), $data);
        $status = $response->status();

        if ($status == 200) {
            Log::info('Token Created Successfully');
            $body = json_decode($response->body());

            $setting->updateOrCreate(
                [],
                [
                    'token'         => $body->access_token,
                    'refresh_token' => $body->refresh_token,
                    'expires_in'    => Carbon::now()->addSeconds($body->expires_in),
                ]
            );

            return true;
        }

        Log::error('Token Not Created', ['status' => $status, 'response' => $response->body()]);
        return false;
    }




    public function test()
    {
        return $this->createToken();
    }

    // public function create_pdf($id)
    // {
    //     $order = Order::where('id', $id)->first();
    //     if (!$order) {
    //         return back()->with('error', 'Order not found.');
    //     }

    //     // Fetch settings from the database
    //     $pdfColor = Setting::where('key', 'pdf_color')->first()->value ?? 'rgb(255, 0, 0)';
    //     $pdfOpacity = Setting::where('key', 'pdf_opacity')->first()->value ?? 0.2;
    //     $fontSize = Setting::where('key', 'pdf_font_size')->first()->value ?? 36;
    //     $fontStyle = Setting::where('key', 'pdf_font_style')->first()->value ?? 'B';
    //     $watermarkX = Setting::where('key', 'pdf_watermark_x')->first()->value ?? 30;
    //     $watermarkY = Setting::where('key', 'pdf_watermark_y')->first()->value ?? 140;
    //     $rotationAngle =  Setting::where('key', 'pdf_rotation_angle')->first()->value ?? 45;

    //     $fontSize = (float) $fontSize; // Ensure font size is an integer
    //     $watermarkX = (int) $watermarkX; // Ensure X position is an integer
    //     $watermarkY = (int) $watermarkY; // Ensure Y position is an integer
    //     $rotationAngle = (float) $rotationAngle; // Ensure rotation angle is a float

    //     // Clean and convert opacity value properly
    //     $pdfOpacity = str_replace(['"', "'"], '', $pdfOpacity);  // Remove quotes
    //     $pdfOpacity = trim($pdfOpacity); // Trim spaces
    //     $pdfOpacity = (float) $pdfOpacity; // Convert to float

    //     // Ensure opacity is within valid range (0.0 to 1.0)
    //     $pdfOpacity = max(0.0, min(1.0, $pdfOpacity));

    //     //  return $pdfOpacity;
    //     // Fetch the watermark color and opacity
    //     $color = $this->parseRgbColor($pdfColor);

    //     $userId = $order->user_id;
    //     $orderNumber = $order->order_number;
    //     $clientName = $order->data['client_name'] ?? ' Client';
    //     $serialNumber = $order->serial_number;

    //     $mpdf = new Mpdf([
    //         'mode' => 'utf-8',
    //         'default_font' => 'dejavusans',
    //         'format' => 'A4',
    //         'margin_top' => 0,    // Set top margin to 0
    //         'margin_bottom' => 0, // Optionally adjust bottom margin
    //         'margin_left' => 0,   // Optionally adjust left margin
    //         'margin_right' => 0   // Optionally adjust right margin
    //     ]);

    //     $pdfBookPath = storage_path('app/public/' . $order->pdf_path_original);
    //     $pageCount = $mpdf->SetSourceFile($pdfBookPath);
    //     $mpdf->autoScriptToLang = true;
    //     $mpdf->autoLangToFont = true;
    //     $mpdf->SetDirectionality('rtl');
    //     $mpdf->SetHeader('');
    //     for ($i = 1; $i <= $pageCount; $i++) {
    //         $tplId = $mpdf->ImportPage($i);
    //         $mpdf->AddPage();
    //         $mpdf->UseTemplate($tplId);

    //         // Start rotation
    //         $mpdf->StartTransform();

    //         // Rotate around center of the page (adjust X/Y for your needs)
    //         // $mpdf->Rotate(45, 105, 148);

    //         // Watermark styling
    //         // dd($pdfOpacity);
    //         $mpdf->SetAlpha($pdfOpacity); // Transparency (e.g. 0.2)
    //         $mpdf->SetFont('Amiri', 'B', 22);
    //         $mpdf->SetTextColor($color[0], $color[1], $color[2]); // e.g. [255, 0, 0]

    //         // Repeat watermark text vertically
    //         $startY = 30;          // Starting vertical position
    //         $lineSpacing = 16;     // Spacing between lines (in mm)
    //         $repeatCount = 17;     // Number of repetitions
    //         $pdfText = "Order Number {$orderNumber} - Serial Number {$serialNumber}";

    //         // $mpdf->SetXY(170, 0);
    //         // $mpdf->Cell(0, 15, $pdfText, 10, 1, 'C');
    //         // $mpdf->SetXY(170, 15);
    //         $mpdf->Cell(0, 15, $pdfText, 10, 1, 'C');
    //         $mpdf->Cell(0, 15, $pdfText, 10, 1, 'C');
    //         for ($j = 0; $j < $repeatCount; $j++) {
    //             // $y = $startY + ($j * $lineSpacing);
    //             // $mpdf->SetXY(10, $y);
    //             $mpdf->Cell(0, 15, $pdfText, 10, 1, 'C');
    //         }

    //         $mpdf->StopTransform();
    //         $mpdf->SetAlpha(1);
    //     }

    //     if ($order->pdf_path && Storage::disk('public')->exists($order->pdf_path) && str_starts_with($order->pdf_path, 'pdf/book_')) {
    //         Storage::disk('public')->delete($order->pdf_path);
    //     }

    //     // Define user-specific folder path to save the watermarked PDF
    //     $uniqueFileName = 'book_' . time() . '_' . Str::random(8) . '.pdf';

    //     $relativePdfPath = "pdf/{$uniqueFileName}";
    //     $absolutePath = storage_path("app/public/{$relativePdfPath}");

    //     $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);

    //     $order->pdf_path = $relativePdfPath;
    //     $order->save();



    //     $mpdf->Output($absolutePath, Destination::FILE);

    //     // Return the watermarked PDF file as a downloadable response
    //     return response()->file($absolutePath);
    // }

    // v2
    public function create_pdf($id)
    {
        $order = Order::where('id', $id)->first();
        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        // Fetch settings from the database
        $pdfColor = Setting::where('key', 'pdf_color')->first()->value ?? 'rgb(255, 0, 0)';
        $pdfOpacity = Setting::where('key', 'pdf_opacity')->first()->value ?? 0.2;
        $fontSize = Setting::where('key', 'pdf_font_size')->first()->value ?? 36;
        $fontStyle = Setting::where('key', 'pdf_font_style')->first()->value ?? 'B';
        $watermarkX = Setting::where('key', 'pdf_watermark_x')->first()->value ?? 30;
        $watermarkY = Setting::where('key', 'pdf_watermark_y')->first()->value ?? 140;
        $rotationAngle = Setting::where('key', 'pdf_rotation_angle')->first()->value ?? 45;

        $fontSize = (float) $fontSize;
        $watermarkX = (int) $watermarkX;
        $watermarkY = (int) $watermarkY;
        $rotationAngle = (float) $rotationAngle;

        // Clean and convert opacity value properly
        $pdfOpacity = str_replace(['"', "'"], '', $pdfOpacity);
        $pdfOpacity = trim($pdfOpacity);
        $pdfOpacity = (float) $pdfOpacity;
        $pdfOpacity = max(0.0, min(1.0, $pdfOpacity));

        // Fetch the watermark color and opacity
        $color = $this->parseRgbColor($pdfColor);

        $userId = $order->user_id;
        $orderNumber = $order->order_number;
        $clientName = $order->data['client_name'] ?? ' Client';
        $serialNumber = $order->serial_number;

        // First, get the original PDF dimensions
        $pdfBookPath = storage_path('app/public/' . $order->pdf_path_original);
        $tempMpdf = new Mpdf();
        $pageCount = $tempMpdf->SetSourceFile($pdfBookPath);

        // Get the first page to determine dimensions
        $templateId = $tempMpdf->ImportPage(1);
        $pageInfo = $tempMpdf->getTemplateSize($templateId);

        // Calculate the actual dimensions needed
        $originalWidth = $pageInfo['width'];
        $originalHeight = $pageInfo['height'];

        // Option 1: Use custom format based on original dimensions
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'format' => [$originalWidth, $originalHeight], // Use original dimensions
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'ignore_invalid_utf8' => true,
            'allow_charset_conversion' => true
        ]);

        $pageCount = $mpdf->SetSourceFile($pdfBookPath);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHeader('');

        // Clear any existing pages
        $mpdf->AddPageByArray([
            'sheet-size' => [$originalWidth, $originalHeight],
            'orientation' => 'P' // P for Portrait, L for Landscape
        ]);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $mpdf->ImportPage($i);

            // Only add page if it's not the first iteration
            if ($i > 1) {
                $mpdf->AddPageByArray([
                    'sheet-size' => [$originalWidth, $originalHeight],
                    'orientation' => 'P' // P for Portrait, L for Landscape
                ]);
            }

            // Use template - place it at exact position
            $mpdf->UseTemplate($tplId, 0, 0, $originalWidth, $originalHeight);

            // Set position for watermark to avoid page breaks
            $mpdf->SetXY(0, 0);

            // Start rotation
            $mpdf->StartTransform();

            $font = $originalWidth < 200 && $originalHeight < 200 ? 12 : 22;

            // Watermark styling
            $mpdf->SetAlpha($pdfOpacity);
            $mpdf->SetFont('Amiri', 'B', $font);
            $mpdf->SetTextColor($color[0], $color[1], $color[2]);

            // Repeat watermark text vertically with absolute positioning
            $repeatCount = 17;
            $pdfText = "Order Number {$orderNumber} - Serial Number {$serialNumber}";

            // Use absolute positioning to avoid page breaks
            $yPosition = 15;
            for ($j = 0; $j < $repeatCount; $j++) {
                $mpdf->SetXY(0, $yPosition);
                $mpdf->Cell(0, 15, $pdfText, 0, 0, 'C');
                $yPosition += 15;

                // Stop if we're getting close to page bottom
                if ($yPosition > ($originalHeight - 20)) {
                    break;
                }
            }

            $mpdf->StopTransform();
            $mpdf->SetAlpha(1);
        }

        if ($order->pdf_path && Storage::disk('public')->exists($order->pdf_path) && str_starts_with($order->pdf_path, 'pdf/book_')) {
            Storage::disk('public')->delete($order->pdf_path);
        }

        // Define user-specific folder path to save the watermarked PDF
        $uniqueFileName = 'book_' . time() . '_' . Str::random(8) . '.pdf';
        $relativePdfPath = "pdf/{$uniqueFileName}";
        $absolutePath = storage_path("app/public/{$relativePdfPath}");

        $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);

        $order->pdf_path = $relativePdfPath;
        $order->save();

        // Return the watermarked PDF file as a downloadable response
        return response()->file($absolutePath);
    }

    public function create_pdf_order($id)
    {
        $order = Order::where('id', $id)->first();
        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        // Fetch settings from the database
        $pdfColor = Setting::where('key', 'pdf_color')->first()->value ?? 'rgb(255, 0, 0)';
        $pdfOpacity = Setting::where('key', 'pdf_opacity')->first()->value ?? 0.2;
        $fontSize = Setting::where('key', 'pdf_font_size')->first()->value ?? 36;
        $fontStyle = Setting::where('key', 'pdf_font_style')->first()->value ?? 'B';
        $watermarkX = Setting::where('key', 'pdf_watermark_x')->first()->value ?? 30;
        $watermarkY = Setting::where('key', 'pdf_watermark_y')->first()->value ?? 140;
        $rotationAngle = Setting::where('key', 'pdf_rotation_angle')->first()->value ?? 45;

        $fontSize = (float) $fontSize;
        $watermarkX = (int) $watermarkX;
        $watermarkY = (int) $watermarkY;
        $rotationAngle = (float) $rotationAngle;

        // Clean and convert opacity value properly
        $pdfOpacity = str_replace(['"', "'"], '', $pdfOpacity);
        $pdfOpacity = trim($pdfOpacity);
        $pdfOpacity = (float) $pdfOpacity;
        $pdfOpacity = max(0.0, min(1.0, $pdfOpacity));

        // Fetch the watermark color and opacity
        $color = $this->parseRgbColor($pdfColor);

        $userId = $order->user_id;
        $orderNumber = $order->order_number;
        $clientName = $order->data['client_name'] ?? ' Client';
        $serialNumber = $order->serial_number;

        // First, get the original PDF dimensions
        $pdfBookPath = storage_path('app/public/' . $order->pdf_path_original);
        $tempMpdf = new Mpdf();
        $pageCount = $tempMpdf->SetSourceFile($pdfBookPath);

        // Get the first page to determine dimensions
        $templateId = $tempMpdf->ImportPage(1);
        $pageInfo = $tempMpdf->getTemplateSize($templateId);

        // Calculate the actual dimensions needed
        $originalWidth = $pageInfo['width'];
        $originalHeight = $pageInfo['height'];

        // Option 1: Use custom format based on original dimensions
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'format' => [$originalWidth, $originalHeight], // Use original dimensions
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'ignore_invalid_utf8' => true,
            'allow_charset_conversion' => true
        ]);

        $pageCount = $mpdf->SetSourceFile($pdfBookPath);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHeader('');

        // Clear any existing pages
        $mpdf->AddPageByArray([
            'sheet-size' => [$originalWidth, $originalHeight],
            'orientation' => 'P' // P for Portrait, L for Landscape
        ]);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $mpdf->ImportPage($i);

            // Only add page if it's not the first iteration
            if ($i > 1) {
                $mpdf->AddPageByArray([
                    'sheet-size' => [$originalWidth, $originalHeight],
                    'orientation' => 'P' // P for Portrait, L for Landscape
                ]);
            }

            // Use template - place it at exact position
            $mpdf->UseTemplate($tplId, 0, 0, $originalWidth, $originalHeight);

            // Set position for watermark to avoid page breaks
            $mpdf->SetXY(0, 0);

            // Start rotation
            $mpdf->StartTransform();

            $font = $originalWidth < 200 && $originalHeight < 200 ? 12 : 22;

            // Watermark styling
            $mpdf->SetAlpha($pdfOpacity);
            $mpdf->SetFont('Amiri', 'B', $font);
            $mpdf->SetTextColor($color[0], $color[1], $color[2]);

            // Repeat watermark text vertically with absolute positioning
            $repeatCount = 17;
            $pdfText = "Order Number {$orderNumber} - Serial Number {$serialNumber}";

            // Use absolute positioning to avoid page breaks
            $yPosition = 15;
            for ($j = 0; $j < $repeatCount; $j++) {
                $mpdf->SetXY(0, $yPosition);
                $mpdf->Cell(0, 15, $pdfText, 0, 0, 'C');
                $yPosition += 15;

                // Stop if we're getting close to page bottom
                if ($yPosition > ($originalHeight - 20)) {
                    break;
                }
            }

            $mpdf->StopTransform();
            $mpdf->SetAlpha(1);
        }

        if ($order->pdf_path && Storage::disk('public')->exists($order->pdf_path) && str_starts_with($order->pdf_path, 'pdf/book_')) {
            Storage::disk('public')->delete($order->pdf_path);
        }

        // Define user-specific folder path to save the watermarked PDF
        $uniqueFileName = 'book_' . time() . '_' . Str::random(8) . '.pdf';
        $relativePdfPath = "pdf/{$uniqueFileName}";
        $absolutePath = storage_path("app/public/{$relativePdfPath}");

        $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);

        $order->pdf_path = $relativePdfPath;
        $order->save();
    }

    // v2
    // public function create_pdf_order($id)
    // {
    //     $order = \App\Models\Order::where('id', $id)->first();
    //     if (!$order) {
    //         return response()->json(['error' => 'Order not found'], 404);
    //     }

    //     // Fetch settings
    //     $pdfColor = Setting::where('key', 'pdf_color')->first()->value ?? 'rgb(255, 0, 0)';
    //     $pdfOpacity = Setting::where('key', 'pdf_opacity')->first()->value ?? 0.2;
    //     $rotationAngle = Setting::where('key', 'pdf_rotation_angle')->first()->value ?? 45;

    //     $pdfOpacity = (float) trim(str_replace(['"', "'"], '', $pdfOpacity));
    //     $pdfOpacity = max(0.0, min(1.0, $pdfOpacity));
    //     $color = $this->parseRgbColor($pdfColor);

    //     $orderNumber = $order->order_number;
    //     $serialNumber = $order->serial_number;

    //     // Get path to original PDF
    //     $pdfBookPath = storage_path('app/public/' . $order->pdf_path);
    //     if (!file_exists($pdfBookPath)) {
    //         return response()->json(['error' => 'PDF file not found.'], 404);
    //     }

    //     // Get original dimensions
    //     $tempMpdf = new \Mpdf\Mpdf();
    //     $pageCount = $tempMpdf->SetSourceFile($pdfBookPath);
    //     $templateId = $tempMpdf->ImportPage(1);
    //     $pageInfo = $tempMpdf->getTemplateSize($templateId);
    //     $originalWidth = $pageInfo['width'];
    //     $originalHeight = $pageInfo['height'];

    //     // Create new mPDF with exact dimensions
    //     $mpdf = new \Mpdf\Mpdf([
    //         'mode' => 'utf-8',
    //         'default_font' => 'dejavusans',
    //         'format' => [$originalWidth, $originalHeight],
    //         'margin_top' => 0,
    //         'margin_bottom' => 0,
    //         'margin_left' => 0,
    //         'margin_right' => 0,
    //         'ignore_invalid_utf8' => true,
    //         'allow_charset_conversion' => true
    //     ]);

    //     $mpdf->autoScriptToLang = true;
    //     $mpdf->autoLangToFont = true;
    //     $mpdf->SetDirectionality('rtl');

    //     $pageCount = $mpdf->SetSourceFile($pdfBookPath);

    //     for ($i = 1; $i <= $pageCount; $i++) {
    //         $tplId = $mpdf->ImportPage($i);
    //         $mpdf->AddPageByArray([
    //             'sheet-size' => [$originalWidth, $originalHeight],
    //             'orientation' => 'P'
    //         ]);
    //         $mpdf->UseTemplate($tplId, 0, 0, $originalWidth, $originalHeight);
    //         $mpdf->SetXY(0, 0);
    //         $mpdf->StartTransform();

    //         // Font size adapts based on page size
    //         $font = ($originalWidth < 200 && $originalHeight < 200) ? 12 : 22;
    //         $mpdf->SetAlpha($pdfOpacity);
    //         $mpdf->SetFont('Amiri', 'B', $font);
    //         $mpdf->SetTextColor($color[0], $color[1], $color[2]);

    //         // Watermark content
    //         $pdfText = "Order Number {$orderNumber} - Serial Number {$serialNumber}";

    //         // Repeat watermark vertically
    //         $repeatCount = 17;
    //         $yPosition = 15;
    //         for ($j = 0; $j < $repeatCount; $j++) {
    //             $mpdf->SetXY(0, $yPosition);
    //             $mpdf->Cell(0, 15, $pdfText, 0, 0, 'C');
    //             $yPosition += 15;
    //             if ($yPosition > ($originalHeight - 20)) break;
    //         }

    //         $mpdf->StopTransform();
    //         $mpdf->SetAlpha(1);
    //     }

    //     // Delete old PDF if exists
    //     if ($order->pdf_path && Storage::disk('public')->exists($order->pdf_path) && str_starts_with($order->pdf_path, 'pdf/book_')) {
    //         Storage::disk('public')->delete($order->pdf_path);
    //     }

    //     // Save final watermarked PDF
    //     $uniqueFileName = 'book_' . time() . '_' . Str::random(8) . '.pdf';
    //     $relativePdfPath = "pdf/{$uniqueFileName}";
    //     $absolutePath = storage_path("app/public/{$relativePdfPath}");
    //     $mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);

    //     $order->pdf_path = $relativePdfPath;
    //     $order->save();

    //     return response()->json(['message' => 'PDF created successfully.', 'path' => $relativePdfPath]);
    // }




    private function parseRgbColor($rgbString)
    {
        preg_match('/rgb\((\d+), (\d+), (\d+)\)/', $rgbString, $matches);
        return isset($matches[1], $matches[2], $matches[3]) ? [(int)$matches[1], (int)$matches[2], (int)$matches[3]] : [192, 192, 192];
    }


    // public function send_pdf($id = 1)
    // {
    //     $order = \App\Models\Order::findOrFail($id);
    //     $downloadLink = asset('storage/' . $order->pdf_path);

    //     // Make sure order has a valid phone number (in international format, e.g. 9665XXXXXXXX)
    //     $phoneNumber = $order->user?->phone; // Adjust this field name as needed

    //     $message = "مرحبًا، يمكنك تحميل ملف الطلب الخاص بك من الرابط التالي:\n{$downloadLink}";
    //     $encodedMessage = urlencode($message);

    //     $whatsAppUrl = "https://wa.me/{$phoneNumber}?text={$encodedMessage}";

    //     return redirect()->away($whatsAppUrl);
    // }


    // public function send_pdf(int $id = 1)
    // {
    //     $order = \App\Models\Order::with('user')->findOrFail($id);

    //     // لو بتخزن الملف في storage/app/public
    //     $downloadLink = asset('storage/' . $order->pdf_path);

    //     $rawPhone = $order->user?->phone;
    //     if (!$rawPhone) {
    //         abort(422, 'رقم الهاتف غير متوفر.');
    //     }

    //     // تنظيف الرقم: إزالة أي رموز غير الأرقام + إزالة + أو 00 في البداية
    //     $phoneNumber = preg_replace('/\D+/', '', $rawPhone);
    //     $phoneNumber = ltrim($phoneNumber, '+');
    //     if (Str::startsWith($phoneNumber, '00')) {
    //         $phoneNumber = substr($phoneNumber, 2);
    //     }

    //     // تجهيز الرسالة
    //     $message = "مرحبًا، يمكنك تحميل ملف الطلب الخاص بك من الرابط التالي:\n{$downloadLink}";

    //     // ابني الـ URL بشكل آمن
    //     $query = http_build_query([
    //         'phone' => $phoneNumber,
    //         'text'  => $message,
    //     ]);

    //     // استخدم api.whatsapp.com (يشتغل كويس مع Desktop و Web)
    //     $base = 'https://web.whatsapp.com/send';
    //     $whatsAppUrl = "{$base}?{$query}";
        
    //     return redirect()->away($whatsAppUrl);
    // }
    public function send_pdf(int $id = 1)
    {
        $order = \App\Models\Order::with('user')->findOrFail($id);

        // لو بتخزن الملف في storage/app/public
        $filePath = storage_path('app/public/' . $order->pdf_path);

        return response()->download($filePath, $order->order_number . '.pdf');
    }


    public function preview_pdf($id)
    {
        $order = \App\Models\Order::find($id);

        // تحقق من وجود الطلب والمسار
        if (!$order || !$order->pdf_path || !file_exists(storage_path('app/public/' . $order->pdf_path))) {
            return redirect()->back()->with('error', 'عذرًا، لم يتم العثور على الملف.');
        }

        $pdfPath = storage_path('app/public/' . $order->pdf_path);
        return response()->file($pdfPath);
    }
}
