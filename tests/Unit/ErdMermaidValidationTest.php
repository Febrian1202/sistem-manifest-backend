<?php

test('mermaid diagram exists and contains all chen notation entities, attributes, and relations', function () {
    $filePath = dirname(__DIR__, 2).'/docs/diagrams/erd/erd-chen-audit-manifest.mermaid';
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toMatch('/^(flowchart|graph)\s+(TB|TD|LR)/m');

    $entities = ['fakultas', 'laboratorium', 'pengguna', 'komputer', 'katalog_software', 'lisensi', 'laporan_audit'];
    foreach ($entities as $entity) {
        expect($content)->toContain($entity);
    }

    $relationships = ['memiliki', 'menugaskan', 'menempatkan', 'menginstal', 'mencakup', 'dialokasikan', 'diaudit', 'menyetujui'];
    foreach ($relationships as $rel) {
        expect($content)->toContain($rel);
    }

    // Verify key attributes
    expect($content)->toContain('id_fakultas *')
        ->toContain('id_lab *')
        ->toContain('id_fakultas **')
        ->toContain('id_user *')
        ->toContain('id_komputer *')
        ->toContain('id_software *')
        ->toContain('id_lisensi *')
        ->toContain('id_laporan *');
});
