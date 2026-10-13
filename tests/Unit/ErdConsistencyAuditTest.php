<?php

test('all 7 entities and attributes match consistently across drawio, mermaid, and thesis documentation', function () {
    $drawio = file_get_contents(dirname(__DIR__, 2).'/docs/diagrams/erd-chen-audit-manifest.drawio');
    $mermaid = file_get_contents(dirname(__DIR__, 2).'/docs/diagrams/erd-chen-audit-manifest.mermaid');
    $skripsi = file_get_contents(dirname(__DIR__, 2).'/docs/skripsi/perancangan-basis-data-erd.md');

    $attributesToCheck = [
        'id_fakultas *',
        'nama_fakultas',
        'kode_fakultas',
        'id_lab *',
        'id_fakultas **',
        'nama_lab',
        'lokasi',
        'id_user *',
        'id_lab **',
        'nama',
        'email',
        'role',
        'id_komputer *',
        'hostname',
        'mac_address',
        'ip_address',
        'os_name',
        'status',
        'id_software *',
        'nama_software',
        'kategori',
        'vendor',
        'id_lisensi *',
        'id_software **',
        'nomor_lisensi',
        'tipe_lisensi',
        'jumlah_kuota',
        'tgl_kadaluarsa',
        'id_laporan *',
        'periode',
        'status_kepatuhan',
        'tgl_audit',
        'versi_terpasang',
        'kuota_alokasi',
        'tgl_alokasi',
    ];

    foreach ($attributesToCheck as $attr) {
        expect($drawio)->toContain($attr);
        expect($mermaid)->toContain($attr);
        expect($skripsi)->toContain($attr);
    }
});
