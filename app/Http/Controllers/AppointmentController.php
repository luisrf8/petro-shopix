<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConsumption;
use App\Models\AppointmentService;
use App\Models\AppointmentPackage;
use App\Models\AppointmentPackageSession;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\UserScheduleRule;
use App\Support\TenantCurrency;
use App\Support\WorkflowNotifier;
use App\Support\TenantPlanCapabilities;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->findOrFail((int) $user->tenant_id);
        $planCapabilities = TenantPlanCapabilities::forTenant($tenant);

        if (!$planCapabilities->canAppointments()) {
            return redirect()->route('dashboard')->with('warning', 'El módulo de citas está disponible a partir del plan Pro.');
        }

        $selectedDate = $this->resolveSelectedDate($request->query('date'));
        $selectedUserId = (int) $request->query('user_id', 0);
        $serviceBusinessType = Str::lower((string) ($tenant->business_type ?? '')) === 'servicio';
        $calendarWeekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $calendarWeekEnd = $calendarWeekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $professionals = $this->appointmentUsersQuery((int) $tenant->id)->get();
        $calendarProfessionals = $selectedUserId > 0
            ? $professionals->where('id', $selectedUserId)->values()
            : $professionals->values();

        $serviceVariants = $this->serviceVariantsQuery((int) $tenant->id)->get();
        $consumableVariants = $this->consumableVariantsQuery((int) $tenant->id)->get();
        $services = AppointmentService::query()
            ->with(['assignedUser', 'productVariant.product'])
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('name')
            ->get();
        $paymentMethods = PaymentMethod::query()
            ->with('currency')
            ->where('tenant_id', (int) $tenant->id)
            ->active()
            ->orderBy('name')
            ->get();
        $customers = $this->customerUsersQuery((int) $tenant->id)->get(['id', 'name', 'phone_number', 'email', 'dni']);
        $scheduleRules = UserScheduleRule::query()
            ->with('user')
            ->where('tenant_id', (int) $tenant->id)
            ->where('is_active', true)
            ->orderBy('user_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $dayAppointments = Appointment::query()
            ->with(['service', 'assignedUser', 'customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->whereDate('starts_at', $selectedDate->toDateString())
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->orderBy('starts_at')
            ->get();

        $upcomingAppointments = Appointment::query()
            ->with(['service.productVariant.product', 'assignedUser', 'customer', 'consumptions.variant.product', 'paymentMethod.currency'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $calendarAppointments = Appointment::query()
            ->with(['service.productVariant.product', 'assignedUser', 'customer', 'consumptions.variant.product', 'paymentMethod.currency'])
            ->where('tenant_id', (int) $tenant->id)
            ->whereBetween('starts_at', [$calendarWeekStart->copy()->startOfDay(), $calendarWeekEnd->copy()->endOfDay()])
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->orderBy('starts_at')
            ->get();

        $calendarDays = collect(range(0, 6))->map(function (int $offset) use ($calendarWeekStart) {
            $day = $calendarWeekStart->copy()->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'label' => Str::ucfirst($day->translatedFormat('D d M')),
                'day_name' => Str::ucfirst($day->translatedFormat('l')),
                'is_today' => $day->isToday(),
            ];
        })->values();

        $calendarBounds = $this->resolveCalendarBounds($scheduleRules, $calendarProfessionals->pluck('id')->all());
        $calendarEvents = $this->buildCalendarEvents($calendarAppointments, $calendarBounds['startHour']);
        $bsRate = TenantCurrency::resolveRateToBs((int) $tenant->id, 'USD');
        $servicesPayload = $services->map(function (AppointmentService $service) {
            return [
                'id' => (int) $service->id,
                'name' => (string) $service->display_name,
                'duration_minutes' => (int) ($service->duration_minutes ?? 60),
                'buffer_minutes' => (int) ($service->buffer_minutes ?? 0),
                'price' => (float) ($service->price ?? 0),
                'assigned_user_id' => $service->user_id ? (int) $service->user_id : null,
                'product_variant_id' => $service->product_variant_id ? (int) $service->product_variant_id : null,
                'product_label' => $service->productVariant && $service->productVariant->product
                    ? trim(($service->productVariant->product->name ?? 'Servicio') . ' · ' . ($service->productVariant->size ?? ''))
                    : null,
                'color_hex' => $service->color_hex ?: '#0f172a',
            ];
        })->values();

        $consumableVariantsPayload = $consumableVariants->map(function (ProductVariant $variant) {
            return [
                'id' => (int) $variant->id,
                'label' => trim(($variant->product->name ?? 'Consumible') . ' · ' . ($variant->size ?? '')),
                'stock' => (float) ($variant->stock ?? 0),
                'unit_cost' => (float) ($variant->effective_price ?? $variant->price ?? 0),
            ];
        })->values();

        return view('appointments.index', compact(
            'tenant',
            'planCapabilities',
            'selectedDate',
            'selectedUserId',
            'professionals',
            'calendarProfessionals',
            'services',
            'servicesPayload',
            'serviceVariants',
            'consumableVariants',
            'consumableVariantsPayload',
            'paymentMethods',
            'customers',
            'scheduleRules',
            'dayAppointments',
            'upcomingAppointments',
            'serviceBusinessType',
            'calendarWeekStart',
            'calendarWeekEnd',
            'calendarDays',
            'calendarBounds',
            'calendarEvents',
            'bsRate'
        ));
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();
        $service = AppointmentService::query()->where('tenant_id', $tenantId)->whereKey((int) $validated['service_id'])->firstOrFail();
        $selectedDate = Carbon::parse($validated['date']);

        return response()->json([
            'success' => true,
            'slots' => $this->buildAvailableSlots($tenantId, $targetUser, $service, $selectedDate),
        ]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'user_id' => ['nullable', 'integer'],
            'product_variant_id' => ['required', 'integer'],
        ]);

        $assignedUserId = !empty($validated['user_id'])
            ? (int) $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->value('id')
            : null;

        $productVariant = $this->serviceVariantsQuery($tenantId)->whereKey((int) $validated['product_variant_id'])->firstOrFail();
        $resolvedPrice = isset($validated['price'])
            ? (float) $validated['price']
            : (float) ($productVariant->effective_price ?? $productVariant->price ?? 0);

        AppointmentService::create([
            'tenant_id' => $tenantId,
            'user_id' => $assignedUserId,
            'product_variant_id' => (int) $productVariant->id,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'buffer_minutes' => (int) ($validated['buffer_minutes'] ?? 0),
            'price' => $resolvedPrice,
            'color_hex' => $validated['color_hex'] ?? '#0f172a',
            'is_active' => true,
        ]);

        return back()->with('success', 'Servicio para citas creado correctamente.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'day_of_week' => ['nullable', 'integer', Rule::in(array_keys(UserScheduleRule::WEEK_DAYS))],
            'day_of_weeks' => ['nullable', 'array'],
            'day_of_weeks.*' => ['integer', Rule::in(array_keys(UserScheduleRule::WEEK_DAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();

        $selectedDays = collect($validated['day_of_weeks'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => in_array($day, array_keys(UserScheduleRule::WEEK_DAYS), true));

        if ($selectedDays->isEmpty() && isset($validated['day_of_week'])) {
            $selectedDays->push((int) $validated['day_of_week']);
        }

        $selectedDays = $selectedDays->unique()->values();

        if ($selectedDays->isEmpty()) {
            return back()->withErrors([
                'day_of_weeks' => 'Selecciona al menos un día para configurar el turno.',
            ])->withInput();
        }

        foreach ($selectedDays as $dayOfWeek) {
            UserScheduleRule::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => (int) $targetUser->id,
                    'day_of_week' => (int) $dayOfWeek,
                ],
                [
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'slot_interval_minutes' => (int) ($validated['slot_interval_minutes'] ?? 60),
                    'is_active' => true,
                ]
            );
        }

        $daysCount = $selectedDays->count();

        return back()->with('success', $daysCount > 1
            ? 'Turnos guardados correctamente para ' . $daysCount . ' días.'
            : 'Horario asignado correctamente.');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $validated = $request->validate([
            'appointment_service_id' => ['required', 'integer'],
            'appointment_id' => ['nullable', 'integer'],
            'user_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'customer_id' => ['nullable', 'integer'],
            'create_customer' => ['nullable', 'boolean'],
            'contact_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_phone_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_dni' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUSES))],
            'payment_method_id' => ['nullable', 'integer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', Rule::in(array_keys(Appointment::PAYMENT_STATUSES))],
            'consumptions' => ['nullable', 'array'],
            'consumptions.*.variant_id' => ['nullable', 'integer'],
            'consumptions.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();
        $service = AppointmentService::query()->with(['productVariant.product'])->where('tenant_id', $tenantId)->whereKey((int) $validated['appointment_service_id'])->firstOrFail();
        $editingAppointment = null;

        if (!empty($validated['appointment_id'])) {
            $editingAppointment = Appointment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey((int) $validated['appointment_id'])
                ->firstOrFail();
        }

        if ($service->user_id && (int) $service->user_id !== (int) $targetUser->id) {
            return back()->withErrors(['user_id' => 'Este servicio está asignado a otro profesional.'])->withInput();
        }

        $selectedDate = Carbon::parse($validated['scheduled_date']);
        $startAt = Carbon::parse($selectedDate->toDateString() . ' ' . $validated['start_time']);
        $endAt = (clone $startAt)->addMinutes((int) $service->duration_minutes);

        $availableSlots = collect($this->buildAvailableSlots(
            $tenantId,
            $targetUser,
            $service,
            $selectedDate,
            $editingAppointment?->id ? (int) $editingAppointment->id : null
        ));
        if (!$availableSlots->firstWhere('start', $startAt->format('H:i'))) {
            return back()->withErrors(['start_time' => 'La hora seleccionada no está disponible para ese profesional.'])->withInput();
        }

        $normalizedContactPhone = $this->normalizePhoneWithCode(
            (string) ($validated['contact_phone'] ?? ''),
            $validated['contact_phone_code'] ?? null
        );

        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customerId = (int) User::query()->where('tenant_id', $tenantId)->whereKey((int) $validated['customer_id'])->value('id');
        }

        $shouldCreateCustomer = (bool) ($validated['create_customer'] ?? false);
        if (!$customerId && $shouldCreateCustomer) {
            $customerName = trim((string) ($validated['contact_name'] ?? ''));
            if ($customerName === '') {
                return back()->withErrors(['contact_name' => 'Indica el nombre para registrar el nuevo cliente.'])->withInput();
            }

            $providedEmail = trim((string) ($validated['customer_email'] ?? ''));
            $customerEmail = $providedEmail;

            if ($customerEmail !== '') {
                $existingByEmail = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($customerEmail)])->first();
                if ($existingByEmail && (int) ($existingByEmail->tenant_id ?? 0) !== $tenantId) {
                    return back()->withErrors(['customer_email' => 'El correo ya está registrado en otra tienda.'])->withInput();
                }

                if ($existingByEmail && (int) ($existingByEmail->tenant_id ?? 0) === $tenantId) {
                    $customerId = (int) $existingByEmail->id;
                }
            }

            if (!$customerId) {
                if ($customerEmail === '') {
                    $customerEmail = 'cliente.' . $tenantId . '.' . now()->format('YmdHis') . '.' . random_int(100, 999) . '@shopix.local';
                }

                $customerRoleId = $this->resolveCustomerRoleId();
                $newCustomer = User::query()->create([
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'password' => Hash::make(Str::random(32)),
                    'role_id' => $customerRoleId,
                    'tenant_id' => $tenantId,
                    'dni' => trim((string) ($validated['customer_dni'] ?? '')) ?: null,
                    'phone_number' => $normalizedContactPhone,
                ]);

                $customerId = (int) $newCustomer->id;
            }
        }

        $paymentMethod = null;
        if (!empty($validated['payment_method_id'])) {
            $paymentMethod = PaymentMethod::query()
                ->with('currency')
                ->where('tenant_id', $tenantId)
                ->active()
                ->whereKey((int) $validated['payment_method_id'])
                ->firstOrFail();
        }

        $paidAmount = isset($validated['paid_amount']) ? (float) $validated['paid_amount'] : null;
        $paymentStatus = $validated['payment_status'] ?? ($paidAmount > 0 ? 'paid' : 'pending');
        $paymentReference = trim((string) ($validated['payment_reference'] ?? '')) ?: null;

        if ($paidAmount && !$paymentMethod) {
            return back()->withErrors(['payment_method_id' => 'Debes seleccionar un método de pago para registrar el cobro.'])->withInput();
        }

        if ($paymentMethod && $paymentMethod->usesReference() && $paidAmount > 0 && !$paymentReference) {
            return back()->withErrors(['payment_reference' => 'Este método de pago requiere referencia.'])->withInput();
        }

        $consumptions = collect($validated['consumptions'] ?? [])
            ->filter(fn ($item) => !empty($item['variant_id']) && !empty($item['quantity']))
            ->values();

        DB::transaction(function () use ($tenantId, $service, $targetUser, $customerId, $validated, $startAt, $endAt, $paymentMethod, $paidAmount, $paymentStatus, $paymentReference, $consumptions, $editingAppointment) {
            $appointmentPayload = [
                'tenant_id' => $tenantId,
                'appointment_service_id' => (int) $service->id,
                'user_id' => (int) $targetUser->id,
                'customer_id' => $customerId,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $normalizedContactPhone,
                'starts_at' => $startAt,
                'ends_at' => $endAt,
                'status' => $validated['status'] ?? 'scheduled',
                'payment_method_id' => $paymentMethod?->id,
                'paid_amount' => $paidAmount,
                'payment_currency' => $paymentMethod?->currency?->code,
                'payment_reference' => $paymentReference,
                'payment_status' => $paymentStatus,
                'source' => 'admin',
                'notes' => $validated['notes'] ?? null,
            ];

            if ($editingAppointment) {
                $editingAppointment->fill($appointmentPayload);
                $editingAppointment->save();
                $editingAppointment->consumptions()->delete();
                $appointment = $editingAppointment;
            } else {
                $appointment = Appointment::create($appointmentPayload);
            }

            foreach ($consumptions as $item) {
                $variant = $this->consumableVariantsQuery($tenantId)->whereKey((int) $item['variant_id'])->firstOrFail();
                $quantity = round((float) $item['quantity'], 2);
                $unitCost = (float) ($variant->effective_price ?? $variant->price ?? 0);

                AppointmentConsumption::create([
                    'tenant_id' => $tenantId,
                    'appointment_id' => (int) $appointment->id,
                    'product_variant_id' => (int) $variant->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'amount' => round($quantity * $unitCost, 2),
                ]);
            }
        });

        $message = $editingAppointment
            ? 'Cita actualizada correctamente.'
            : 'Cita registrada correctamente.';

        return redirect()->route('appointments.index', ['date' => $selectedDate->toDateString()])->with('success', $message);
    }

    public function workflowAction(Request $request, Appointment $appointment): JsonResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        if ((int) $appointment->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'La cita no pertenece a esta tienda.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['call_customer', 'confirm_attendance', 'cancel', 'no_show', 'reschedule', 'confirm_payment'])],
            'scheduled_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'payment_method_id' => ['nullable', 'integer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'create_sale' => ['nullable', 'boolean'],
        ]);

        $actor = auth()->user();
        $result = $this->applyWorkflowAction($appointment, $validated, $actor, false);

        return response()->json($result);
    }

    public function myAppointments(Request $request): JsonResponse
    {
        $customerId = (int) (auth()->id() ?? 0);

        $appointments = Appointment::query()
            ->with(['service', 'assignedUser', 'paymentMethod.currency', 'salesOrder'])
            ->where('customer_id', $customerId)
            ->orderByDesc('starts_at')
            ->limit(80)
            ->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments->map(function (Appointment $appointment) {
                return [
                    'id' => (int) $appointment->id,
                    'tenant_id' => (int) $appointment->tenant_id,
                    'service' => (string) ($appointment->service->display_name ?? $appointment->service->name ?? 'Servicio'),
                    'professional' => (string) ($appointment->assignedUser->name ?? 'Profesional'),
                    'starts_at' => optional($appointment->starts_at)?->toDateTimeString(),
                    'ends_at' => optional($appointment->ends_at)?->toDateTimeString(),
                    'status' => (string) ($appointment->status ?? 'scheduled'),
                    'status_label' => (string) $appointment->status_label,
                    'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                    'payment_status_label' => (string) $appointment->payment_status_label,
                    'paid_amount' => (float) ($appointment->paid_amount ?? 0),
                    'payment_currency' => (string) ($appointment->payment_currency ?: 'USD'),
                    'sales_order_id' => $appointment->sales_order_id ? (int) $appointment->sales_order_id : null,
                    'public_order_url' => $appointment->sales_order_id ? url('/publicOrder/' . (int) $appointment->sales_order_id) : null,
                    'can_confirm' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_reschedule' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_cancel' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_confirm_payment' => in_array((string) ($appointment->payment_status ?? 'pending'), ['pending', 'partial'], true),
                ];
            })->values(),
        ]);
    }

    public function customerWorkflowAction(Request $request, Appointment $appointment): JsonResponse
    {
        $customerId = (int) (auth()->id() ?? 0);

        if ((int) ($appointment->customer_id ?? 0) !== $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para gestionar esta cita.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['confirm_attendance', 'cancel', 'reschedule', 'confirm_payment'])],
            'scheduled_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'payment_method_id' => ['nullable', 'integer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'create_sale' => ['nullable', 'boolean'],
        ]);

        $actor = auth()->user();
        $result = $this->applyWorkflowAction($appointment, $validated, $actor, true);

        return response()->json($result);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'appointment_service_id' => ['required', 'integer'],
            'sessions_count' => ['required', 'integer', 'min:1', 'max:60'],
            'repeat_every_weeks' => ['nullable', 'integer', 'min:1', 'max:12'],
            'preferred_day_of_week' => ['nullable', 'integer', Rule::in(array_keys(UserScheduleRule::WEEK_DAYS))],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
        ]);

        $service = AppointmentService::query()
            ->where('tenant_id', $tenantId)
            ->whereKey((int) $validated['appointment_service_id'])
            ->firstOrFail();

        $targetUserId = !empty($validated['user_id'])
            ? (int) $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->value('id')
            : (int) ($service->user_id ?? 0);

        if ($targetUserId <= 0) {
            return back()->withErrors(['user_id' => 'Debes seleccionar un profesional para crear el paquete de citas.'])->withInput();
        }

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey($targetUserId)->firstOrFail();
        $customerId = !empty($validated['customer_id'])
            ? (int) User::query()->whereKey((int) $validated['customer_id'])->value('id')
            : null;

        $repeatEveryWeeks = max(1, (int) ($validated['repeat_every_weeks'] ?? 1));
        $sessionsCount = max(1, (int) $validated['sessions_count']);
        $preferredTime = (string) ($validated['preferred_time'] ?? '09:00');
        $startDate = !empty($validated['start_date'])
            ? Carbon::parse((string) $validated['start_date'])->startOfDay()
            : now()->startOfDay();

        $package = null;
        DB::transaction(function () use (&$package, $tenantId, $validated, $service, $sessionsCount, $repeatEveryWeeks, $targetUser, $customerId, $startDate, $preferredTime) {
            $package = AppointmentPackage::create([
                'tenant_id' => $tenantId,
                'name' => trim((string) $validated['name']),
                'description' => $validated['description'] ?? null,
                'appointment_service_id' => (int) $service->id,
                'sessions_count' => $sessionsCount,
                'repeat_every_weeks' => $repeatEveryWeeks,
                'preferred_day_of_week' => $validated['preferred_day_of_week'] ?? null,
                'preferred_time' => $preferredTime,
                'price' => isset($validated['price']) ? (float) $validated['price'] : (float) ($service->price ?? 0),
                'is_active' => true,
            ]);

            $dayOfWeek = isset($validated['preferred_day_of_week'])
                ? (int) $validated['preferred_day_of_week']
                : (int) $startDate->dayOfWeek;

            $firstSessionDate = $startDate->copy();
            while ((int) $firstSessionDate->dayOfWeek !== $dayOfWeek) {
                $firstSessionDate->addDay();
            }

            for ($index = 1; $index <= $sessionsCount; $index += 1) {
                $sessionDate = $firstSessionDate->copy()->addWeeks(($index - 1) * $repeatEveryWeeks);
                $startAt = Carbon::parse($sessionDate->toDateString() . ' ' . $preferredTime);
                $endAt = $startAt->copy()->addMinutes(max(15, (int) ($service->duration_minutes ?? 60)));

                $appointment = Appointment::create([
                    'tenant_id' => $tenantId,
                    'appointment_service_id' => (int) $service->id,
                    'user_id' => (int) $targetUser->id,
                    'customer_id' => $customerId,
                    'starts_at' => $startAt,
                    'ends_at' => $endAt,
                    'status' => 'scheduled',
                    'payment_status' => 'pending',
                    'source' => 'package',
                    'workflow_tag' => 'package:' . $package->id,
                    'workflow_note' => 'Sesión ' . $index . ' de ' . $sessionsCount,
                ]);

                AppointmentPackageSession::create([
                    'tenant_id' => $tenantId,
                    'appointment_package_id' => (int) $package->id,
                    'appointment_id' => (int) $appointment->id,
                    'session_number' => $index,
                    'scheduled_for' => $startAt,
                    'status' => 'scheduled',
                ]);
            }
        });

        return back()->with('success', 'Paquete de citas creado correctamente con ' . $sessionsCount . ' sesiones.');
    }

    private function applyWorkflowAction(Appointment $appointment, array $validated, User $actor, bool $fromCustomer): array
    {
        $tenantId = (int) $appointment->tenant_id;
        $action = (string) ($validated['action'] ?? '');
        $note = trim((string) ($validated['note'] ?? '')) ?: null;

        if ($action === 'reschedule' && (empty($validated['scheduled_date']) || empty($validated['start_time']))) {
            return [
                'success' => false,
                'message' => 'Para reprogramar debes indicar fecha y hora.',
            ];
        }

        try {
            DB::transaction(function () use ($appointment, $validated, $action, $note, $actor, $fromCustomer, $tenantId) {
                $appointment->loadMissing(['service', 'assignedUser', 'customer', 'paymentMethod.currency', 'salesOrder']);

                switch ($action) {
                    case 'call_customer':
                        $appointment->called_at = now();
                        $appointment->called_by_user_id = (int) $actor->id;
                        $appointment->workflow_tag = 'called';
                        $appointment->workflow_note = $note ?: 'Cliente contactado por teléfono.';
                        break;

                    case 'confirm_attendance':
                        $appointment->status = 'confirmed';
                        $appointment->attendance_confirmed_at = now();
                        $appointment->attendance_confirmed_by_user_id = (int) $actor->id;
                        $appointment->workflow_tag = 'attendance_confirmed';
                        $appointment->workflow_note = $note ?: ($fromCustomer ? 'El cliente confirmó su asistencia.' : 'Asistencia confirmada por el equipo.');
                        break;

                    case 'cancel':
                        $appointment->status = 'cancelled';
                        $appointment->cancelled_at = now();
                        $appointment->cancelled_by_user_id = (int) $actor->id;
                        $appointment->workflow_tag = 'cancelled';
                        $appointment->workflow_note = $note ?: ($fromCustomer ? 'El cliente canceló la cita.' : 'Cita cancelada por el equipo.');
                        break;

                    case 'no_show':
                        $appointment->status = 'no_show';
                        $appointment->workflow_tag = 'no_show';
                        $appointment->workflow_note = $note ?: 'Se registró inasistencia del cliente.';
                        break;

                    case 'reschedule':
                        $selectedDate = Carbon::parse((string) $validated['scheduled_date'])->startOfDay();
                        $startAt = Carbon::parse($selectedDate->toDateString() . ' ' . (string) $validated['start_time']);
                        $service = $appointment->service;
                        $targetUser = $appointment->assignedUser;

                        if (!$service || !$targetUser) {
                            throw new \RuntimeException('No se pudo reprogramar porque faltan datos de servicio o profesional.');
                        }

                        $availableSlots = collect($this->buildAvailableSlots(
                            $tenantId,
                            $targetUser,
                            $service,
                            $selectedDate,
                            (int) $appointment->id
                        ));

                        if (!$availableSlots->firstWhere('start', $startAt->format('H:i'))) {
                            throw new \RuntimeException('La hora elegida no está disponible para reprogramar esta cita.');
                        }

                        $appointment->rescheduled_from_appointment_id = $appointment->rescheduled_from_appointment_id ?: (int) $appointment->id;
                        $appointment->rescheduled_at = now();
                        $appointment->rescheduled_by_user_id = (int) $actor->id;
                        $appointment->starts_at = $startAt;
                        $appointment->ends_at = $startAt->copy()->addMinutes(max(15, (int) ($service->duration_minutes ?? 60)));
                        $appointment->confirmation_reminder_sent_at = null;
                        $appointment->status = 'scheduled';
                        $appointment->workflow_tag = 'rescheduled';
                        $appointment->workflow_note = $note ?: 'Cita reprogramada.';
                        break;

                    case 'confirm_payment':
                        $paidAmount = isset($validated['paid_amount'])
                            ? (float) $validated['paid_amount']
                            : (float) ($appointment->paid_amount ?? 0);
                        $paidAmount = round(max($paidAmount, 0), 2);

                        if ($paidAmount <= 0) {
                            $servicePrice = (float) ($appointment->service->price ?? 0);
                            if ($servicePrice > 0) {
                                $paidAmount = round($servicePrice, 2);
                            }
                        }

                        if ($paidAmount <= 0) {
                            throw new \RuntimeException('No se pudo confirmar pago porque el monto es 0.');
                        }

                        $paymentMethod = null;
                        $paymentMethodId = (int) ($validated['payment_method_id'] ?? ($appointment->payment_method_id ?? 0));
                        if ($paymentMethodId > 0) {
                            $paymentMethod = PaymentMethod::query()
                                ->with('currency')
                                ->where('tenant_id', $tenantId)
                                ->active()
                                ->whereKey($paymentMethodId)
                                ->first();
                        }

                        $appointment->paid_amount = $paidAmount;
                        $appointment->payment_method_id = $paymentMethod?->id;
                        $appointment->payment_currency = (string) ($paymentMethod?->currency?->code ?: ($appointment->payment_currency ?: 'USD'));
                        $appointment->payment_reference = trim((string) ($validated['payment_reference'] ?? $appointment->payment_reference ?? '')) ?: null;
                        $appointment->payment_status = 'paid';
                        $appointment->status = 'confirmed';
                        $appointment->attendance_confirmed_at = $appointment->attendance_confirmed_at ?: now();
                        $appointment->attendance_confirmed_by_user_id = $appointment->attendance_confirmed_by_user_id ?: (int) $actor->id;
                        $appointment->workflow_tag = 'payment_confirmed';
                        $appointment->workflow_note = $note ?: 'Pago confirmado en cita.';

                        $createSale = (bool) ($validated['create_sale'] ?? true);
                        if ($createSale) {
                            $this->createSaleOrderFromAppointment($appointment, $paymentMethod);
                        }
                        break;
                }

                $appointment->save();
            });
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }

        $appointment->refresh()->loadMissing(['service', 'assignedUser', 'customer', 'salesOrder']);
        $this->notifyAppointmentWorkflow($appointment, $action, $actor, $note);

        return [
            'success' => true,
            'message' => $this->workflowActionMessage($action),
            'appointment' => [
                'id' => (int) $appointment->id,
                'status' => (string) $appointment->status,
                'status_label' => (string) $appointment->status_label,
                'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                'payment_status_label' => (string) $appointment->payment_status_label,
                'starts_at' => optional($appointment->starts_at)?->toDateTimeString(),
                'ends_at' => optional($appointment->ends_at)?->toDateTimeString(),
                'workflow_tag' => (string) ($appointment->workflow_tag ?? ''),
                'workflow_note' => (string) ($appointment->workflow_note ?? ''),
                'sales_order_id' => $appointment->sales_order_id ? (int) $appointment->sales_order_id : null,
            ],
        ];
    }

    private function workflowActionMessage(string $action): string
    {
        return match ($action) {
            'call_customer' => 'Se registró la llamada al cliente.',
            'confirm_attendance' => 'La asistencia quedó confirmada.',
            'cancel' => 'La cita fue cancelada.',
            'no_show' => 'Se registró la inasistencia.',
            'reschedule' => 'La cita fue reprogramada correctamente.',
            'confirm_payment' => 'Pago confirmado y cita actualizada.',
            default => 'Cita actualizada.',
        };
    }

    private function createSaleOrderFromAppointment(Appointment $appointment, ?PaymentMethod $paymentMethod = null): ?SalesOrder
    {
        if ($appointment->sales_order_id) {
            return SalesOrder::query()->find((int) $appointment->sales_order_id);
        }

        if (!(int) ($appointment->customer_id ?? 0)) {
            return null;
        }

        $appointment->loadMissing(['service.productVariant.product']);
        $variant = $appointment->service?->productVariant;

        if (!$variant) {
            throw new \RuntimeException('La cita no tiene un producto de servicio vinculado para registrar la venta.');
        }

        if ((float) $variant->stock < 1) {
            throw new \RuntimeException('No hay stock suficiente para registrar el pago como venta.');
        }

        $price = round((float) ($appointment->service->price ?? $variant->effective_price ?? $variant->price ?? 0), 2);
        $price = $price > 0 ? $price : round((float) ($appointment->paid_amount ?? 0), 2);

        if ($price <= 0) {
            throw new \RuntimeException('No hay precio válido para convertir la cita en venta.');
        }

        $salesOrder = SalesOrder::create([
            'user_id' => (int) $appointment->customer_id,
            'date' => now()->toDateString(),
            'status' => 1,
            'address' => 'Agenda de citas',
            'preference' => 'Servicio en cita',
            'deliver_status' => 0,
            'tenant_id' => (int) $appointment->tenant_id,
            'document_issue_mode' => 'delivery_note',
            'sale_currency_code' => (string) ($appointment->payment_currency ?: 'USD'),
        ]);

        SalesOrderDetail::create([
            'sales_order_id' => (int) $salesOrder->id,
            'product_variant_id' => (int) $variant->id,
            'quantity' => 1,
            'price' => $price,
            'amount' => $price,
        ]);

        $variant->stock = max(0, (float) $variant->stock - 1);
        $variant->save();

        if ((float) ($appointment->paid_amount ?? 0) > 0) {
            Payment::create([
                'sales_order_id' => (int) $salesOrder->id,
                'payment_method' => (string) ($paymentMethod?->id ?? $appointment->payment_method_id ?? 'manual'),
                'amount' => (float) $appointment->paid_amount,
                'currency' => (string) ($appointment->payment_currency ?: 'USD'),
                'reference' => $appointment->payment_reference,
                'status' => 1,
            ]);
        }

        $appointment->sales_order_id = (int) $salesOrder->id;
        $appointment->save();

        return $salesOrder;
    }

    private function notifyAppointmentWorkflow(Appointment $appointment, string $action, User $actor, ?string $note = null): void
    {
        $payload = [
            'title' => 'Actualización de cita',
            'message' => ($appointment->service->display_name ?? $appointment->service->name ?? 'Servicio') . ' · ' . $this->workflowActionMessage($action),
            'type' => 'info',
            'tenant_id' => (int) $appointment->tenant_id,
            'order_id' => $appointment->sales_order_id ? (int) $appointment->sales_order_id : null,
            'action' => 'appointment_' . $action,
            'meta' => [
                'appointment_id' => (int) $appointment->id,
                'status' => (string) $appointment->status,
                'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                'actor' => (string) ($actor->name ?? 'Sistema'),
                'note' => $note,
            ],
        ];

        WorkflowNotifier::notifyTenantRoles((int) $appointment->tenant_id, ['owner', 'administrador', 'admin', 'vendedor'], $payload);
        WorkflowNotifier::notifyUser($appointment->customer, $payload);
        WorkflowNotifier::notifyUser($appointment->assignedUser, $payload);
    }

    private function appointmentUsersQuery(int $tenantId)
    {
        return User::query()
            ->with('role')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereNull('role_id')
                    ->orWhereHas('role', function ($roleQuery) {
                        $roleQuery->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(name)'), ['user', 'cliente', 'customer', 'super_user', 'super user']);
                    });
            })
            ->orderBy('name');
    }

    private function resolveSelectedDate(?string $date): Carbon
    {
        try {
            return $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        } catch (\Throwable $exception) {
            return now()->startOfDay();
        }
    }

    private function customerUsersQuery(int $tenantId)
    {
        return User::query()
            ->with('role')
            ->where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereHas('role', function ($roleQuery) {
                    $roleQuery->whereIn(DB::raw('LOWER(name)'), ['user', 'cliente', 'customer']);
                })->orWhereNull('role_id');
            })
            ->orderBy('name');
    }

    private function serviceVariantsQuery(int $tenantId)
    {
        return ProductVariant::query()
            ->with('product')
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('is_consumable', false);
            })
            ->orderBy('product_id')
            ->orderBy('size');
    }

    private function consumableVariantsQuery(int $tenantId)
    {
        return ProductVariant::query()
            ->with('product')
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->where('is_consumable', true);
            })
            ->orderBy('product_id')
            ->orderBy('size');
    }

    private function resolveCalendarBounds(Collection $rules, array $professionalIds): array
    {
        $filteredRules = empty($professionalIds)
            ? $rules
            : $rules->whereIn('user_id', $professionalIds);

        if ($filteredRules->isEmpty()) {
            return ['startHour' => 7, 'endHour' => 21];
        }

        $minHour = $filteredRules->min(function (UserScheduleRule $rule) {
            return (int) Carbon::parse((string) $rule->start_time)->format('G');
        });
        $maxHour = $filteredRules->max(function (UserScheduleRule $rule) {
            $end = Carbon::parse((string) $rule->end_time);
            return (int) ceil(((int) $end->format('G') * 60 + (int) $end->format('i')) / 60);
        });

        return [
            'startHour' => max(6, ((int) $minHour) - 1),
            'endHour' => min(23, max(((int) $maxHour) + 1, ((int) $minHour) + 2)),
        ];
    }

    private function buildCalendarEvents(Collection $appointments, int $startHour): array
    {
        return $appointments->map(function (Appointment $appointment) use ($startHour) {
            $startMinutes = ((int) $appointment->starts_at->format('G') * 60) + (int) $appointment->starts_at->format('i');
            $endMinutes = ((int) $appointment->ends_at->format('G') * 60) + (int) $appointment->ends_at->format('i');

            return [
                'id' => (int) $appointment->id,
                'service_id' => (int) ($appointment->appointment_service_id ?? 0),
                'user_id' => (int) ($appointment->user_id ?? 0),
                'customer_id' => (int) ($appointment->customer_id ?? 0),
                'date' => $appointment->starts_at->toDateString(),
                'title' => (string) ($appointment->service->display_name ?? $appointment->service->name ?? 'Servicio'),
                'professional' => (string) ($appointment->assignedUser->name ?? 'Profesional'),
                'customer' => (string) ($appointment->customer->name ?? $appointment->contact_name ?? 'Cliente sin registro'),
                'start_time' => $appointment->starts_at->format('H:i'),
                'end_time' => $appointment->ends_at->format('H:i'),
                'minutes_from_start' => max(0, $startMinutes - ($startHour * 60)),
                'duration_minutes' => max(30, $endMinutes - $startMinutes),
                'status' => (string) $appointment->status_label,
                'payment_status' => (string) $appointment->payment_status_label,
                'status_key' => (string) ($appointment->status ?? 'scheduled'),
                'payment_status_key' => (string) ($appointment->payment_status ?? 'pending'),
                'payment_method_id' => $appointment->payment_method_id ? (int) $appointment->payment_method_id : null,
                'payment_reference' => (string) ($appointment->payment_reference ?? ''),
                'contact_name' => (string) ($appointment->contact_name ?? ''),
                'contact_phone' => (string) ($appointment->contact_phone ?: ($appointment->customer->phone_number ?? '')),
                'paid_amount' => (float) ($appointment->paid_amount ?? 0),
                'payment_currency' => (string) ($appointment->payment_currency ?? ''),
                'color_hex' => (string) ($appointment->service->color_hex ?? '#0f172a'),
                'notes' => (string) ($appointment->notes ?? ''),
            ];
        })->values()->all();
    }

    private function buildAvailableSlots(int $tenantId, User $targetUser, AppointmentService $service, Carbon $selectedDate, ?int $ignoreAppointmentId = null): array
    {
        if ($service->user_id && (int) $service->user_id !== (int) $targetUser->id) {
            return [];
        }

        $rules = UserScheduleRule::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (int) $targetUser->id)
            ->where('day_of_week', (int) $selectedDate->dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($rules->isEmpty()) {
            return [];
        }

        $durationMinutes = max(15, (int) ($service->duration_minutes ?? 60));
        $bufferMinutes = max(0, (int) ($service->buffer_minutes ?? 0));
        $existingAppointments = Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', (int) $targetUser->id)
            ->whereDate('starts_at', $selectedDate->toDateString())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($ignoreAppointmentId && $ignoreAppointmentId > 0, function ($query) use ($ignoreAppointmentId) {
                $query->where('id', '!=', (int) $ignoreAppointmentId);
            })
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        foreach ($rules as $rule) {
            $interval = max(15, (int) ($rule->slot_interval_minutes ?: $durationMinutes));
            $cursor = Carbon::parse($selectedDate->toDateString() . ' ' . $rule->start_time);
            $windowEnd = Carbon::parse($selectedDate->toDateString() . ' ' . $rule->end_time);

            while ($cursor->copy()->addMinutes($durationMinutes) <= $windowEnd) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($durationMinutes + $bufferMinutes);

                $hasConflict = $existingAppointments->contains(function (Appointment $appointment) use ($slotStart, $slotEnd) {
                    return $slotStart < $appointment->ends_at && $slotEnd > $appointment->starts_at;
                });

                if (!$hasConflict) {
                    $slots[] = [
                        'start' => $slotStart->format('H:i'),
                        'end' => $slotStart->copy()->addMinutes($durationMinutes)->format('H:i'),
                        'label' => $slotStart->format('H:i') . ' - ' . $slotStart->copy()->addMinutes($durationMinutes)->format('H:i'),
                    ];
                }

                $cursor->addMinutes($interval);
            }
        }

        return array_values(array_unique($slots, SORT_REGULAR));
    }

    private function normalizePhoneWithCode(?string $phoneValue, mixed $phoneCode = null): ?string
    {
        $rawPhone = trim((string) $phoneValue);
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
            ->whereIn(DB::raw('LOWER(name)'), ['user', 'cliente', 'customer'])
            ->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        return (int) Role::query()->firstOrCreate(['name' => 'user'])->id;
    }
}