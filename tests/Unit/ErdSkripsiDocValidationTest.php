<?php

test('skripsi documentation contains complete academic sections, matrix, and data dictionary', function () {
    $filePath = dirname(__DIR__, 2).'/docs/skripsi/perancangan-basis-data-erd.md';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);

    // Required academic sections
    expect($content)->toContain('Perancangan Basis Data (Entity Relationship Diagram)')
        ->toContain('Definisi Notasi ERD')
        ->toContain('Deskripsi Entitas')
        ->toContain('Analisis Derajat Relasi dan Kardinalitas')
        ->toContain('Kamus Data (Data Dictionary)')
        ->toContain('```mermaid');

    // All 7 entities in data dictionary
    $entities = ['Fakultas', 'Laboratorium', 'Pengguna', 'Komputer', 'Katalog Software', 'Lisensi', 'Laporan Audit'];
    foreach ($entities as $entity) {
        expect($content)->toContain($entity);
    }

    // Cardinality table headers
    expect($content)->toContain('Entitas Asal')
        ->toContain('Relasi')
        ->toContain('Entitas Tujuan')
        ->toContain('Kardinalitas')
        ->toContain('Aturan Bisnis');
});
