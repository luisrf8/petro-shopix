<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
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
        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // Obtener las credenciales desde el request
        $credentials = $request->only('email', 'password');
    
        // Intentar la autenticación tradicional
        $request->authenticate();
    
        // Ya no usamos sesiones, así que no regeneramos
    
        // Obtener el usuario autenticado
        $user = Auth::user();
        // Generar el token usando el usuario autenticado
        $token = JWTAuth::fromUser($user, ['custom_claim' => 'value']);
    
        // Retornar el token y la información del usuario
        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 200);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
 
            return response()->json([
                'user'  => $user,
            ], 200);
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
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

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        // return redirect('/');
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
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
            'role_id' => 3,  // Puedes asignar un rol por defecto o según tus necesidades
            'dni' => $dni,
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
}
