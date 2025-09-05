<?php

use Mpdf\Mpdf;
use Telegram\Bot\Api;
use App\Models\Domain;
use Illuminate\Http\Request;
use Mpdf\Output\Destination;
use App\Helpers\LicenseHelper;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\SallaWebhookController;


Route::get('/login', function () {
    return redirect('/admin/login'); // or your Filament login path
})->name('login');

Route::get('/', function () {
    $host = request()->getHost();
    // if ($host == 'salla.cupun.net') {
    if ($host == 'salla.cupun.net' || $host == 'localhost' || $host == '127.0.0.1') {
        return view('welcome');
    }
    return view('license');
});

Route::get('/license/generate', function () {
    return LicenseHelper::generateKey();
});

Route::post('/license/validate', [LicenseController::class, 'validate'])->name('license.validate');

Route::post('/callback', [FrontController::class, 'callback'])->name('callback');
Route::post('/notification', [SallaWebhookController::class, 'handle'])->name('notification');
Route::get('/test-salla', [FrontController::class, 'test'])->name('test-salla');
//                 
Route::get('/create_pdf/{id?}', [FrontController::class, 'create_pdf'])->name('create_pdf');
Route::get('/preview/{id?}', [FrontController::class, 'preview_pdf'])->name('preview_pdf');
Route::get('/send/{id?}', [FrontController::class, 'send_pdf'])->name('send_pdf');


Route::get('/allowed-domains', function (Request $request) {
    $domain = $request->query('domain');

    return response()->json([
        'domain' => Domain::where('name', $domain)->exists()
    ]);
});

Route::get('/allowed-domains/check', function (Request $request) {
    $domain = $request->query('domain');

    return response()->json([
        'domain' => Domain::where('name', $domain)->where('type', 'inversor')->exists()
    ]);
});

Route::get('/allowed-domains/dashboard/check', function (Request $request) {
    $domain = $request->query('domain');

    $domain = Http::get("https://salla.cupun.net/allowed-domains/check", [
        'domain' => $domain,
    ]);

    return response()->json([
        'allowed' => $domain->json('domain') ? true : false
    ]);
});


