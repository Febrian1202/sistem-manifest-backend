# ERD Notasi Chen Audit Manifest Software Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menghasilkan perancangan ERD lengkap dengan Notasi Chen klasik untuk sistem audit manifest software laboratorium USN Kolaka dalam format diagram Draw.io (`.drawio`), kode Mermaid (`.mermaid`), dan naskah narasi akademis Bab 3/4 Skripsi (`.md`).

**Architecture:** Model konseptual ERD mengadopsi Notasi Chen formal (persegi untuk entitas, belah ketupat untuk relasi, elips untuk atribut dengan penanda `*` PK dan `**` FK). Seluruh elemen diselaraskan dengan database Laravel sistem (`Faculty`, `Laboratory`, `User`, `Computer`, `SoftwareCatalog`, `LicenseInventory`, `ComplianceReport`), lalu diverifikasi menggunakan automated structural test.

**Tech Stack:** Draw.io mxGraph XML, Mermaid Diagram, Markdown (Bahasa Indonesia akademis), Pest PHP / Node CLI for XML validation.

**Spec:** `docs/superpowers/specs/2026-10-13-erd-chen-audit-manifest-design.md`

## Global Constraints
- Format notasi: Chen klasik (Entitas: persegi, Relasi: belah ketupat, Atribut: elips/oval dengan `*` untuk PK dan `**` untuk FK).
- Cakupan entitas: 7 entitas inti (`fakultas`, `laboratorium`, `pengguna`, `komputer`, `katalog_software`, `lisensi`, `laporan_audit`).
- Konsistensi kardinalitas: 8 relasi bisnis dengan atribut relasi pada belah ketupat M:N (`menginstal` dan `dialokasikan`).
- Bahasa naskah skripsi: Bahasa Indonesia formal akademik.
- Lokasi file luaran:
  - `docs/diagrams/erd-chen-audit-manifest.drawio`
  - `docs/diagrams/erd-chen-audit-manifest.mermaid`
  - `docs/skripsi/perancangan-basis-data-erd.md`

## Review Focus
1. **Validitas XML Draw.io**: File `.drawio` harus berupa XML mxGraphModel yang valid tanpa error parsing saat dibuka di draw.io.
2. **Kerapian Layout Koordinat**: Posisi X/Y setiap entitas, relasi, dan oval atribut tidak boleh bertumpukan (overlap) dan simetris secara visual.
3. **Kesesuaian Notasi Kunci**: Semua primary key bertanda `*` dan foreign key bertanda `**` persis seperti gambar acuan.
4. **Kelengkapan Atribut Relasi M:N**: Belah ketupat `menginstal` memiliki atribut `versi_terpasang` dan `status_kepatuhan`, belah ketupat `dialokasikan` memiliki atribut `kuota_alokasi` dan `tgl_alokasi`.
5. **Keselarasan 3 Artefak**: Entitas, atribut, dan relasi harus identik 100% antara file Draw.io, Mermaid, dan dokumen naskah narasi skripsi.

---

### Task 1: Generate Draw.io Diagram File (`erd-chen-audit-manifest.drawio`)

**Files:**
- Create: `docs/diagrams/erd-chen-audit-manifest.drawio`
- Test: `tests/Unit/ErdDrawioValidationTest.php`

**Interfaces:**
- Produces: File XML Draw.io dengan struktur mxGraphModel berisikan 7 entitas (rectangle), 8 relasi (rhombus), 32 atribut elips (ellipse), dan konektor tepi (edge).

- [ ] **Step 1: Write the failing test for Draw.io XML structure and entity completeness**

```php
// tests/Unit/ErdDrawioValidationTest.php
<?php

test('drawio file exists and is valid mxgraph xml with all 7 chen entities', function () {
    $filePath = base_path('docs/diagrams/erd-chen-audit-manifest.drawio');
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/ErdDrawioValidationTest.php`
Expected: FAIL (file does not exist)

- [ ] **Step 3: Implement `docs/diagrams/erd-chen-audit-manifest.drawio`**

Buat file XML `docs/diagrams/erd-chen-audit-manifest.drawio` dengan standar mxGraph XML:
- Canvas ukuran luas (2400 x 1600 px) dengan penataan layout simetris.
- Entitas menggunakan style `shape=rectangle;whiteSpace=wrap;html=1;strokeWidth=2;fillColor=#ffffff;strokeColor=#1d1d1f;fontStyle=1;fontSize=13;`.
- Relasi menggunakan style `shape=rhombus;whiteSpace=wrap;html=1;strokeWidth=2;fillColor=#ffffff;strokeColor=#1d1d1f;fontStyle=1;fontSize=11;`.
- Atribut menggunakan style `shape=ellipse;whiteSpace=wrap;html=1;strokeWidth=1.5;fillColor=#ffffff;strokeColor=#1d1d1f;fontSize=10;`.
- Atribut Primary Key menggunakan aksen latar lembut `#f0f7ff`, border `#0071e3`, dan teks bertanda `*`.
- Atribut Foreign Key bertanda `**`.
- Kardinalitas (1, N, M) ditempatkan pada garis konektor.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/ErdDrawioValidationTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add docs/diagrams/erd-chen-audit-manifest.drawio tests/Unit/ErdDrawioValidationTest.php
git commit -m "feat(docs): create chen notation erd drawio diagram and validation test"
```

---

### Task 2: Generate Mermaid Diagram File (`erd-chen-audit-manifest.mermaid`)

**Files:**
- Create: `docs/diagrams/erd-chen-audit-manifest.mermaid`
- Test: `tests/Unit/ErdMermaidValidationTest.php`

**Interfaces:**
- Consumes: Definisi 7 entitas dan 8 relasi dari spec.
- Produces: File kode diagram Mermaid yang valid dengan notasi Chen (box entitas, diamond relasi, oval atribut).

- [ ] **Step 1: Write the failing test for Mermaid diagram syntax and elements**

```php
// tests/Unit/ErdMermaidValidationTest.php
<?php

