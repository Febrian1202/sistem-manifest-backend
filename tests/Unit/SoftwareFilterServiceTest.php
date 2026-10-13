<?php

use App\Services\SoftwareFilterService;

test('SoftwareFilterService correctly identifies newly identified system junk, uninstallers, and sub-components', function () {
    $service = new SoftwareFilterService;

    $items = [
        ['name' => 'Application Verifier x64 External Package (DesktopEditions)'],
        ['name' => 'Application Verifier x64 External Package (OnecoreUAP)'],
        ['name' => 'AutoCAD Open in Desktop'],
        ['name' => 'Autodesk Access'],
        ['name' => 'Autodesk App Manager'],
        ['name' => 'Autodesk CER'],
        ['name' => 'Autodesk Featured Apps'],
        ['name' => 'Autodesk Save to Web and Mobile'],
        ['name' => 'CorelDRAW Graphics Suite 2021 - Capture (x64)'],
        ['name' => 'CorelDRAW Graphics Suite 2021 - Font Manager (x64)'],
        ['name' => 'CorelDRAW Graphics Suite 2021 - Writing Tools (x64)'],
        ['name' => 'EPSON L120 Series Printer Uninstall'],
        ['name' => 'EPSON L3210 Series Printer Uninstall'],
        ['name' => 'EPSON Scan OCR Component'],
        ['name' => 'EShare Audio Card 1.0.1'],
        ['name' => 'ESystemRemoteService 1.1'],
        ['name' => 'icecap_collection_neutral'],
        ['name' => 'icecap_collection_x64'],
        ['name' => 'icecap_collectionresources'],
        ['name' => 'icecap_collectionresourcesx64'],
        ['name' => 'Microsoft .NET Toolset 9.0.203 (x64)'],
        ['name' => 'Microsoft System CLR Types for SQL Server 2019'],
        ['name' => 'Microsoft Visual Basic for Applications 7.1 (x64)'],
        ['name' => 'Microsoft Visual Studio Tools for Applications 2019'],
        ['name' => 'Microsoft Web Deploy 4.0'],
        ['name' => 'Microsoft Windows Application Compatibility Fix Database'],
        ['name' => 'Microsoft.Ink.Handwriting.Main.en-US.1.0.1'],
        ['name' => 'MSI Development Tools'],
        ['name' => 'Universal CRT Headers Libraries and Sources'],
        ['name' => 'Universal CRT Tools x64'],
        ['name' => 'VS JIT Debugger'],
        ['name' => 'VS Script Debugging Common'],
        ['name' => 'WinAppDeploy'],
        ['name' => 'Windows App Certification Kit Native Components'],
        ['name' => 'Windows Software Development Kit - Windows 10.0.26100.3916'],
        ['name' => 'WinRT Intellisense Desktop - en-us'],
        ['name' => 'WPTx64 (DesktopEditions)'],
        ['name' => 'WPTx64 (OnecoreUAP)'],
        // Clean items:
        ['name' => 'CorelDRAW Graphics Suite 2021'],
        ['name' => 'Adobe Premiere Pro 2019'],
        ['name' => 'Android Studio'],
        ['name' => 'Dev-C++'],
    ];

    $result = $service->filter($items);

    $junkNames = collect($result->junk)->pluck('name')->all();
    $cleanNames = collect($result->clean)->pluck('name')->all();

    expect($cleanNames)->toContain('CorelDRAW Graphics Suite 2021')
        ->toContain('Adobe Premiere Pro 2019')
        ->toContain('Android Studio')
        ->toContain('Dev-C++');

    expect($junkNames)->toContain('EPSON L120 Series Printer Uninstall')
        ->toContain('Application Verifier x64 External Package (DesktopEditions)')
        ->toContain('CorelDRAW Graphics Suite 2021 - Capture (x64)')
        ->toContain('Autodesk CER')
        ->toContain('VS JIT Debugger')
        ->toContain('icecap_collection_neutral')
        ->toContain('WPTx64 (DesktopEditions)');
});
