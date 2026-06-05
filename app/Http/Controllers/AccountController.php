<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'All') {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $roles = Role::whereIn('name', ['admin', 'pimpinan'])->pluck('name');

        return view('pages.admin.accounts', compact('users', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($request->role);

            return back()->with([
                'message' => 'Akun pengguna berhasil ditambahkan!',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Account Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat menyimpan akun.',
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountRequest $request, User $user)
    {
        try {
            $currentUser = auth()->user();

            // Self-edit restriction: Admin cannot change their own role
            $role = $request->role;
            if ($user->id === $currentUser->id) {
                $role = $currentUser->roles->first()?->name;
            }

            // Minimum 1 admin verification:
            // If the user being edited is currently an admin, and the target role is NOT admin (e.g. pimpinan),
            // we must check if there is at least one other admin in the system.
            if ($user->hasRole('admin') && $role !== 'admin') {
                $adminCount = User::role('admin')->count();
                if ($adminCount <= 1) {
                    return back()->with([
                        'status' => 'destructive',
                        'message' => 'Gagal mengubah role! Harus ada minimal 1 akun Administrator di sistem.',
                    ]);
                }
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // Sync role (skip if it was forced to remain current due to self-edit check)
            if ($role) {
                $user->syncRoles([$role]);
            }

            return back()->with([
                'message' => 'Data akun berhasil diperbarui!',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Account Update Error: '.$e->getMessage(), [
                'id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat memperbarui akun.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $currentUser = auth()->user();

            // Prevent self deletion
            if ($user->id === $currentUser->id) {
                return back()->with([
                    'status' => 'destructive',
                    'message' => 'Gagal menghapus! Anda tidak bisa menghapus akun Anda sendiri.',
                ]);
            }

            // Prevent deleting the last admin
            if ($user->hasRole('admin')) {
                $adminCount = User::role('admin')->count();
                if ($adminCount <= 1) {
                    return back()->with([
                        'status' => 'destructive',
                        'message' => 'Gagal menghapus! Harus ada minimal 1 akun Administrator di sistem.',
                    ]);
                }
            }

            $name = $user->name;
            $user->delete();

            return back()->with([
                'status' => 'success',
                'message' => "Akun {$name} berhasil dihapus.",
            ]);
        } catch (\Exception $e) {
            Log::error('Account Delete Error: '.$e->getMessage(), [
                'id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat menghapus akun.',
            ]);
        }
    }

    /**
     * Admin resets user password.
     */
    public function resetPassword(ResetPasswordRequest $request, User $user)
    {
        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with([
                'message' => "Password untuk {$user->name} berhasil di-reset!",
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Account Reset Password Error: '.$e->getMessage(), [
                'id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat mereset password.',
            ]);
        }
    }

    /**
     * User changes own password (self-service).
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = auth()->user();
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with([
                'message' => 'Password Anda berhasil diubah!',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Account Change Password Error: '.$e->getMessage(), [
                'id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat mengubah password.',
            ]);
        }
    }
}
