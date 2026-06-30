# Kode DBML untuk dbdiagram.io

Salin (copy) semua kode di bawah ini, lalu tempel (paste) di sebelah kiri layar pada situs **[dbdiagram.io](https://dbdiagram.io)**. 
Sistem akan secara otomatis membuatkan gambar relasi tabel yang rapi beserta tipe datanya.

```dbml
// ==========================================
// DBML Database Schema
// Sistem Informasi Manifest Lisensi Software
// ==========================================

Table users {
  id bigint [pk, increment]
  name varchar
  email varchar [unique]
  email_verified_at timestamp [null]
  password varchar
  remember_token varchar [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table computers {
  id bigint [pk, increment]
  hostname varchar [unique]
  os_name varchar [null]
  os_version varchar [null]
  os_architecture varchar [null]
  os_license_status varchar [null]
  os_partial_key varchar [null]
  processor varchar [null]
  ram_gb integer [null]
  disk_total_gb integer [null]
  disk_free_gb integer [null]
  ip_address varchar [null]
  mac_address varchar [unique, null]
  serial_number varchar [null]
  manufacturer varchar [null]
  model varchar [null]
  location varchar [null]
  scan_requested boolean [default: false]
  last_seen_at timestamp [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table software_catalogs {
  id bigint [pk, increment]
  normalized_name varchar [unique]
  category varchar [note: "Enum: Freeware, Commercial, OpenSource"]
  status varchar [note: "Enum: Whitelist, Blacklist, Unreviewed"]
  description text [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table software_discoveries {
  id bigint [pk, increment]
  computer_id bigint [ref: > computers.id]
  catalog_id bigint [null, ref: > software_catalogs.id]
  raw_name varchar
  version varchar [null]
  vendor varchar [null]
  install_date date [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table license_inventories {
  id bigint [pk, increment]
  catalog_id bigint [ref: > software_catalogs.id]
  purchase_order_number varchar [null]
  quota_limit integer [default: 1]
  license_key varchar [null, note: 'Encrypted']
  purchase_date date [null]
  expiry_date date [null]
  price_per_unit decimal [null]
  notes text [null]
  proof_image varchar [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table compliance_reports {
  id bigint [pk, increment]
  computer_id bigint [ref: > computers.id]
  status varchar [note: "Enum: Safe, Warning, Critical"]
  total_software_installed integer [default: 0]
  unlicensed_count integer [default: 0]
  blacklisted_count integer [default: 0]
  violation_details json [null]
  scanned_at timestamp
  created_at timestamp [null]
  updated_at timestamp [null]
}

Table activity_log {
  id bigint [pk, increment]
  log_name varchar [null]
  description text
  subject_type varchar [null]
  subject_id bigint [null]
  event varchar [null]
  causer_type varchar [null]
  causer_id bigint [null]
  attribute_changes json [null]
  properties json [null]
  created_at timestamp [null]
  updated_at timestamp [null]
}
```
