<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrcodeController extends Controller
{
    public function download()
    {
        $url = url()->previous();
        $logo = asset('images/brand/icon-rounded-margin.png');
        $format = 'png';
        $filename = 'QR-pianolit.' . $format;
// return $url;
        return response()->streamDownload(
            function () use ($url, $logo, $format) {
                echo QrCode::size(500)->margin(1)
                                        ->format($format)
                                        ->merge($logo, .25, true)
                                        ->errorCorrection('M')
                                        ->style('round')
                                        ->generate($url);
            },
            $filename,
            ['Content-Type' => 'image/png']
        );
    }
}
