<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa untuk Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut berisi pesan kesalahan standar yang digunakan oleh
    | kelas validator. Beberapa aturan memiliki beberapa versi seperti
    | aturan ukuran. Silakan ubah setiap pesan ini di sini.
    |
    */

    'accepted' => 'Kolom :attribute harus diterima.',
    'accepted_if' => 'Kolom :attribute harus diterima jika :other adalah :value.',
    'active_url' => 'Kolom :attribute harus berupa URL yang valid.',
    'after' => 'Kolom :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Kolom :attribute tidak valid.',
    'array' => 'Kolom :attribute harus berupa sebuah array.',
    'ascii' => 'Kolom :attribute hanya boleh berisi karakter alfanumerik dan simbol single-byte.',
    'before' => 'Kolom :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Kolom :attribute harus memiliki antara :min dan :max item.',
        'file' => 'Kolom :attribute harus berukuran antara :min dan :max kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min dan :max.',
        'string' => 'Kolom :attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai true atau false.',
    'can' => 'Kolom :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute kekurangan nilai yang diperlukan.',
    'current_password' => 'Password salah.',
    'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute harus cocok dengan format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal tempat desimal.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak ketika :other adalah :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai yang duplikat.',
    'doesnt_contain' => 'Kolom :attribute tidak boleh berisi salah satu dari nilai berikut: :values.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri dengan salah satu dari nilai berikut: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali dengan salah satu dari nilai berikut: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Kolom :attribute harus di-encode dalam format :encoding.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari nilai berikut: :values.',
    'enum' => 'Nilai :attribute yang dipilih tidak valid.',
    'exists' => 'Nilai :attribute yang dipilih tidak valid.',
    'extensions' => 'Kolom :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => 'Kolom :attribute harus berupa file.',
    'filled' => 'Kolom :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'Kolom :attribute harus memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus berukuran lebih dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai lebih dari :value.',
        'string' => 'Kolom :attribute harus terdiri dari lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus memiliki :value item atau lebih.',
        'file' => 'Kolom :attribute harus berukuran lebih dari atau sama dengan :value kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai lebih dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus terdiri dari lebih dari atau sama dengan :value karakter.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => 'Nilai :attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute harus ada di dalam :other.',
    'in_array_keys' => 'Kolom :attribute harus berisi salah satu dari key berikut: :values.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat (integer).',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa string JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa sebuah list.',
    'lowercase' => 'Kolom :attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus memiliki kurang dari :value item.',
        'file' => 'Kolom :attribute harus berukuran kurang dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai kurang dari :value.',
        'string' => 'Kolom :attribute harus terdiri dari kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai kurang dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus terdiri dari kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh berukuran lebih dari :max kilobita.',
        'numeric' => 'Kolom :attribute tidak boleh bernilai lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh terdiri dari lebih dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => 'Kolom :attribute harus berupa file bertipe: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa file bertipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus memiliki setidaknya :min item.',
        'file' => 'Kolom :attribute harus berukuran setidaknya :min kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai setidaknya :min.',
        'string' => 'Kolom :attribute minimal harus :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute harus memiliki setidaknya :min digit.',
    'missing' => 'Kolom :attribute tidak boleh ada.',
    'missing_if' => 'Kolom :attribute tidak boleh ada ketika :other bernilai :value.',
    'missing_unless' => 'Kolom :attribute tidak boleh ada kecuali :other bernilai :value.',
    'missing_with' => 'Kolom :attribute tidak boleh ada ketika :values ada.',
    'missing_with_all' => 'Kolom :attribute tidak boleh ada ketika :values semuanya ada.',
    'multiple_of' => 'Kolom :attribute harus kelipatan dari :value.',
    'not_in' => 'Nilai :attribute yang dipilih tidak valid.',
    'not_regex' => 'Format kolom :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus mengandung setidaknya satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung setidaknya satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => 'Kolom :attribute yang dimasukkan telah muncul dalam kebocoran data. Silakan pilih :attribute yang lain.',
    ],
    'present' => 'Kolom :attribute harus ada.',
    'present_if' => 'Kolom :attribute harus ada ketika :other bernilai :value.',
    'present_unless' => 'Kolom :attribute harus ada kecuali :other bernilai :value.',
    'present_with' => 'Kolom :attribute harus ada ketika :values ada.',
    'present_with_all' => 'Kolom :attribute harus ada ketika :values semuanya ada.',
    'prohibited' => 'Kolom :attribute dilarang.',
    'prohibited_if' => 'Kolom :attribute dilarang ketika :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute dilarang ketika :other diterima.',
    'prohibited_if_declined' => 'Kolom :attribute dilarang ketika :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute dilarang kecuali :other ada di dalam :values.',
    'prohibits' => 'Kolom :attribute melarang :other untuk ada.',
    'regex' => 'Format kolom :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus berisi entri untuk: :values.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi ketika :other diterima.',
    'required_if_declined' => 'Kolom :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other ada di dalam :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi ketika :values semuanya ada.',
    'required_without' => 'Kolom :attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi ketika tidak ada satupun :values yang ada.',
    'same' => 'Kolom :attribute harus cocok dengan :other.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Kolom :attribute harus berukuran :size kilobita.',
        'numeric' => 'Kolom :attribute harus berukuran :size.',
        'string' => 'Kolom :attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali dengan salah satu dari nilai berikut: :values.',
    'string' => 'Kolom :attribute harus berupa string.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => 'Kolom :attribute sudah digunakan.',
    'uploaded' => 'Kolom :attribute gagal diunggah.',
    'uppercase' => 'Kolom :attribute harus berupa huruf besar.',
    'url' => 'Kolom :attribute harus berupa URL yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Kustomisasi Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat menentukan pesan validasi kustom untuk atribut menggunakan
    | konvensi "attribute.rule" untuk menamai baris. Ini memudahkan untuk
    | menentukan baris bahasa kustom tertentu untuk aturan atribut tertentu.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kustomisasi Atribut Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan untuk menukar placeholder atribut dengan sesuatu
    | yang lebih ramah pembaca seperti "Alamat E-Mail" daripada "email".
    | Ini membantu kita membuat pesan validasi menjadi lebih ekspresif.
    |
    */

    'attributes' => [],

];
