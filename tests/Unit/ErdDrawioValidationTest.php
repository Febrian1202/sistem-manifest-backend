<?php

test('drawio file exists and is valid mxgraph xml with all 7 chen entities', function () {
    $filePath = dirname(__DIR__, 2).'/docs/diagrams/erd/erd-chen-audit-manifest.drawio';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    $xml = simplexml_load_string($content);
    expect($xml)->not->toBeFalse();

    $entities = ['fakultas', 'laboratorium', 'pengguna', 'komputer', 'katalog_software', 'lisensi', 'laporan_audit'];
    foreach ($entities as $entity) {
        expect($content)->toContain($entity);
    }

    // Verify key attributes with * (PK) and ** (FK)
    expect($content)->toContain('id_fakultas *')
        ->toContain('id_lab *')
        ->toContain('id_fakultas **')
        ->toContain('id_user *')
        ->toContain('id_komputer *')
        ->toContain('id_software *')
        ->toContain('id_lisensi *')
        ->toContain('id_laporan *');

    // Verify relationship diamonds
    $relationships = ['memiliki', 'menugaskan', 'menempatkan', 'menginstal', 'mencakup', 'dialokasikan', 'diaudit', 'menyetujui'];
    foreach ($relationships as $rel) {
        expect($content)->toContain($rel);
    }
});
