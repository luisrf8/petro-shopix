<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\UserRedirector;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Role;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
 
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
            AuditLogger::logEvent('auth', 'CUSTOMER_LOGIN_FAILED', 'Intento fallido de inicio de sesión cliente.', null, [
                'email' => $credentials['email'] ?? null,
            ]);

            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        $user = Auth::user();

        if (!UserRedirector::isCustomer($user)) {
            AuditLogger::logEvent('auth', 'CUSTOMER_LOGIN_DENIED', 'Usuario no cliente intentó login de landing.', (int) ($user->id ?? 0), [
                'email' => $user->email ?? null,
                'role' => optional($user?->role)->name,
            ]);

            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Este acceso es solo para clientes desde las landings.',
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        AuditLogger::logEvent('auth', 'CUSTOMER_LOGIN_SUCCESS', 'Inicio de sesión cliente exitoso.', (int) ($user->id ?? 0), [
            'email' => $user->email ?? null,
            'role' => optional($user?->role)->name,
        ]);

        Auth::guard('web')->logout();
    
        return response()->json([
            'token' => $token,
            'user'  => $user,
            'redirect_to' => $this->sanitizeCustomerRedirect((string) $request->input('redirect', '/')),
        ], 200);
    }

    public function redirectToCustomerProvider(Request $request, string $provider)
    {
        if (! $this->isSupportedSocialProvider($provider)) {
            abort(404);
        }

        $redirectTo = $this->sanitizeCustomerRedirect((string) $request->query('redirect', '/'));

        if (! $this->isSocialProviderConfigured($provider)) {
            return $this->redirectCustomerAuthFailure(
                $redirectTo,
                'Configura las credenciales de ' . ($this->socialProviderMeta()[$provider]['label'] ?? Str::title($provider)) . ' para habilitar este acceso.'
            );
        }

        $request->session()->put('customer_social_redirect', $redirectTo);

        if ($provider === 'apple') {
            $state = Str::random(40);
            $nonce = Str::random(40);

            $request->session()->put('customer_social_apple_state', $state);
            $request->session()->put('customer_social_apple_nonce', $nonce);

            $query = http_build_query([
                'client_id' => (string) config('services.apple.client_id'),
                'redirect_uri' => (string) config('services.apple.redirect'),
                'response_type' => 'code',
                'response_mode' => 'query',
                'scope' => 'name email',
                'state' => $state,
                'nonce' => $nonce,
            ]);

            return redirect()->away('https://appleid.apple.com/auth/authorize?' . $query);
        }

        if ($provider === 'google') {
            $state = Str::random(40);
            $request->session()->put('customer_social_google_state', $state);

            $googleClient = $this->makeGoogleClient();
            $googleClient->setState($state);

            return redirect()->away($googleClient->createAuthUrl());
        }

        $state = Str::random(40);
        $request->session()->put('customer_social_facebook_state', $state);

        $query = http_build_query([
            'client_id' => (string) config('services.facebook.client_id'),
            'redirect_uri' => (string) config('services.facebook.redirect'),
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state,
        ]);

        return redirect()->away('https://www.facebook.com/v19.0/dialog/oauth?' . $query);
    }

    public function handleCustomerProviderCallback(Request $request, string $provider)
    {
        if (! $this->isSupportedSocialProvider($provider)) {
            abort(404);
        }

        $redirectTo = $this->sanitizeCustomerRedirect((string) $request->session()->pull('customer_social_redirect', $request->query('redirect', '/')));

        try {
            $socialProfile = match ($provider) {
                'google' => $this->resolveGoogleCustomerProfile($request),
                'facebook' => $this->resolveFacebookCustomerProfile($request),
                'apple' => $this->resolveAppleCustomerProfile($request),
                default => throw new \RuntimeException('Proveedor social no soportado.'),
            };

            $user = $this->resolveCustomerSocialUser($provider, $socialProfile);
            $token = JWTAuth::fromUser($user);

            AuditLogger::logEvent('auth', 'CUSTOMER_SOCIAL_LOGIN_SUCCESS', 'Inicio de sesión social cliente exitoso.', (int) ($user->id ?? 0), [
                'provider' => $provider,
                'email' => $user->email ?? null,
            ]);

            return response()->view('auth.social-login-callback', [
                'token' => $token,
                'user' => $user,
                'redirectTo' => $redirectTo,
                'providerLabel' => $this->socialProviderMeta()[$provider]['label'] ?? Str::title($provider),
            ]);
        } catch (\Throwable $exception) {
            AuditLogger::logEvent('auth', 'CUSTOMER_SOCIAL_LOGIN_FAILED', 'Falló el inicio de sesión social cliente.', null, [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return $this->redirectCustomerAuthFailure(
                $redirectTo,
                $exception->getMessage() ?: 'No fue posible iniciar sesión con ' . Str::title($provider) . '.'
            );
        }
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
            AuditLogger::logEvent('auth', 'ADMIN_LOGIN_FAILED', 'Intento fallido de login admin.', null, [
                'email' => $credentials['email'] ?? null,
            ]);

            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (!UserRedirector::canAccessBackoffice($user)) {
            AuditLogger::logEvent('auth', 'ADMIN_LOGIN_DENIED', 'Acceso a panel administrativo denegado por rol.', (int) ($user->id ?? 0), [
                'email' => $user->email ?? null,
                'role' => optional($user?->role)->name,
            ]);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Este acceso es exclusivo para usuarios de panel administrativo.',
                'redirect_to' => '/',
            ], 403);
        }

        AuditLogger::logEvent('auth', 'ADMIN_LOGIN_SUCCESS', 'Inicio de sesión administrativo exitoso.', (int) ($user->id ?? 0), [
            'email' => $user->email ?? null,
            'role' => optional($user?->role)->name,
        ]);

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
        $currentUser = Auth::guard('web')->user();
        if ($currentUser) {
            AuditLogger::logEvent('auth', 'LOGOUT', 'Cierre de sesión web.', (int) $currentUser->id, [
                'role' => optional($currentUser->role)->name,
            ]);
        }

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
        $currentUser = Auth::guard('web')->user();
        if ($currentUser) {
            AuditLogger::logEvent('auth', 'LOGOUT', 'Cierre de sesión administrativo.', (int) $currentUser->id, [
                'role' => optional($currentUser->role)->name,
            ]);
        }

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
            'phone_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'phone_number' => 'nullable|string|max:50',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $dni = trim((string) $request->input('dni', ''));
        if ($dni === '') {
            $dni = 'CLI-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        }

        $phoneNumber = $this->normalizeCustomerPhone(
            (string) $request->input('phone_number', ''),
            $request->input('phone_code')
        );
    
        // Crear el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),  // Hashear la contraseña
            'role_id' => $this->resolveCustomerRoleId(),
            'dni' => $dni,
            'phone_number' => $phoneNumber,
            'country_id' => $request->input('country_id') ?: null,
            'state_id' => $request->input('state_id') ?: null,
            'city_id' => $request->input('city_id') ?: null,
            'address' => trim((string) $request->input('address', '')) ?: null,
            'latitude' => $request->filled('latitude') ? (float) $request->input('latitude') : null,
            'longitude' => $request->filled('longitude') ? (float) $request->input('longitude') : null,
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
            'phone_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $user->phone_number = $this->normalizeCustomerPhone(
            (string) ($validated['phone_number'] ?? ''),
            $validated['phone_code'] ?? null
        );
        $user->country_id = !empty($validated['country_id']) ? (int) $validated['country_id'] : null;
        $user->state_id = !empty($validated['state_id']) ? (int) $validated['state_id'] : null;
        $user->city_id = !empty($validated['city_id']) ? (int) $validated['city_id'] : null;
        $user->address = trim((string) ($validated['address'] ?? '')) ?: null;
        $user->latitude = array_key_exists('latitude', $validated) && $validated['latitude'] !== null
            ? (float) $validated['latitude']
            : null;
        $user->longitude = array_key_exists('longitude', $validated) && $validated['longitude'] !== null
            ? (float) $validated['longitude']
            : null;
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user,
        ], 200);
    }

    private function normalizeCustomerPhone(string $phoneNumber, mixed $phoneCode = null): ?string
    {
        $rawPhone = trim($phoneNumber);
        if ($rawPhone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $rawPhone);
        if ($digits === '') {
            return null;
        }

        if (Str::startsWith($rawPhone, '+')) {
            return '+' . $digits;
        }

        $rawCode = trim((string) ($phoneCode ?? ''));
        $codeDigits = preg_replace('/\D+/', '', $rawCode);
        if ($codeDigits !== '') {
            return '+' . $codeDigits . $digits;
        }

        return $digits;
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

    private function socialProviderMeta(): array
    {
        return [
            'google' => [
                'label' => 'Google',
                'icon' => 'bi bi-google',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'icon' => 'bi bi-facebook',
            ],
            'apple' => [
                'label' => 'Apple',
                'icon' => 'bi bi-apple',
            ],
        ];
    }

    private function isSupportedSocialProvider(string $provider): bool
    {
        return array_key_exists($provider, $this->socialProviderMeta());
    }

    private function isSocialProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'google', 'facebook' => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret"))
                && filled(config("services.{$provider}.redirect")),
            'apple' => filled(config('services.apple.client_id'))
                && filled(config('services.apple.client_secret'))
                && filled(config('services.apple.redirect')),
            default => false,
        };
    }

    private function sanitizeCustomerRedirect(string $redirectTo): string
    {
        $redirectTo = trim($redirectTo);

        if ($redirectTo === '') {
            return '/';
        }

        if (Str::startsWith($redirectTo, ['http://', 'https://'])) {
            $appUrl = (string) config('app.url');

            if ($appUrl !== '' && Str::startsWith($redirectTo, rtrim($appUrl, '/'))) {
                $parsedPath = parse_url($redirectTo, PHP_URL_PATH) ?: '/';
                $parsedQuery = parse_url($redirectTo, PHP_URL_QUERY);

                return $parsedQuery ? $parsedPath . '?' . $parsedQuery : $parsedPath;
            }

            return '/';
        }

        if (! Str::startsWith($redirectTo, '/')) {
            $redirectTo = '/' . ltrim($redirectTo, '/');
        }

        if (Str::startsWith($redirectTo, ['/admin', '/dashboard', '/plans', '/notifications'])) {
            return '/';
        }

        return $redirectTo;
    }

    private function redirectCustomerAuthFailure(string $redirectTo, string $message): RedirectResponse
    {
        $redirectTo = $this->sanitizeCustomerRedirect($redirectTo);
        $separator = str_contains($redirectTo, '?') ? '&' : '?';
        $target = $redirectTo . $separator . 'shopix_auth_error=' . urlencode($message) . '#top';

        return redirect($target);
    }

    private function makeGoogleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('services.google.client_id'));
        $client->setClientSecret((string) config('services.google.client_secret'));
        $client->setRedirectUri((string) config('services.google.redirect'));
        $client->setScopes(['openid', 'email', 'profile']);
        $client->setAccessType('online');
        $client->setPrompt('select_account');

        return $client;
    }

    private function resolveGoogleCustomerProfile(Request $request): array
    {
        $expectedState = (string) $request->session()->pull('customer_social_google_state', '');
        $receivedState = trim((string) $request->query('state', ''));

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            throw new \RuntimeException('La respuesta de Google no pasó la validación de seguridad.');
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            throw new \RuntimeException('Google no devolvió el código de autorización.');
        }

        $googleClient = $this->makeGoogleClient();
        $tokenData = $googleClient->fetchAccessTokenWithAuthCode($code);

        if (! is_array($tokenData) || ! empty($tokenData['error'])) {
            throw new \RuntimeException('No fue posible validar el acceso con Google.');
        }

        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('Google no devolvió un token de acceso válido.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (! $profileResponse->successful()) {
            throw new \RuntimeException('No fue posible obtener el perfil de Google.');
        }

        $profile = $profileResponse->json();

        return [
            'id' => trim((string) data_get($profile, 'sub', '')),
            'name' => trim((string) data_get($profile, 'name', '')),
            'email' => trim((string) data_get($profile, 'email', '')),
            'avatar' => data_get($profile, 'picture'),
        ];
    }

    private function resolveFacebookCustomerProfile(Request $request): array
    {
        $expectedState = (string) $request->session()->pull('customer_social_facebook_state', '');
        $receivedState = trim((string) $request->query('state', ''));

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            throw new \RuntimeException('La respuesta de Facebook no pasó la validación de seguridad.');
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            throw new \RuntimeException('Facebook no devolvió el código de autorización.');
        }

        $tokenResponse = Http::acceptJson()->get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => (string) config('services.facebook.client_id'),
            'client_secret' => (string) config('services.facebook.client_secret'),
            'redirect_uri' => (string) config('services.facebook.redirect'),
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            throw new \RuntimeException('No fue posible validar el acceso con Facebook.');
        }

        $accessToken = (string) data_get($tokenResponse->json(), 'access_token', '');
        if ($accessToken === '') {
            throw new \RuntimeException('Facebook no devolvió un token de acceso válido.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email,picture.width(256).height(256)',
            ]);

        if (! $profileResponse->successful()) {
            throw new \RuntimeException('No fue posible obtener el perfil de Facebook.');
        }

        $profile = $profileResponse->json();

        return [
            'id' => trim((string) data_get($profile, 'id', '')),
            'name' => trim((string) data_get($profile, 'name', '')),
            'email' => trim((string) data_get($profile, 'email', '')),
            'avatar' => data_get($profile, 'picture.data.url'),
        ];
    }

    private function resolveAppleCustomerProfile(Request $request): array
    {
        $expectedState = (string) $request->session()->pull('customer_social_apple_state', '');
        $expectedNonce = (string) $request->session()->pull('customer_social_apple_nonce', '');
        $receivedState = trim((string) $request->query('state', ''));

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            throw new \RuntimeException('La respuesta de Apple no pasó la validación de seguridad.');
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            throw new \RuntimeException('Apple no devolvió el código de autorización.');
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post('https://appleid.apple.com/auth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => (string) config('services.apple.client_id'),
                'client_secret' => (string) config('services.apple.client_secret'),
                'redirect_uri' => (string) config('services.apple.redirect'),
            ]);

        if (! $tokenResponse->successful()) {
            throw new \RuntimeException('Apple rechazó la validación del inicio de sesión.');
        }

        $idToken = (string) data_get($tokenResponse->json(), 'id_token', '');
        if ($idToken === '') {
            throw new \RuntimeException('Apple no devolvió el token de identidad.');
        }

        $claims = $this->decodeJwtPayload($idToken);
        if (($claims['iss'] ?? null) !== 'https://appleid.apple.com') {
            throw new \RuntimeException('El token de Apple no es válido para esta aplicación.');
        }

        if (($claims['aud'] ?? null) !== (string) config('services.apple.client_id')) {
            throw new \RuntimeException('El token de Apple no coincide con el cliente configurado.');
        }

        if (! empty($expectedNonce) && ! empty($claims['nonce']) && ! hash_equals($expectedNonce, (string) $claims['nonce'])) {
            throw new \RuntimeException('La validación del token de Apple falló.');
        }

        if ((int) ($claims['exp'] ?? 0) > 0 && (int) $claims['exp'] < time()) {
            throw new \RuntimeException('El token de Apple ya expiró.');
        }

        return [
            'id' => (string) ($claims['sub'] ?? ''),
            'name' => 'Cliente Apple',
            'email' => trim((string) ($claims['email'] ?? '')),
            'avatar' => null,
        ];
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $segments = explode('.', $jwt);
        if (count($segments) < 2) {
            throw new \RuntimeException('El token recibido no es válido.');
        }

        $payload = json_decode($this->base64UrlDecode($segments[1]), true);

        if (! is_array($payload)) {
            throw new \RuntimeException('No fue posible leer los datos del proveedor social.');
        }

        return $payload;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }

    private function resolveCustomerSocialUser(string $provider, array $socialProfile): User
    {
        $providerColumn = $this->providerColumn($provider);
        $providerId = trim((string) ($socialProfile['id'] ?? ''));

        if ($providerId === '') {
            throw new \RuntimeException('El proveedor no devolvió un identificador válido.');
        }

        $email = $this->normalizeSocialEmail($provider, $providerId, (string) ($socialProfile['email'] ?? ''));

        $user = User::query()
            ->where($providerColumn, $providerId)
            ->first();

        if (! $user && $email !== '') {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->first();
        }

        $name = trim((string) ($socialProfile['name'] ?? '')) ?: 'Cliente ' . ($this->socialProviderMeta()[$provider]['label'] ?? Str::title($provider));
        $avatar = trim((string) ($socialProfile['avatar'] ?? '')) ?: null;

        if ($user) {
            if (! UserRedirector::isCustomer($user)) {
                throw new \RuntimeException('Ese correo ya pertenece a una cuenta administrativa. Usa otro método de acceso.');
            }

            $user->{$providerColumn} = $providerId;
            $user->name = $user->name ?: $name;
            $user->avatar = $avatar ?: $user->avatar;
            if (empty($user->email)) {
                $user->email = $email;
            }
            if (empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
            $user->save();

            return $user;
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'role_id' => $this->resolveCustomerRoleId(),
            'dni' => 'SOC-' . strtoupper($provider) . '-' . Str::upper(Str::random(8)),
            'email_verified_at' => now(),
            $providerColumn => $providerId,
            'avatar' => $avatar,
        ]);
    }

    private function normalizeSocialEmail(string $provider, string $providerId, string $email): string
    {
        $email = trim(Str::lower($email));

        if ($email !== '') {
            return $email;
        }

        return $provider . '-' . $providerId . '@social.shopix.local';
    }

    private function providerColumn(string $provider): string
    {
        return match ($provider) {
            'google' => 'google_id',
            'facebook' => 'facebook_id',
            'apple' => 'apple_id',
            default => throw new \RuntimeException('Proveedor social no soportado.'),
        };
    }

}
