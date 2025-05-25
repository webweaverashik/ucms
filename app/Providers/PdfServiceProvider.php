<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\Pdf;
use Spatie\Browsershot\Browsershot;

class PdfServiceProvider extends ServiceProvider
{
    public function register()
    {
        Pdf::extend('custom', function () {
            return Pdf::create()
                ->setBrowsershotCallback(function (Browsershot $browsershot) {
                    $browsershot
                        ->setNodeBinary('/home/uniqueco/.nvm/versions/node/v20.19.2/bin/node')
                        ->setNpmBinary('/home/uniqueco/.nvm/versions/node/v20.19.2/bin/npm')
                        ->noSandbox()
                        ->addChromiumArguments(['--disable-dev-shm-usage']);
                });
        });
    }
}
