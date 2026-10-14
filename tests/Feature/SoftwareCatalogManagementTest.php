<?php

use App\Models\SoftwareCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'pimpinan']);
    Role::create(['name' => 'kepala_lab']);
    Role::create(['name' => 'staff_lab']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Visual Studio Code',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);
});

test('admin can update software category only', function () {
    $this->actingAs($this->admin)
        ->from(route('softwares'))
        ->put(route('softwares.update', $this->catalog), [
            'category' => 'Commercial',
        ])
        ->assertRedirect(route('softwares'))
        ->assertSessionHas('status', 'success');

    $fresh = $this->catalog->fresh();
    expect($fresh->category)->toBe('Commercial')
        ->and($fresh->status)->toBe('Unreviewed');
});

test('admin can update software status only', function () {
    $this->actingAs($this->admin)
        ->from(route('softwares'))
        ->put(route('softwares.update', $this->catalog), [
            'status' => 'Whitelist',
        ])
        ->assertRedirect(route('softwares'))
        ->assertSessionHas('status', 'success');

    $fresh = $this->catalog->fresh();
    expect($fresh->category)->toBe('Freeware')
        ->and($fresh->status)->toBe('Whitelist');
});

test('admin can update software category, status, and description simultaneously', function () {
    $this->actingAs($this->admin)
        ->from(route('softwares'))
        ->put(route('softwares.update', $this->catalog), [
            'category' => 'OpenSource',
            'status' => 'Whitelist',
            'description' => 'Source code editor by Microsoft',
        ])
        ->assertRedirect(route('softwares'))
        ->assertSessionHas('status', 'success');

    $fresh = $this->catalog->fresh();
    expect($fresh->category)->toBe('OpenSource')
        ->and($fresh->status)->toBe('Whitelist')
        ->and($fresh->description)->toBe('Source code editor by Microsoft');
});

test('non-admin cannot update software catalog', function () {
    $this->actingAs($this->pimpinan)
        ->put(route('softwares.update', $this->catalog), [
            'category' => 'Commercial',
        ])
        ->assertStatus(403);
});
