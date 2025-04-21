<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Models\DataOfficeSimpeg;
use App\Models\DataOfficeAbsen;
use App\Models\DataOfficeEkinerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--env' => 'testing']);
    }

    public function test_get_profile_success()
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $officeSimpeg = DataOfficeSimpeg::factory()->create(['id_instansi' => 'instansi-123']);
        $officeAbsen = DataOfficeAbsen::factory()->create(['id_instansi' => 'instansi-123']);
        $officeEkinerja = DataOfficeEkinerja::factory()->create(['id' => 'instansi-123']);
        $simpeg = DataPegawaiSimpeg::factory()->create(['nip' => 'testuser', 'id_instansi' => 'instansi-123']);
        $absen = DataPegawaiAbsen::factory()->create(['nip' => 'testuser', 'id_instansi' => 'instansi-123']);
        $ekinerja = DataPegawaiEkinerja::factory()->create(['nip' => 'testuser', 'id_instansi' => 'instansi-123']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'user' => ['name', 'username', 'email'],
                    'dataPegawaiSimpeg' => ['id_pegawai', 'nip', 'nama_lengkap', 'office' => ['id_instansi', 'nama_instansi']],
                    'dataPegawaiAbsen' => ['id_pegawai', 'nip', 'nama_lengkap', 'office' => ['id_instansi', 'nama_instansi']],
                    'dataPegawaiEkinerja' => ['id_pegawai', 'nip', 'nama_lengkap', 'office' => ['id_instansi', 'nama_instansi']],
                ],
                'error',
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'user' => ['username' => 'testuser'],
                    'dataPegawaiSimpeg' => ['nip' => 'testuser', 'office' => ['id_instansi' => 'instansi-123']],
                    'dataPegawaiAbsen' => ['nip' => 'testuser', 'office' => ['id_instansi' => 'instansi-123']],
                    'dataPegawaiEkinerja' => ['nip' => 'testuser', 'office' => ['id_instansi' => 'instansi-123']],
                ],
            ]);
    }

    public function test_get_profile_partial_data()
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $officeSimpeg = DataOfficeSimpeg::factory()->create(['id_instansi' => 'instansi-123']);
        $simpeg = DataPegawaiSimpeg::factory()->create(['nip' => 'testuser', 'id_instansi' => 'instansi-123']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => ['username' => 'testuser'],
                    'dataPegawaiSimpeg' => ['nip' => 'testuser', 'office' => ['id_instansi' => 'instansi-123']],
                    'dataPegawaiAbsen' => null,
                    'dataPegawaiEkinerja' => null,
                ],
                'message' => 'Profile retrieved successfully',
            ]);
    }

    public function test_get_profile_no_office_data()
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $simpeg = DataPegawaiSimpeg::factory()->create(['nip' => 'testuser', 'id_instansi' => 'instansi-123']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => ['username' => 'testuser'],
                    'dataPegawaiSimpeg' => ['nip' => 'testuser', 'office' => null],
                    'dataPegawaiAbsen' => null,
                    'dataPegawaiEkinerja' => null,
                ],
                'message' => 'Profile retrieved successfully',
            ]);
    }

    public function test_get_profile_unauthenticated()
    {
        $response = $this->getJson('/api/profile/me');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthenticated',
                ],
            ]);
    }

    public function test_get_apps_success()
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $absen = UserAbsen::factory()->create(['name' => 'testuser']);
        $ekinerja = UserEkinerja::factory()->create(['UID' => 'testuser']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/apps');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'userAbsen' => ['id', 'name'],
                    'userEkinerja' => ['UID', 'nama'],
                ],
                'error',
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Apps data retrieved successfully',
                'data' => [
                    'userAbsen' => ['name' => 'testuser'],
                    'userEkinerja' => ['UID' => 'testuser'],
                ],
            ]);
    }

    public function test_get_apps_partial_data()
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $absen = UserAbsen::factory()->create(['name' => 'testuser']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/apps');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'userAbsen' => ['name' => 'testuser'],
                    'userEkinerja' => null,
                ],
                'message' => 'Apps data retrieved successfully',
            ]);
    }

    public function test_get_apps_unauthenticated()
    {
        $response = $this->getJson('/api/profile/apps');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthenticated',
                ],
            ]);
    }
}