test('mermaid diagram exists and contains all chen notation entities, attributes, and relations', function () {
    $filePath = base_path('docs/diagrams/erd-chen-audit-manifest.mermaid');
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/ErdMermaidValidationTest.php`
Expected: FAIL (file does not exist)

- [ ] **Step 3: Implement `docs/diagrams/erd-chen-audit-manifest.mermaid`**

Tulis sintaks Mermaid Flowchart yang merepresentasikan Notasi Chen:
- Node entitas dalam bentuk box: `E_FAKULTAS["<b>fakultas</b>"]:::entity`
- Node relasi dalam bentuk belah ketupat (rhombus): `R_MEMILIKI{"<b>memiliki</b>"}:::rel`
- Node atribut dalam bentuk stadium/rounded: `A_FAK_ID(["id_fakultas *"]):::attrPk`
- Garis penghubung dengan label kardinalitas: `E_FAKULTAS ---|1| R_MEMILIKI ---|N| E_LAB`
- Class styling untuk membedakan warna entitas, belah ketupat, dan elips atribut.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/ErdMermaidValidationTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add docs/diagrams/erd-chen-audit-manifest.mermaid tests/Unit/ErdMermaidValidationTest.php
git commit -m "feat(docs): create chen notation mermaid diagram and validation test"
```

---

### Task 3: Generate Academic Skripsi Documentation (`perancangan-basis-data-erd.md`)

**Files:**
- Create: `docs/skripsi/perancangan-basis-data-erd.md`
- Test: `tests/Unit/ErdSkripsiDocValidationTest.php`

**Interfaces:**
- Consumes: Diagram Draw.io dan Mermaid dari Task 1 & 2.
- Produces: Dokumen naskah Bab 3/4 Skripsi yang berisi deskripsi akademis, diagram, matriks kardinalitas bisnis, dan kamus data terstruktur.

- [ ] **Step 1: Write the failing test for Skripsi documentation content completeness**

```php
// tests/Unit/ErdSkripsiDocValidationTest.php
<?php

test('skripsi documentation contains complete academic sections, matrix, and data dictionary', function () {
    $filePath = base_path('docs/skripsi/perancangan-basis-data-erd.md');
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/ErdSkripsiDocValidationTest.php`
Expected: FAIL (file does not exist)

- [ ] **Step 3: Implement `docs/skripsi/perancangan-basis-data-erd.md`**

Susun naskah akademis berkualitas tinggi untuk Bab Perancangan Basis Data:
1. **Pendahuluan Perancangan Basis Data**: Konsep Notasi Chen (Peter Chen, 1976), makna simbol persegi, belah ketupat, dan elips.
2. **Diagram ERD Notasi Chen**: Kode Mermaid disematkan langsung untuk pratinjau markdown + instruksi ekspor Draw.io.
3. **Deskripsi 7 Entitas**: Penjelasan ringkas 1-2 kalimat fungsi masing-masing entitas dalam sistem audit manifest.
4. **Matriks Kardinalitas Relasi Bisnis**: Tabel lengkap 8 relasi (Entitas Asal, Nama Relasi, Entitas Tujuan, Derajat Kardinalitas, Logika Bisnis & Pertanggungjawaban).
5. **Kamus Data (Data Dictionary) Terstruktur**: Tabel untuk setiap entitas berisi: Nama Field, Tipe Data, Karakteristik (PK / FK / Normal), dan Deskripsi/Keterangan.
6. **Kamus Data Atribut Relasi Associative**: Tabel atribut untuk relasi `menginstal` (`versi_terpasang`, `status_kepatuhan`) dan relasi `dialokasikan` (`kuota_alokasi`, `tgl_alokasi`).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/ErdSkripsiDocValidationTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add docs/skripsi/perancangan-basis-data-erd.md tests/Unit/ErdSkripsiDocValidationTest.php
git commit -m "feat(docs): create academic thesis erd documentation chapter"
```

---

### Task 4: Automated Verification Test & Consistency Audit

**Files:**
- Create: `tests/Unit/ErdConsistencyAuditTest.php`

**Interfaces:**
- Consumes: Artefak dari Task 1, 2, dan 3.
- Produces: Test suite yang memvalidasi bahwa seluruh field, atribut PK/FK, dan nama relasi konsisten 100% di ketiga artefak.

- [ ] **Step 1: Implement `tests/Unit/ErdConsistencyAuditTest.php`**

```php
<?php

test('all 7 entities and attributes match consistently across drawio, mermaid, and thesis documentation', function () {
    $drawio = file_get_contents(base_path('docs/diagrams/erd-chen-audit-manifest.drawio'));
    $mermaid = file_get_contents(base_path('docs/diagrams/erd-chen-audit-manifest.mermaid'));
    $skripsi = file_get_contents(base_path('docs/skripsi/perancangan-basis-data-erd.md'));

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
    ];

    foreach ($attributesToCheck as $attr) {
        expect($drawio)->toContain($attr);
        expect($mermaid)->toContain($attr);
        expect($skripsi)->toContain($attr);
    }
});
```

- [ ] **Step 2: Run all unit tests to verify the entire ERD test suite passes**

Run: `php artisan test --filter=Erd`
Expected: 4 tests passed, 0 failures.

- [ ] **Step 3: Run Pint formatter on test files**

Run: `./vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/ErdConsistencyAuditTest.php
git commit -m "test: add comprehensive erd consistency audit across all artifacts"
```
