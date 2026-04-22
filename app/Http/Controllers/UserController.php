<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionReason;

class UserController extends Controller
{
    /**
     * Mostrar la lista de usuarios.
     */
    public function index()
    {
        $users = User::with('role')->paginate(10); ;
        $roles = Role::all();
        return view('users', compact('users', 'roles'));
    }

    /**
     * Mostrar el formulario para crear un usuario.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Guardar un nuevo usuario.
     */
    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'phone_number' => 'required|string|max:20',
            'dni' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone_number' => $request->phone_number,
            'dni' => trim((string) $request->dni),
        ]);

        // return redirect()->route('users')->with('success', 'Usuario creado correctamente.');
        return response()->json(['message' => 'Usuario creado correctamente.'], 201);

    }

    /**
     * Mostrar el formulario para editar un usuario.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = User::findOrFail($id);
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role_id' => 'required|exists:roles,id',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        // Actualizar datos básicos del usuario
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Activar o inactivar un usuario.
     */
    public function toggleStatus(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $user = User::findOrFail($id);
        $reason = null;
        if ((bool) $user->is_active) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar el usuario.');
        }

        $user->is_active = !$user->is_active; // Cambia el estado
        $user->save();

        if (!(bool) $user->is_active) {
            ActionReason::log('users', 'USER_DEACTIVATED', (string) $reason, [
                'affected_user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);
        }

        return response()->json(['status' => 'success', 'new_status' => $user->is_active], 200);
    
    }
}
