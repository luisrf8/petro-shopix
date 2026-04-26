<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConsumption;
use App\Models\AppointmentService;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserScheduleRule;
use App\Support\TenantPlanCapabilities;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            'calendarEvents'
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
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(UserScheduleRule::WEEK_DAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:15', 'max:240'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();

        UserScheduleRule::create([
            'tenant_id' => $tenantId,
            'user_id' => (int) $targetUser->id,
            'day_of_week' => (int) $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'slot_interval_minutes' => (int) ($validated['slot_interval_minutes'] ?? 60),
            'is_active' => true,
        ]);

        return back()->with('success', 'Horario asignado correctamente.');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $validated = $request->validate([
            'appointment_service_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'customer_id' => ['nullable', 'integer'],
            'contact_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
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

        if ($service->user_id && (int) $service->user_id !== (int) $targetUser->id) {
            return back()->withErrors(['user_id' => 'Este servicio está asignado a otro profesional.'])->withInput();
        }

        $selectedDate = Carbon::parse($validated['scheduled_date']);
        $startAt = Carbon::parse($selectedDate->toDateString() . ' ' . $validated['start_time']);
        $endAt = (clone $startAt)->addMinutes((int) $service->duration_minutes);

        $availableSlots = collect($this->buildAvailableSlots($tenantId, $targetUser, $service, $selectedDate));
        if (!$availableSlots->firstWhere('start', $startAt->format('H:i'))) {
            return back()->withErrors(['start_time' => 'La hora seleccionada no está disponible para ese profesional.'])->withInput();
        }

        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $customerId = (int) User::query()->where('tenant_id', $tenantId)->whereKey((int) $validated['customer_id'])->value('id');
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

        DB::transaction(function () use ($tenantId, $service, $targetUser, $customerId, $validated, $startAt, $endAt, $paymentMethod, $paidAmount, $paymentStatus, $paymentReference, $consumptions) {
            $appointment = Appointment::create([
                'tenant_id' => $tenantId,
                'appointment_service_id' => (int) $service->id,
                'user_id' => (int) $targetUser->id,
                'customer_id' => $customerId,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
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
            ]);

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

        return redirect()->route('appointments.index', ['date' => $selectedDate->toDateString()])->with('success', 'Cita registrada correctamente.');
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
                'paid_amount' => (float) ($appointment->paid_amount ?? 0),
                'payment_currency' => (string) ($appointment->payment_currency ?? ''),
                'color_hex' => (string) ($appointment->service->color_hex ?? '#0f172a'),
                'notes' => (string) ($appointment->notes ?? ''),
            ];
        })->values()->all();
    }

    private function buildAvailableSlots(int $tenantId, User $targetUser, AppointmentService $service, Carbon $selectedDate): array
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

                if (!$hasConflict && $slotStart >= now()->subMinute()) {
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
}