Route::get('/test2', function () {
    $pdfPath = storage_path('app/public/book.pdf');

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'default_font' => 'dejavusans',
    ]);

    $pageCount = $mpdf->SetSourceFile($pdfPath);

    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $mpdf->ImportPage($i);
        $mpdf->AddPage();
        $mpdf->UseTemplate($tplId);

        // Start rotation
        $mpdf->StartTransform();

        // Set rotation center and angle (angle in degrees, X and Y in mm)
        $mpdf->Rotate(45, 105, 148); // Rotate around center of the page (adjust X/Y as needed)

        // Watermark styling
        $mpdf->SetAlpha(0.2); // Transparency
        $mpdf->SetFont('dejavusans', 'B', 36);
        $mpdf->SetTextColor(255, 0, 0); // Red
        $mpdf->SetXY(60, 20); // X = 60mm from left, Y = 100mm from top

        // Draw text
        $mpdf->Cell(0, 10, 'Clienttttttttttttttttt:', 0, 1, 'C');

        // End rotation
        $mpdf->StopTransform();

        $mpdf->SetAlpha(1); // Reset opacity
    }
    $newPdfPath = storage_path('app/public/watermarked22.pdf');
    $mpdf->Output($newPdfPath, Destination::FILE);

    return response()->file($newPdfPath);
});
Route::get('/test', function () {
    $pdfPath = storage_path('app/public/book.pdf');

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'default_font' => 'dejavusans',
    ]);

    $pageCount = $mpdf->SetSourceFile($pdfPath);

    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $mpdf->ImportPage($i);
        $mpdf->AddPage();
        $mpdf->UseTemplate($tplId);
        $mpdf->SetDirectionality('rtl');

        // Start rotation
        $mpdf->StartTransform();

        // Rotate around center (adjust X/Y to your page size, A4 ~210x297mm)
        $mpdf->Rotate(45, 105, 148);

        // Watermark styling
        $mpdf->SetAlpha(0.1); // Transparency
        $mpdf->SetFont('dejavusans', 'B', 90);
        $mpdf->SetTextColor(255, 0, 0); // Red

        // Repeat watermark text vertically
        $startY = 30;          // Starting vertical position
        $lineSpacing = 33;      // Spacing between lines (in mm)
        $repeatCount = 8;       // How many times to repeat the text
        $mpdf->SetAlpha(0.1); // 0.1 = very transparent, watermark-like

        $html = <<<HTML
        <div dir="rtl" style="font-family: 'Amiri'; font-size: 55px; color: rgb(255, 102, 102); text-align: center;">
            رقم الطلب: 
        </div>
        HTML;

        $mpdf->StartTransform();
        $mpdf->Rotate(30, 105, 148); // Example: rotate watermark diagonally
        $mpdf->WriteFixedPosHTML($html, 15, 148, 180, 20, 'auto');
        $mpdf->StopTransform();

        $mpdf->SetAlpha(1); // Reset alpha for remaining content

        $mpdf->WriteFixedPosHTML($html, 15, 148, 180, 20, 'auto');

        // Bottom-aligned watermark settings


        // // Watermark styling (keep your existing settings)

        // $mpdf->Rotate(45, 155, 120);
        // $mpdf->SetAlpha(0.1); // Transparency

        // // Bottom-aligned watermark settings
        // $pageHeight = $mpdf->h; // Get page height
        // $watermarkText = 'Clientttttttttttttttttttttttttttttttttttttttt:';
        // $textHeight = 15; // Approximate height of your text (adjust as needed)
        // $bottomMargin = 30; // Space from bottom

        // // Calculate starting Y position at bottom
        // $startY = $pageHeight - $bottomMargin - $textHeight;

        // // Set position at bottom (adjust X as needed)
        // $mpdf->SetXY(170, $startY);
        // $mpdf->Cell(0, 10, $watermarkText, 0, 1, 'R');
        // $mpdf->StopTransform();

        // $mpdf->SetAlpha(1); // Reset opacity
    }

    $newPdfPath = storage_path('app/public/watermarked22.pdf');
    $mpdf->Output($newPdfPath, Destination::FILE);

    return response()->file($newPdfPath);
});


Route::get('/send-message', function () {
    $chatId = '1100956557'; // Replace with your chat ID
    $message = 'Hello, this is a message from Laravel!';

    Telegram::sendMessage([
        'chat_id' => $chatId,
        'text' => $message,
    ]);

    return 'Message sent to Telegram!';
});


Route::get('updates', function () {
    $updates = Telegram::getUpdates();
    return $updates;
});


Route::post('/webhook', function () {

    $settings = TelegramSetting::where('creator_id', auth()->user()->id)->first();

    if (!$settings) {
        return 'Telegram settings not found!';
    }

    $api = new Api($settings->webhook_token);   
    $update = $api->getWebhookUpdate();

    app()->call(TelegramController::class, [
        'update' => $update,
        'settings' => $settings,
        'api' => $api, // ابعت الـ Api ككائن علشان تبعت رسائل من خلاله
    ]);

    return response('ok');
});



Route::get('/set-telegram-webhook', function () {

    $telegramSetting = TelegramSetting::where('creator_id', auth()->user()->id)->first();

    if (!$telegramSetting) {
        return response('Telegram settings not found!', 404);
    }

    $token = $telegramSetting->webhook_token;
    $api = new \Telegram\Bot\Api($token);
    $url = url("/webhook");

    $response = $api->setWebhook([
        'url' => $url,
    ]);

    return response()->json($response);
})->name('telegram.setWebhook');


// download project
Route::get('/download-project', [ProjectController::class, 'download'])->name('download-project');



Route::get('/migrate', function () {
    Artisan::call('migrate');
    return 'migrated!';
});


Route::get('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return 'cache cleared successfully!';
});


Route::get('/seed', function () {
    Artisan::call('db:seed');
    return 'seeder run successfully!';
});

Route::get('/roles-seed', function () {
    Artisan::call('db:seed', ['--class' => 'RolesTableSeeder']);
    return 'roles seeder run successfully!';
});



