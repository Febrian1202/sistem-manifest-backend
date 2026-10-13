<?php

use App\Services\SoftwareCatalogService;
use Tests\TestCase;

uses(TestCase::class);

test('SoftwareCatalogService correctly auto-classifies commercial, freeware, and opensource software', function () {
    $service = new SoftwareCatalogService;

    // Commercial tests
    $premiere = $service->getAutoProcessedData('Adobe Premiere Pro 2019');
    expect($premiere)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $office = $service->getAutoProcessedData('Microsoft Office Professional Plus 2019 - en-us');
    expect($office)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $m365 = $service->getAutoProcessedData('Microsoft 365 - id-id');
    expect($m365)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $corel = $service->getAutoProcessedData('CorelDRAW Graphics Suite 2021');
    expect($corel)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $tablePlus = $service->getAutoProcessedData('TablePlus 6.8.1');
    expect($tablePlus)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $smartPls = $service->getAutoProcessedData('SmartPLS 4 4.1.1.7');
    expect($smartPls)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $winrar = $service->getAutoProcessedData('WinRAR 7.11 (64-bit)');
    expect($winrar)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    $powerIso = $service->getAutoProcessedData('PowerISO');
    expect($powerIso)->toBe(['category' => 'Commercial', 'status' => 'Whitelist']);

    // Freeware & OpenSource tests
    $androidStudio = $service->getAutoProcessedData('Android Studio');
    expect($androidStudio)->toBe(['category' => 'OpenSource', 'status' => 'Whitelist']);

    $arduino = $service->getAutoProcessedData('Arduino IDE 2.3.8');
    expect($arduino)->toBe(['category' => 'OpenSource', 'status' => 'Whitelist']);

    $capcut = $service->getAutoProcessedData('CapCut');
    expect($capcut)->toBe(['category' => 'Freeware', 'status' => 'Whitelist']);

    $devCpp = $service->getAutoProcessedData('Dev-C++');
    expect($devCpp)->toBe(['category' => 'OpenSource', 'status' => 'Whitelist']);

    $laragon = $service->getAutoProcessedData('Laragon 6.0.0');
    expect($laragon)->toBe(['category' => 'Freeware', 'status' => 'Whitelist']);

    $xampp = $service->getAutoProcessedData('XAMPP');
    expect($xampp)->toBe(['category' => 'OpenSource', 'status' => 'Whitelist']);

    $zoom = $service->getAutoProcessedData('Zoom Workplace');
    expect($zoom)->toBe(['category' => 'Freeware', 'status' => 'Whitelist']);

    // Unknown software remains Unreviewed
    $unknown = $service->getAutoProcessedData('Some Unknown Custom App 1.0');
    expect($unknown)->toBe(['category' => 'Freeware', 'status' => 'Unreviewed']);
});
