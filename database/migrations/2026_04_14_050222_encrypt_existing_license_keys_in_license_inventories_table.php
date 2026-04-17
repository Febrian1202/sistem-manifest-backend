<?php

use App\Models\LicenseInventory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt existing data by re-saving via Eloquent model (which has the encrypted cast)
        LicenseInventory::all()->each(function (LicenseInventory $license) {
            if ($license->license_key !== null && $license->license_key !== '') {
                // By re-saving, the 'encrypted' cast in the model will encrypt the value
                $license->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt data by reading via Eloquent model and saving back via raw DB query
        LicenseInventory::all()->each(function (LicenseInventory $license) {
            if ($license->license_key !== null && $license->license_key !== '') {
                // Eloquent automatically decrypts the key when accessing the property
                $decryptedKey = $license->license_key;
                
                // Directly update the database to store it as plaintext, bypassing the model cast
                DB::table('license_inventories')
                    ->where('id', $license->id)
                    ->update(['license_key' => $decryptedKey]);
            }
        });
    }
};
