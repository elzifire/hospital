<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * CRUD RoleController — terutama pemutakhiran permission role lewat
 * form Edit Role (syncPermissions), pembersihan permission saat semua
 * checkbox dilepas, dan render halaman edit yang memuat permission milik
 * role untuk dijadikan kondisi awal checkbox.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    #[Test]
    public function update_permission_di_role_tersimpan(): void
    {
        $role = Role::create(['name' => 'role-uji']);
        $role->syncPermissions(['manage pnpp', 'manage satker']);

        $this->actingAs($this->superadmin());

        $this->put(route('admin.roles.update', $role->id), [
            'name' => 'role-uji',
            'permissions' => ['view dashboard', 'manage pnpp'],
        ])->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertSame(['manage pnpp', 'view dashboard'], $role->permissions()->orderBy('name')->pluck('name')->all());
    }

    #[Test]
    public function update_tanpa_permission_mengosongkan_role(): void
    {
        $role = Role::create(['name' => 'role-uji-2']);
        $role->syncPermissions(['manage pnpp']);

        $this->actingAs($this->superadmin());

        $this->put(route('admin.roles.update', $role->id), ['name' => 'role-uji-2']);

        $role->refresh();
        $this->assertEmpty($role->permissions()->pluck('name')->all());
    }

    #[Test]
    public function store_dengan_permission_bekerja(): void
    {
        $this->actingAs($this->superadmin());

        $this->post(route('admin.roles.store'), [
            'name' => 'role-store-uji',
            'permissions' => ['manage pnpp', 'manage poli'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'role-store-uji')->firstOrFail();
        $this->assertSame(['manage pnpp', 'manage poli'], $role->permissions()->orderBy('name')->pluck('name')->all());
    }

    #[Test]
    public function permission_ganda_yang_dikirim_tidak_berduplikat(): void
    {
        $role = Role::create(['name' => 'role-uji-3']);

        $this->actingAs($this->superadmin());

        $this->put(route('admin.roles.update', $role->id), [
            'name' => 'role-uji-3',
            'permissions' => ['manage pnpp', 'manage pnpp'],
        ])->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertSame(['manage pnpp'], $role->permissions()->pluck('name')->all());
    }

    #[Test]
    public function halaman_edit_role_merender_permission_sebagai_checkbox_berchecked(): void
    {
        $role = Role::create(['name' => 'role-uji-render']);
        $role->syncPermissions(['manage pnpp', 'manage satker']);

        $this->actingAs($this->superadmin());

        $res = $this->get(route('admin.roles.edit', $role->id));
        $res->assertOk();
        $res->assertSee('manage pnpp', false);
        $res->assertSee('checked', false);
    }
}
