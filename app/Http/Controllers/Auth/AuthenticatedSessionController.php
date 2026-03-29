<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\UserRedirector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
 
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return $this->createAdmin();
    }

    public function createAdmin()
    {
        return view('auth.login-admin');
    }

    public function createCustomer()
    {
        return view('auth.login-customer');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        return $this->authenticateCustomer($request);
    }

    public function authenticateCustomer(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        $user = Auth::user();

        if (!UserRedirector::isCustomer($user)) {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Este acceso es solo para clientes desde las landings.',
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        Auth::guard('web')->logout();
    
        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 200);
    }

    public function authenticate(Request $request): JsonResponse
    {
        return $this->authenticateAdmin($request);
    }

    public function authenticateAdmin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (!UserRedirector::canAccessBackoffice($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Este acceso es exclusivo para usuarios de panel administrativo.',
                'redirect_to' => '/',
            ], 403);
        }

        return response()->json([
            'user'  => $user,
            'redirect_to' => UserRedirector::resolveBackofficeRedirect($user),
        ], 200);
    }
    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Logged out successfully'], 200);
        }

        return redirect('/admin/login');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/admin/login');
    }

    /**
     * Get the user details from the JWT token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFromToken(Request $request)
    {
        try {
            // Intentar obtener el usuario autenticado desde el token
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
    
            return response()->json([
                'user' => $user
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }
    }
    public function registerEcomm(Request $request)
    {
        // Validación de los datos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'dni' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:50',
        ]);

        $dni = trim((string) $request->input('dni', ''));
        if ($dni === '') {
            $dni = 'CLI-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        }
    
        // Crear el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),  // Hashear la contraseña
            'role_id' => $this->resolveCustomerRoleId(),
            'dni' => $dni,
            'phone_number' => trim((string) $request->input('phone_number', '')) ?: null,
        ]);
    
        // Generar el token JWT para el usuario recién creado
        try {
            // Intentar generar el token para el usuario
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            // En caso de error al generar el token
            return response()->json(['error' => 'No se pudo crear el token'], 500);
        }
    
        // Responder con el usuario creado y el token JWT
        return response()->json([
            'message' => 'Usuario creado con éxito',
            'user' => $user,
            'token' => $token,  // El token JWT generado
        ], 201);
    }

    public function changeEcommPassword(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return response()->json(['message' => 'La contraseña actual no es correcta.'], 422);
        }

        $user->password = Hash::make((string) $validated['new_password']);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.'], 200);
    }

    public function updateEcommProfile(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $validated = $request->validate([
            'phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        $user->phone_number = trim((string) ($validated['phone_number'] ?? '')) ?: null;
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user,
        ], 200);
    }

    private function resolveCustomerRoleId(): int
    {
        $roleId = Role::query()
            ->whereRaw('LOWER(name) IN (?, ?, ?)', ['user', 'cliente', 'customer'])
            ->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        return (int) Role::query()->firstOrCreate(['name' => 'user'])->id;
    }

}
