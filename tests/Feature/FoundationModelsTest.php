<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('laboratory can be created via factory and has fillable attributes', function () {
    $lab = Laboratory::factory()->create([
        'name' => 'Lab Sistem Informasi',
        'code' => 'LAB-SI',
        'building' => 'Gedung C',
        'floor' => '2',
        'description' => 'Lab untuk rekayasa perangkat lunak',
    ]);

    expect($lab->name)->toBe('Lab Sistem Informasi')
        ->and($lab->code)->toBe('LAB-SI')
        ->and($lab->building)->toBe('Gedung C')
        ->and($lab->floor)->toBe('2')
        ->and($lab->description)->toBe('Lab untuk rekayasa perangkat lunak');

    $this->assertDatabaseHas('laboratories', [
        'code' => 'LAB-SI',
    ]);
});

test('laboratory relationships work correctly', function () {
    $lab = Laboratory::factory()->create();

    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);
    $user = User::factory()->create(['laboratory_id' => $lab->id]);
    $approval = ReportApproval::factory()->create(['laboratory_id' => $lab->id]);

    expect($lab->computers)->toHaveCount(1)
        ->and($lab->computers->first()->id)->toBe($computer->id)
        ->and($lab->penanggungJawab)->toHaveCount(1)
        ->and($lab->penanggungJawab->first()->id)->toBe($user->id)
        ->and($lab->reportApprovals)->toHaveCount(1)
        ->and($lab->reportApprovals->first()->id)->toBe($approval->id);

    expect($computer->laboratory->id)->toBe($lab->id)
        ->and($user->laboratory->id)->toBe($lab->id)
        ->and($approval->laboratory->id)->toBe($lab->id);
});

test('report approval factory default and states work correctly', function () {
    $pending = ReportApproval::factory()->create();
    expect($pending->status)->toBe('pending')
        ->and($pending->report_type)->toBe('kepatuhan')
        ->and($pending->notes)->toBeNull()
        ->and($pending->reviewed_at)->toBeNull();

    $approved = ReportApproval::factory()->approved()->create();
    expect($approved->status)->toBe('approved')
        ->and($approved->notes)->toBe('Data sudah lengkap dan valid.')
        ->and($approved->reviewed_at)->toBeInstanceOf(Carbon::class);

    $rejected = ReportApproval::factory()->rejected()->create();
    expect($rejected->status)->toBe('rejected')
        ->and($rejected->notes)->toBe('Data belum lengkap, perlu scan ulang.')
        ->and($rejected->reviewed_at)->toBeInstanceOf(Carbon::class);
});

test('database seeder sets up roles permissions and sample laboratories', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::where('name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'kepala_lab')->exists())->toBeTrue()
        ->and(Role::where('name', 'pimpinan')->exists())->toBeTrue();

    expect(Permission::where('name', 'manage laboratories')->exists())->toBeTrue()
        ->and(Permission::where('name', 'review reports')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view lab inventory')->exists())->toBeTrue();

    $kepalaLabRole = Role::findByName('kepala_lab', 'web');
    expect($kepalaLabRole->hasPermissionTo('access admin panel'))->toBeTrue()
        ->and($kepalaLabRole->hasPermissionTo('view reports'))->toBeTrue()
        ->and($kepalaLabRole->hasPermissionTo('review reports'))->toBeTrue()
        ->and($kepalaLabRole->hasPermissionTo('view lab inventory'))->toBeTrue();

    expect(Laboratory::count())->toBeGreaterThanOrEqual(3);

    $labCodes = Laboratory::pluck('code')->all();
    expect($labCodes)->toContain('LAB-KOM1', 'LAB-KOM2', 'LAB-JRG');

    $kepalaLab = User::where('email', 'kepalalab@usn.ac.id')->first();
    expect($kepalaLab)->not->toBeNull()
        ->and($kepalaLab->hasRole('kepala_lab'))->toBeTrue()
        ->and($kepalaLab->laboratory_id)->not->toBeNull()
        ->and($kepalaLab->laboratory->code)->toBe('LAB-KOM1');
});
