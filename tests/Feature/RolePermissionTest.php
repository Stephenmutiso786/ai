<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hod_can_view_dashboard_but_cannot_manage_users(): void
    {
        $user = User::create([
            'name' => 'HOD User',
            'email' => 'hod@example.test',
            'password' => Hash::make('password'),
            'role' => 'hod',
            'kyc_status' => 'verified',
        ]);

        $this->actingAs($user)
            ->get(route('admin.control-center'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_client_cannot_access_any_admin_page(): void
    {
        $user = User::create([
            'name' => 'Client User',
            'email' => 'client@example.test',
            'password' => Hash::make('password'),
            'role' => 'client',
            'kyc_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('admin.control-center'))
            ->assertForbidden();
    }

    public function test_super_admin_can_still_access_user_management(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_super_admin' => true,
            'kyc_status' => 'verified',
        ]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_cannot_grant_super_admin_access(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'kyc_status' => 'verified',
        ]);
        $target = User::create([
            'name' => 'Target User',
            'email' => 'target@example.test',
            'password' => Hash::make('password'),
            'role' => 'client',
            'kyc_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'role' => 'super_admin',
                'is_super_admin' => '1',
                'kyc_status' => 'verified',
            ])
            ->assertForbidden();
    }
}
