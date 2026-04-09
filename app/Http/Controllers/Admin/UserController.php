<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    /**
     * Lista paginada de usuarios con filtros por rol y búsqueda.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('users.view'), 403);

        $query = User::with(['roles', 'route']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->input('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $roleFilter));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $roles = [
            'super_admin' => 'Super Admin',
            'admin'       => 'Admin',
            'manager'     => 'Manager',
            'contador'    => 'Contador',
            'conductor'   => 'Conductor',
        ];

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Formulario de creación de usuario.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->can('users.create'), 403);

        $roles  = Role::orderBy('name')->pluck('name', 'name');
        $routes = Route::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'routes'));
    }

    /**
     * Almacena un nuevo usuario.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.create'), 403);

        $data = $request->validated();

        $user = User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($data['password']),
            'phone'                => $data['phone'] ?? null,
            'is_active'            => $data['is_active'] ?? true,
            'must_change_password' => $data['must_change_password'] ?? true,
            'route_id'             => $data['role'] === 'conductor' ? ($data['route_id'] ?? null) : null,
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» creado correctamente.");
    }

    /**
     * Formulario de edición de usuario.
     */
    public function edit(User $user): View
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        $roles  = Role::orderBy('name')->pluck('name', 'name');
        $routes = Route::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'routes'));
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        $data = $request->validated();

        // Impedir que un super_admin se quite su propio rol
        if (
            $user->id === auth()->id()
            && $user->hasRole('super_admin')
            && $data['role'] !== 'super_admin'
        ) {
            return back()->with('error', 'No puedes quitarte el rol de Super Admin a ti mismo.');
        }

        $updateData = [
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'is_active'            => $data['is_active'] ?? $user->is_active,
            'must_change_password' => $data['must_change_password'] ?? $user->must_change_password,
            'route_id'             => $data['role'] === 'conductor' ? ($data['route_id'] ?? null) : null,
        ];

        // Solo actualizar contraseña si se envía
        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Sincronizar rol si cambió
        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» actualizado correctamente.");
    }

    /**
     * Desactiva un usuario (soft-disable). No borra de DB.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.delete'), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        $user->update(['is_active' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» desactivado correctamente.");
    }

    /**
     * Activa o desactiva un usuario (toggle).
     */
    public function toggle(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $estado = $user->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Usuario «{$user->name}» {$estado} correctamente.");
    }

    /**
     * Resetea la contraseña del usuario a una temporal y obliga el cambio.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->can('users.edit'), 403);

        $user->update([
            'password'             => Hash::make('Gacov2026!'),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Contraseña de «{$user->name}» restablecida. La contraseña temporal es: Gacov2026!");
    }
}
