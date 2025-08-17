<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\LicenseHelper;
use App\Models\Setting;

class LicenseController extends Controller
{
    // public function validate(Request $request)
    // {
    //     if (LicenseHelper::validateKey($request->input('license'), $request->getHost())) {
    //         Setting::updateOrCreate(
    //             ['key' => 'allowed'],
    //             ['value' => '1']
    //         ); // set allowed to true
    //         return redirect('/admin/login');
    //     }

    //     return redirect()->back()->withErrors(['license' => 'رقم الترخيص غير صحيح']);
    // }


    public function validate(Request $request)
    {
        $host = $request->getHost();
        $license = $request->input('license');

        $type = LicenseHelper::validateKey($license, $host);

        if ($type !== null) {
            Setting::updateOrCreate(['key' => 'allowed'], ['value' => '1']);
            Setting::updateOrCreate(['key' => 'is_inversor'], ['value' => $type === 'inversor' ? '1' : '0']);

            return redirect('/admin/login');
        }

        return redirect()->back()->withErrors(['license' => 'رقم الترخيص غير صحيح']);
    }
}
