<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConsumption;
use App\Models\AppointmentImage;
use App\Models\AppointmentService;
use App\Models\AppointmentServiceItem;
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
use App\Support\ImageStorage;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
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
    public function servicesIndex(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->findOrFail((int) $user->tenant_id);
        $planCapabilities = TenantPlanCapabilities::forTenant($tenant);

        if (!$planCapabilities->canAppointments()) {
            return redirect()->route('dashboard')->with('warning', 'El módulo de citas está disponible a partir del plan Pro.');
        }

        $professionals = $this->appointmentUsersQuery((int) $tenant->id)->get();
        $serviceVariants = $this->serviceVariantsQuery((int) $tenant->id)->get();
        $services = AppointmentService::query()
            ->with(['assignedUser', 'assignedUsers:id,name', 'productVariant.product.category'])
            ->where('tenant_id', (int) $tenant->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
        $activeServices = $services
            ->filter(fn (AppointmentService $service) => (bool) ($service->is_active ?? true))
            ->values();
        $customers = $this->customerUsersQuery((int) $tenant->id)->get(['id', 'name']);
        $packages = AppointmentPackage::query()
            ->with([
                'service:id,name,product_variant_id',
                'service.productVariant:id,product_id,size',
                'service.productVariant.product:id,name',
                'sessions:id,appointment_package_id,session_number,status,scheduled_for',
            ])
            ->where('tenant_id', (int) $tenant->id)
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        $activeTab = in_array((string) $request->query('tab', 'create'), ['create', 'created', 'packages'], true)
            ? (string) $request->query('tab', 'create')
            : 'create';

        return view('appointments.services', compact(
            'tenant',
            'planCapabilities',
            'professionals',
            'serviceVariants',
            'services',
            'activeServices',
            'customers',
            'packages',
            'activeTab'
        ));
    }

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
            ->with(['assignedUser', 'assignedUsers:id,name', 'productVariant.product.category'])
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('name')
            ->get();
        $activeServices = $services
            ->filter(fn (AppointmentService $service) => (bool) ($service->is_active ?? true))
            ->values();
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
            ->with(['service', 'serviceItems.service', 'assignedUser', 'customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->whereDate('starts_at', $selectedDate->toDateString())
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->orderBy('starts_at')
            ->get();

        $upcomingAppointments = Appointment::query()
            ->with(['service.productVariant.product', 'serviceItems.service.productVariant.product', 'assignedUser', 'customer', 'consumptions.variant.product', 'paymentMethod.currency'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $calendarAppointments = Appointment::query()
            ->with(['service.productVariant.product', 'serviceItems.service.productVariant.product', 'assignedUser', 'customer', 'consumptions.variant.product', 'paymentMethod.currency'])
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
        $servicesPayload = $activeServices->map(function (AppointmentService $service) {
            $assignedUserIds = $this->appointmentServiceAssignedUserIds($service);

            return [
                'id' => (int) $service->id,
                'name' => (string) $service->display_name,
                'duration_minutes' => (int) ($service->duration_minutes ?? 60),
                'buffer_minutes' => (int) ($service->buffer_minutes ?? 0),
                'price' => (float) ($service->price ?? 0),
                'assigned_user_id' => !empty($assignedUserIds) ? (int) $assignedUserIds[0] : null,
                'assigned_user_ids' => $assignedUserIds,
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
                'is_consumable' => (bool) ($variant->product->is_consumable ?? false),
            ];
        })->values();

        if ($request->expectsJson() || $request->boolean('realtime')) {
            return response()->json([
                'success' => true,
                'appointments_first_come_enabled' => (bool) ($tenant->appointments_first_come_enabled ?? false),
                'selected_date' => $selectedDate->toDateString(),
                'selected_user_id' => $selectedUserId,
                'calendar_week_start' => $calendarWeekStart->toDateString(),
                'calendar_week_end' => $calendarWeekEnd->toDateString(),
                'calendar_week_title' => Str::ucfirst($calendarWeekStart->translatedFormat('d M')) . ' - ' . Str::ucfirst($calendarWeekEnd->translatedFormat('d M Y')),
                'calendar_week_note' => 'Semana de ' . $calendarWeekStart->format('d/m') . ' a ' . $calendarWeekEnd->format('d/m') . '.',
                'calendar_week_events_count' => count($calendarEvents ?? []),
                'calendar_events' => $calendarEvents,
            ]);
        }

        return view('appointments.index', compact(
            'tenant',
            'planCapabilities',
            'selectedDate',
            'selectedUserId',
            'professionals',
            'calendarProfessionals',
            'services',
            'activeServices',
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
        $tenant = Tenant::query()->findOrFail($tenantId);

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
            'appointment_id' => ['nullable', 'integer'],
            'date' => ['required', 'date'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();
        $selectedServices = $this->resolveAppointmentServicesFromPayload($tenantId, $targetUser, $validated);
        if ($selectedServices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar al menos un servicio válido.',
                'slots' => [],
            ], 422);
        }

        $primaryService = $selectedServices->first();
        $selectedDate = Carbon::parse($validated['date']);
        $totalMinutes = $this->calculateTotalAppointmentMinutes($selectedServices);

        return response()->json([
            'success' => true,
            'appointments_first_come_enabled' => (bool) ($tenant->appointments_first_come_enabled ?? false),
            'slots' => $this->buildAvailableSlots(
                $tenantId,
                $targetUser,
                $primaryService,
                $selectedDate,
                !empty($validated['appointment_id']) ? (int) $validated['appointment_id'] : null,
                $totalMinutes
            ),
        ]);
    }

    public function customerControlIndex(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->findOrFail((int) $user->tenant_id);
        $planCapabilities = TenantPlanCapabilities::forTenant($tenant);

        if (!$planCapabilities->canAppointments()) {
            return redirect()->route('dashboard')->with('warning', 'El módulo de citas está disponible a partir del plan Pro.');
        }

        $customers = $this->customerUsersQuery((int) $tenant->id)
            ->get(['id', 'name', 'phone_number', 'email', 'dni']);

        $selectedCustomerId = (int) $request->query('customer_id', 0);
        $selectedCustomer = $selectedCustomerId > 0
            ? $customers->firstWhere('id', $selectedCustomerId)
            : null;

        $appointments = collect();
        $salesOrders = collect();
        $summary = [
            'appointments_count' => 0,
            'appointments_total_usd' => 0.0,
            'sales_count' => 0,
            'sales_total_usd' => 0.0,
            'payments_total_usd' => 0.0,
        ];

        if ($selectedCustomer) {
            $appointments = Appointment::query()
                ->with([
                    'service.productVariant.product',
                    'serviceItems.service.productVariant.product',
                    'assignedUser',
                    'customer',
                    'consumptions.variant.product',
                    'images.uploadedBy',
                    'paymentMethod.currency',
                    'salesOrder.payments.payment',
                ])
                ->where('tenant_id', (int) $tenant->id)
                ->where('customer_id', (int) $selectedCustomer->id)
                ->orderByDesc('starts_at')
                ->limit(250)
                ->get();

            $salesOrders = SalesOrder::query()
                ->with([
                    'details.variant.product',
                    'payments.payment',
                ])
                ->where('tenant_id', (int) $tenant->id)
                ->where('user_id', (int) $selectedCustomer->id)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(250)
                ->get();

            $summary['appointments_count'] = (int) $appointments->count();
            $summary['appointments_total_usd'] = round((float) $appointments->sum(function (Appointment $appointment) {
                return $this->calculateAppointmentServicesTotalPrice($appointment);
            }), 2);
            $summary['sales_count'] = (int) $salesOrders->count();
            $summary['sales_total_usd'] = round((float) $salesOrders->sum(function (SalesOrder $order) {
                $itemsSubtotal = (float) $order->details->sum('amount');
                $delivery = (float) ($order->delivery_fee ?? 0);
                return round($itemsSubtotal + $delivery, 2);
            }), 2);
            $summary['payments_total_usd'] = round((float) $salesOrders->sum(function (SalesOrder $order) {
                return (float) $order->payments->sum('amount');
            }), 2);
        }

        $bsRate = TenantCurrency::resolveRateToBs((int) $tenant->id, 'USD');

        return view('appointments.customer-control', compact(
            'tenant',
            'planCapabilities',
            'customers',
            'selectedCustomerId',
            'selectedCustomer',
            'appointments',
            'salesOrders',
            'summary',
            'bsRate'
        ));
    }

    public function customerControlReportPdf(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->findOrFail((int) $user->tenant_id);

        $customerId = (int) $request->query('customer_id', 0);
        if ($customerId <= 0) {
            return redirect()->route('appointments.customerControl.index')->with('warning', 'Selecciona un cliente para generar el reporte.');
        }

        $customer = $this->customerUsersQuery((int) $tenant->id)
            ->whereKey($customerId)
            ->first();

        if (!$customer) {
            return redirect()->route('appointments.customerControl.index')->with('warning', 'El cliente seleccionado no pertenece a esta sede.');
        }

        $appointments = Appointment::query()
            ->with([
                'service.productVariant.product',
                'serviceItems.service.productVariant.product',
                'assignedUser',
                'customer',
                'consumptions.variant.product',
                'images.uploadedBy',
                'paymentMethod.currency',
                'salesOrder.payments.payment',
            ])
            ->where('tenant_id', (int) $tenant->id)
            ->where('customer_id', $customerId)
            ->orderByDesc('starts_at')
            ->limit(400)
            ->get();

        $bsRate = TenantCurrency::resolveRateToBs((int) $tenant->id, 'USD');

        $summary = [
            'appointments_count' => (int) $appointments->count(),
            'appointments_total_usd' => round((float) $appointments->sum(function (Appointment $appointment) {
                return $this->calculateAppointmentServicesTotalPrice($appointment);
            }), 2),
            'evidences_count' => (int) $appointments->sum(fn (Appointment $appointment) => $appointment->images->count()),
        ];

        $html = view('appointments.pdf.customer-control-report', compact('tenant', 'customer', 'appointments', 'summary', 'bsRate'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'reporte_citas_cliente_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) ($customer->name ?? 'cliente')) . '_' . now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function customerControlAppointmentPdf(Appointment $appointment)
    {
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        abort_unless((int) $appointment->tenant_id === $tenantId, 404);

        $appointment->load([
            'service.productVariant.product',
            'serviceItems.service.productVariant.product',
            'assignedUser',
            'customer',
            'consumptions.variant.product',
            'images.uploadedBy',
            'paymentMethod.currency',
            'salesOrder.payments.payment',
        ]);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $bsRate = TenantCurrency::resolveRateToBs($tenantId, 'USD');
        $appointmentTotal = $this->calculateAppointmentServicesTotalPrice($appointment);

        $html = view('appointments.pdf.customer-appointment-detail', compact('tenant', 'appointment', 'appointmentTotal', 'bsRate'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'cita_' . (int) $appointment->id . '_' . now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function customerControlStoreEvidence(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        abort_unless((int) $appointment->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:8000'],
            'caption' => ['nullable', 'string', 'max:255'],
            'evidence_images' => ['nullable', 'array', 'max:6'],
            'evidence_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'customer_id' => ['nullable', 'integer'],
        ]);

        if ($request->has('notes')) {
            $appointment->notes = trim((string) ($validated['notes'] ?? ''));
            $appointment->save();
        }

        $caption = trim((string) ($validated['caption'] ?? ''));
        $images = $request->file('evidence_images', []);

        foreach ($images as $imageFile) {
            if (!$imageFile) {
                continue;
            }

            $storedPath = ImageStorage::storeUploadedImageAsWebp($imageFile, 'appointments/evidence');
            AppointmentImage::create([
                'tenant_id' => $tenantId,
                'appointment_id' => (int) $appointment->id,
                'uploaded_by_user_id' => (int) ($user->id ?? 0),
                'image_path' => $storedPath,
                'caption' => $caption !== '' ? $caption : null,
            ]);
        }

        return redirect()
            ->route('appointments.customerControl.index', ['customer_id' => (int) ($validated['customer_id'] ?? $appointment->customer_id)])
            ->with('success', 'Control de cita actualizado con anotaciones e imágenes.');
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
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'product_variant_id' => ['required', 'integer'],
        ]);

        $assignedUserIds = $this->normalizeAssignedUserIdsFromRequest($tenantId, $validated);
        $primaryAssignedUserId = !empty($assignedUserIds) ? (int) $assignedUserIds[0] : null;

        $productVariant = $this->serviceVariantsQuery($tenantId)->whereKey((int) $validated['product_variant_id'])->firstOrFail();
        $resolvedPrice = isset($validated['price'])
            ? (float) $validated['price']
            : (float) ($productVariant->effective_price ?? $productVariant->price ?? 0);

        $service = AppointmentService::create([
            'tenant_id' => $tenantId,
            'user_id' => $primaryAssignedUserId,
            'product_variant_id' => (int) $productVariant->id,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'buffer_minutes' => (int) ($validated['buffer_minutes'] ?? 0),
            'price' => $resolvedPrice,
            'color_hex' => $validated['color_hex'] ?? '#0f172a',
            'is_active' => true,
        ]);

        $this->syncAppointmentServiceAssignedUsers($service, $assignedUserIds);

        return $this->redirectServicesPage('create')->with('success', 'Servicio para citas creado correctamente.');
    }

    public function updateService(Request $request, AppointmentService $service): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        abort_unless((int) $service->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'user_id' => ['nullable', 'integer'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'product_variant_id' => ['required', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $assignedUserIds = $this->normalizeAssignedUserIdsFromRequest($tenantId, $validated);
        $primaryAssignedUserId = !empty($assignedUserIds) ? (int) $assignedUserIds[0] : null;

        $productVariant = $this->serviceVariantsQuery($tenantId)
            ->whereKey((int) $validated['product_variant_id'])
            ->firstOrFail();

        $service->update([
            'user_id' => $primaryAssignedUserId,
            'product_variant_id' => (int) $productVariant->id,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'buffer_minutes' => (int) ($validated['buffer_minutes'] ?? 0),
            'price' => isset($validated['price']) ? (float) $validated['price'] : (float) ($service->price ?? 0),
            'color_hex' => $validated['color_hex'] ?? '#0f172a',
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) ($service->is_active ?? true),
        ]);

        $this->syncAppointmentServiceAssignedUsers($service, $assignedUserIds);

        return $this->redirectServicesPage('created')->with('success', 'Servicio actualizado correctamente.');
    }

    public function toggleServiceStatus(AppointmentService $service): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        abort_unless((int) $service->tenant_id === $tenantId, 404);

        $service->is_active = !((bool) ($service->is_active ?? true));
        $service->save();

        return $this->redirectServicesPage('created')->with('success', $service->is_active
            ? 'Servicio activado correctamente.'
            : 'Servicio inactivado correctamente.');
    }

    public function destroyService(AppointmentService $service): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        abort_unless((int) $service->tenant_id === $tenantId, 404);

        $hasAppointments = Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('appointment_service_id', (int) $service->id)
            ->exists();

        $hasServiceItems = AppointmentServiceItem::query()
            ->where('tenant_id', $tenantId)
            ->where('appointment_service_id', (int) $service->id)
            ->exists();

        if ($hasAppointments || $hasServiceItems) {
            return $this->redirectServicesPage('created')->withErrors([
                'appointment_services' => 'No se puede eliminar este servicio porque ya tiene citas asociadas. Puedes inactivarlo.',
            ]);
        }

        $this->syncAppointmentServiceAssignedUsers($service, []);
        $service->delete();

        return $this->redirectServicesPage('created')->with('success', 'Servicio eliminado correctamente.');
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
        $tenant = Tenant::query()->findOrFail($tenantId);
        $allowUnpaidReservation = (bool) ($tenant->appointments_allow_unpaid_reservation ?? true);
        $validated = $request->validate([
            'appointment_service_id' => ['nullable', 'integer'],
            'appointment_service_ids' => ['nullable', 'array'],
            'appointment_service_ids.*' => ['integer'],
            'appointment_id' => ['nullable', 'integer'],
            'allow_service_change' => ['nullable', 'boolean'],
            'allow_additional_services' => ['nullable', 'boolean'],
            'roll_next_appointments' => ['nullable', 'boolean'],
            'user_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'customer_id' => ['nullable', 'integer'],
            'create_customer' => ['nullable', 'boolean'],
            'contact_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'contact_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'contact_phone_code' => ['nullable', 'string', 'max:10', 'regex:/^\+?[0-9]{1,4}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_dni' => ['required_if:create_customer,1', 'nullable', 'string', 'max:100'],
            'is_retention_agent' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUSES))],
            'payment_method_id' => ['nullable', 'integer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', Rule::in(array_keys(Appointment::PAYMENT_STATUSES))],
            'require_payment_proof' => ['nullable', 'boolean'],
            'payment_proof_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'payment_entries' => ['nullable', 'array'],
            'payment_entries.*.payment_method_id' => ['nullable', 'integer'],
            'payment_entries.*.paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_entries.*.payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_entries.*.payment_status' => ['nullable', Rule::in(array_keys(Appointment::PAYMENT_STATUSES))],
            'consumptions' => ['nullable', 'array'],
            'consumptions.*.variant_id' => ['nullable', 'integer'],
            'consumptions.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $targetUser = $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->firstOrFail();
        $payloadSelectedServices = $this->resolveAppointmentServicesFromPayload($tenantId, $targetUser, $validated);
        $editingAppointment = null;

        if (!empty($validated['appointment_id'])) {
            $editingAppointment = Appointment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey((int) $validated['appointment_id'])
                ->firstOrFail();
        }

        $isCommercialLocked = $editingAppointment
            ? (
                in_array((string) ($editingAppointment->status ?? ''), ['confirmed', 'completed'], true)
                || !empty($editingAppointment->attendance_confirmed_at)
                || (int) ($editingAppointment->sales_order_id ?? 0) > 0
            )
            : false;

        $allowServiceChange = (bool) ($validated['allow_service_change'] ?? false);
        $allowAdditionalServices = (bool) ($validated['allow_additional_services'] ?? false);
        $rollNextAppointments = $this->resolveTruthyRequestFlag($request->input('roll_next_appointments', null));
        $currentServices = collect();
        $selectedServices = $payloadSelectedServices;

        $originalStartAt = $editingAppointment?->starts_at ? $editingAppointment->starts_at->copy() : null;
        $originalEndAt = $editingAppointment?->ends_at ? $editingAppointment->ends_at->copy() : null;

        if ($editingAppointment) {
            $currentServices = $this->resolveAppointmentServicesFromAppointment($editingAppointment);

            if ($isCommercialLocked) {
                $selectedServices = $currentServices;
            } elseif (!$allowServiceChange && !$allowAdditionalServices) {
                // Status/date/payment updates must keep all associated services as-is.
                $selectedServices = $currentServices;
            } elseif ($allowAdditionalServices && !$allowServiceChange) {
                // Additive mode: preserve current services and append new ones only.
                $merged = $currentServices->values();
                $existingIds = $merged->pluck('id')->map(fn ($id) => (int) $id)->all();

                $payloadSelectedServices->each(function (AppointmentService $service) use (&$merged, &$existingIds) {
                    $serviceId = (int) ($service->id ?? 0);
                    if ($serviceId > 0 && !in_array($serviceId, $existingIds, true)) {
                        $merged->push($service);
                        $existingIds[] = $serviceId;
                    }
                });

                $selectedServices = $merged;
            } elseif ($allowServiceChange) {
                // Change mode uses exactly payload selection, allowing temporary empty services.
                $selectedServices = $payloadSelectedServices;
            }
        }

        if ($selectedServices->isEmpty() && !$editingAppointment) {
            return back()->withErrors(['appointment_service_ids' => 'Debes seleccionar al menos un servicio válido.'])->withInput();
        }

        $invalidAssignedService = $selectedServices->first(function (AppointmentService $service) use ($targetUser) {
            return !$this->appointmentServiceAllowsUserId($service, (int) $targetUser->id);
        });

        if ($invalidAssignedService) {
            return back()->withErrors([
                'appointment_service_ids' => 'Uno o más servicios seleccionados no están disponibles para el profesional indicado.',
            ])->withInput();
        }

        $selectedDate = Carbon::parse($validated['scheduled_date']);
        $service = $selectedServices->first();
        $firstComeEnabled = (bool) ($tenant->appointments_first_come_enabled ?? false);
        $requestedStart = trim((string) ($validated['start_time'] ?? ''));
        $isSameSlotAsCurrentAppointment = false;

        if ($selectedServices->isEmpty()) {
            $startAt = $editingAppointment?->starts_at?->copy() ?: Carbon::parse($selectedDate->toDateString() . ' ' . ($requestedStart ?: '08:00'));
            $endAt = $editingAppointment?->ends_at?->copy() ?: $startAt->copy()->addMinutes(60);
        } else {
            $totalMinutes = $this->calculateTotalAppointmentMinutes($selectedServices);

            $availableSlots = collect($this->buildAvailableSlots(
                $tenantId,
                $targetUser,
                $service,
                $selectedDate,
                $editingAppointment?->id ? (int) $editingAppointment->id : null,
                $totalMinutes
            ));

            if ($availableSlots->isEmpty()) {
                return back()->withErrors(['start_time' => 'No hay horarios disponibles para el profesional y servicios seleccionados.'])->withInput();
            }

            if ($firstComeEnabled || $requestedStart === '') {
                $requestedStart = (string) ($availableSlots->first()['start'] ?? '');
            }

            $startAt = Carbon::parse($selectedDate->toDateString() . ' ' . $requestedStart);
            $endAt = (clone $startAt)->addMinutes($totalMinutes);

            $isSameSlotAsCurrentAppointment = $editingAppointment
                && optional($editingAppointment->starts_at)?->toDateString() === $selectedDate->toDateString()
                && optional($editingAppointment->starts_at)?->format('H:i') === $startAt->format('H:i');

            if (!$firstComeEnabled && !$availableSlots->firstWhere('start', $startAt->format('H:i')) && !($editingAppointment && $rollNextAppointments && $isSameSlotAsCurrentAppointment)) {
                return back()->withErrors(['start_time' => 'La hora seleccionada no está disponible para ese profesional.'])->withInput();
            }
        }

        if (!$rollNextAppointments && $editingAppointment && $allowAdditionalServices && $originalStartAt && $originalEndAt && $selectedServices->isNotEmpty()) {
            $sameSlot = $originalStartAt->toDateString() === $startAt->toDateString()
                && $originalStartAt->format('H:i') === $startAt->format('H:i');
            $extendedInPlace = $sameSlot && $endAt->greaterThan($originalEndAt);

            if ($extendedInPlace) {
                $rollNextAppointments = true;
            }
        }

        $normalizedContactPhone = $this->normalizePhoneWithCode(
            (string) ($validated['contact_phone'] ?? ''),
            $validated['contact_phone_code'] ?? null
        );

        $customerId = null;
        if (!empty($validated['customer_id'])) {
            $requestedCustomerId = (int) $validated['customer_id'];
            $resolvedCustomer = User::query()->select(['id', 'tenant_id'])->whereKey($requestedCustomerId)->first();

            if ($resolvedCustomer) {
                $belongsToCurrentTenant = (int) ($resolvedCustomer->tenant_id ?? 0) === $tenantId;
                $isExistingAppointmentCustomer = $editingAppointment
                    && (int) ($editingAppointment->customer_id ?? 0) === (int) $resolvedCustomer->id;

                if ($belongsToCurrentTenant || $isExistingAppointmentCustomer) {
                    $customerId = (int) $resolvedCustomer->id;
                }
            }
        }

        $shouldCreateCustomer = (bool) ($validated['create_customer'] ?? false);
        if (!$shouldCreateCustomer && !$customerId && $editingAppointment && (int) ($editingAppointment->customer_id ?? 0) > 0) {
            $customerId = (int) $editingAppointment->customer_id;
        }

        if (!$shouldCreateCustomer && !$customerId) {
            return back()->withErrors([
                'customer_id' => 'Selecciona un cliente existente o activa la opción de cliente nuevo.',
            ])->withInput();
        }

        if (!$customerId && $shouldCreateCustomer) {
            $customerName = trim((string) ($validated['contact_name'] ?? ''));
            if ($customerName === '') {
                return back()->withErrors(['contact_name' => 'Indica el nombre para registrar el nuevo cliente.'])->withInput();
            }

            if ($normalizedContactPhone === '') {
                return back()->withErrors(['contact_phone' => 'Indica el teléfono para registrar el nuevo cliente.'])->withInput();
            }

            $providedEmail = trim((string) ($validated['customer_email'] ?? ''));
            $customerEmail = $providedEmail;

            if ($customerEmail !== '') {
                $existingByEmail = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($customerEmail)])->first();
                if ($existingByEmail && (int) ($existingByEmail->tenant_id ?? 0) !== $tenantId) {
                    return back()->withErrors(['customer_email' => 'El correo ya está registrado en otra sede.'])->withInput();
                }

                if ($existingByEmail && (int) ($existingByEmail->tenant_id ?? 0) === $tenantId) {
                    $customerId = (int) $existingByEmail->id;
                }
            }

            if (!$customerId) {
                $customerRoleId = $this->resolveCustomerRoleId();
                $newCustomer = User::query()->create([
                    'name' => $customerName,
                    'email' => $customerEmail !== '' ? $customerEmail : null,
                    'password' => Hash::make(Str::random(32)),
                    'role_id' => $customerRoleId,
                    'tenant_id' => $tenantId,
                    'dni' => trim((string) ($validated['customer_dni'] ?? '')) ?: null,
                    'phone_number' => $normalizedContactPhone,
                    'is_retention_agent' => (bool) ($validated['is_retention_agent'] ?? false),
                ]);

                $customerId = (int) $newCustomer->id;
            }
        }

        $bsRate = TenantCurrency::resolveRateToBs($tenantId, 'USD');
        $rawPaymentEntries = collect();
        $basePaymentAmount = isset($validated['paid_amount']) ? round(max(0, (float) $validated['paid_amount']), 2) : 0;
        $basePaymentMethodId = (int) ($validated['payment_method_id'] ?? 0);
        $basePaymentReference = trim((string) ($validated['payment_reference'] ?? ''));
        $basePaymentStatus = (string) ($validated['payment_status'] ?? 'pending');

        if ($basePaymentAmount > 0 || $basePaymentMethodId > 0 || $basePaymentReference !== '') {
            $rawPaymentEntries->push([
                'payment_method_id' => $basePaymentMethodId,
                'paid_amount' => $basePaymentAmount,
                'payment_reference' => $basePaymentReference,
                'payment_status' => $basePaymentStatus,
            ]);
        }

        foreach (($validated['payment_entries'] ?? []) as $entry) {
            $entryMethodId = (int) ($entry['payment_method_id'] ?? 0);
            $entryAmount = round(max(0, (float) ($entry['paid_amount'] ?? 0)), 2);
            $entryReference = trim((string) ($entry['payment_reference'] ?? ''));
            $entryStatus = (string) ($entry['payment_status'] ?? 'pending');

            if ($entryAmount <= 0 && $entryMethodId <= 0 && $entryReference === '') {
                continue;
            }

            $rawPaymentEntries->push([
                'payment_method_id' => $entryMethodId,
                'paid_amount' => $entryAmount,
                'payment_reference' => $entryReference,
                'payment_status' => $entryStatus,
            ]);
        }

        $paymentMethodIds = $rawPaymentEntries
            ->pluck('payment_method_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();

        $paymentMethodsById = $paymentMethodIds->isEmpty()
            ? collect()
            : PaymentMethod::query()
                ->with('currency')
                ->where('tenant_id', $tenantId)
                ->active()
                ->whereIn('id', $paymentMethodIds->all())
                ->get()
                ->keyBy('id');

        $paymentEntries = collect();
        foreach ($rawPaymentEntries as $index => $entry) {
            $entryAmount = round(max(0, (float) ($entry['paid_amount'] ?? 0)), 2);
            if ($entryAmount <= 0) {
                continue;
            }

            $entryMethodId = (int) ($entry['payment_method_id'] ?? 0);
            if ($entryMethodId <= 0) {
                return back()->withErrors([
                    'payment_method_id' => 'Debes seleccionar un método de pago para cada monto registrado.',
                ])->withInput();
            }

            /** @var PaymentMethod|null $entryPaymentMethod */
            $entryPaymentMethod = $paymentMethodsById->get($entryMethodId);
            if (!$entryPaymentMethod) {
                return back()->withErrors([
                    'payment_method_id' => 'Uno de los métodos de pago seleccionados no está disponible.',
                ])->withInput();
            }

            $entryReference = trim((string) ($entry['payment_reference'] ?? ''));
            if ($entryPaymentMethod->usesReference() && $entryReference === '') {
                return back()->withErrors([
                    'payment_reference' => 'Este método de pago requiere referencia.',
                ])->withInput();
            }

            $entryCurrency = strtoupper(trim((string) ($entryPaymentMethod->currency->code ?? 'USD')));
            $entryAmountUsd = $entryAmount;
            if ($entryCurrency === 'BS') {
                if ($bsRate <= 0) {
                    return back()->withErrors([
                        'paid_amount' => 'No hay tasa de cambio Bs configurada para convertir el pago.',
                    ])->withInput();
                }
                $entryAmountUsd = round($entryAmount / $bsRate, 4);
            }

            $paymentEntries->push([
                'payment_method_id' => (int) $entryPaymentMethod->id,
                'payment_method_name' => (string) ($entryPaymentMethod->name ?? 'Método'),
                'paid_amount' => $entryAmount,
                'paid_amount_usd' => $entryAmountUsd,
                'payment_reference' => $entryReference !== '' ? $entryReference : null,
                'payment_currency' => $entryCurrency,
                'exchange_rate' => $entryCurrency === 'BS' ? $bsRate : null,
                'requires_proof' => (bool) $entryPaymentMethod->usesReference(),
                'payment_status' => (string) ($entry['payment_status'] ?? 'pending'),
                'recorded_at' => now()->toDateTimeString(),
                'source' => 'admin_form',
                'entry_index' => (int) $index,
            ]);
        }

        $requiresPaymentProof = $paymentEntries->contains(fn (array $entry) => (bool) ($entry['requires_proof'] ?? false));
        $proofFile = $request->file('payment_proof_image');
        if ($requiresPaymentProof && !$proofFile) {
            return back()->withErrors([
                'payment_proof_image' => 'Este pago requiere comprobante de imagen.',
            ])->withInput();
        }

        $proofPath = null;
        $proofUrl = null;
        if ($proofFile) {
            $proofPath = ImageStorage::storeUploadedImageAsWebp($proofFile, 'appointment_payment_proofs');
            $proofUrl = ImageStorage::url($proofPath);
        }

        $paidAmount = round((float) $paymentEntries->sum('paid_amount_usd'), 2);
        $primaryPaymentEntry = $paymentEntries->last();
        $paymentMethod = $primaryPaymentEntry
            ? $paymentMethodsById->get((int) ($primaryPaymentEntry['payment_method_id'] ?? 0))
            : null;
        $paymentReference = $primaryPaymentEntry['payment_reference'] ?? null;
        $paymentStatus = $validated['payment_status'] ?? ($paidAmount > 0 ? 'paid' : 'pending');

        $notesBody = $this->stripAppointmentPaymentMetaLines((string) ($validated['notes'] ?? ''));
        $paymentMetaLines = $paymentEntries->map(function (array $entry) use ($proofPath, $proofUrl) {
            return '[APPOINTMENT_PAYMENT_META]' . json_encode([
                'source' => (string) ($entry['source'] ?? 'admin_form'),
                'entry_index' => (int) ($entry['entry_index'] ?? 0),
                'payment_method_id' => (int) ($entry['payment_method_id'] ?? 0),
                'payment_method_name' => (string) ($entry['payment_method_name'] ?? 'Método'),
                'amount_registered' => (float) ($entry['paid_amount'] ?? 0),
                'amount_usd' => (float) ($entry['paid_amount_usd'] ?? 0),
                'currency_original' => (string) ($entry['payment_currency'] ?? 'USD'),
                'currency_base' => 'USD',
                'exchange_rate' => $entry['exchange_rate'] ?? null,
                'reference' => $entry['payment_reference'] ?? null,
                'payment_status' => (string) ($entry['payment_status'] ?? 'pending'),
                'proof_path' => $proofPath,
                'proof_url' => $proofUrl,
                'recorded_at' => (string) ($entry['recorded_at'] ?? now()->toDateTimeString()),
            ], JSON_UNESCAPED_UNICODE);
        })->all();

        $compiledNotes = trim(implode("\n", array_filter([
            trim($notesBody),
            trim(implode("\n", $paymentMetaLines)),
        ], fn ($value) => trim((string) $value) !== '')));
        $compiledNotes = $compiledNotes !== '' ? $compiledNotes : null;

        $consumptions = collect($validated['consumptions'] ?? [])
            ->filter(fn ($item) => !empty($item['variant_id']) && !empty($item['quantity']))
            ->values();

        if ($isCommercialLocked && $editingAppointment) {
            $consumptions = $editingAppointment->consumptions()
                ->get(['product_variant_id as variant_id', 'quantity'])
                ->map(function ($item) {
                    return [
                        'variant_id' => (int) ($item->variant_id ?? 0),
                        'quantity' => (float) ($item->quantity ?? 0),
                    ];
                })
                ->filter(fn ($item) => !empty($item['variant_id']) && !empty($item['quantity']))
                ->values();
        }

        $servicesTotalUsd = round((float) $selectedServices->sum(function (AppointmentService $selectedService) {
            return (float) ($selectedService->price ?? 0);
        }), 4);
        $consumptionsTotalUsd = 0.0;
        if ($consumptions->isNotEmpty()) {
            $variantIds = $consumptions
                ->pluck('variant_id')
                ->map(fn ($value) => (int) $value)
                ->filter(fn (int $value) => $value > 0)
                ->unique()
                ->values();

            $variantsById = $this->consumableVariantsQuery($tenantId)
                ->whereIn('id', $variantIds->all())
                ->get()
                ->keyBy('id');

            foreach ($consumptions as $item) {
                $variantId = (int) ($item['variant_id'] ?? 0);
                $quantity = round(max(0, (float) ($item['quantity'] ?? 0)), 2);
                if ($variantId <= 0 || $quantity <= 0) {
                    continue;
                }

                /** @var ProductVariant|null $variant */
                $variant = $variantsById->get($variantId);
                if (!$variant) {
                    return back()->withErrors([
                        'consumptions' => 'Uno de los consumibles seleccionados no está disponible.',
                    ])->withInput();
                }

                $unitCost = (float) ($variant->effective_price ?? $variant->price ?? 0);
                $consumptionsTotalUsd += round($unitCost * $quantity, 4);
            }
        }

        $appointmentTotalUsd = round(max(0, $servicesTotalUsd + $consumptionsTotalUsd), 2);
        if (!$allowUnpaidReservation && !$editingAppointment && $appointmentTotalUsd > 0 && ($paidAmount + 0.0001) < $appointmentTotalUsd) {
            return back()->withErrors([
                'paid_amount' => 'Para crear una cita nueva debes registrar el pago completo. Total cita: ' . number_format($appointmentTotalUsd, 2) . ' USD · Pagado: ' . number_format($paidAmount, 2) . ' USD.',
            ])->withInput();
        }

        $savedAppointment = null;
        $shiftedAppointmentsCount = 0;
        $actor = auth()->user();

        DB::transaction(function () use ($tenantId, $service, $selectedServices, $targetUser, $customerId, $validated, $startAt, $endAt, $paymentMethod, $paidAmount, $paymentStatus, $paymentReference, $consumptions, $editingAppointment, $normalizedContactPhone, $rollNextAppointments, $isCommercialLocked, $compiledNotes, &$savedAppointment, &$shiftedAppointmentsCount, $actor) {
            $appointmentPayload = [
                'tenant_id' => $tenantId,
            'appointment_service_id' => $service ? (int) $service->id : null,
                'user_id' => (int) $targetUser->id,
                'customer_id' => $customerId,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $normalizedContactPhone,
                'starts_at' => $startAt,
                'ends_at' => $endAt,
                'status' => $validated['status'] ?? 'scheduled',
                'payment_method_id' => $paymentMethod?->id,
                'paid_amount' => $paidAmount,
                'payment_currency' => 'USD',
                'payment_reference' => $paymentReference,
                'payment_status' => $paymentStatus,
                'source' => 'admin',
                'notes' => $compiledNotes,
            ];

            if ($editingAppointment) {
                $editingAppointment->fill($appointmentPayload);
                $editingAppointment->save();
                if (!$isCommercialLocked) {
                    $editingAppointment->consumptions()->delete();
                }
                $appointment = $editingAppointment;
            } else {
                $appointment = Appointment::create($appointmentPayload);
            }

            $savedAppointment = $appointment;
            $this->syncAppointmentServiceItems($appointment, $selectedServices, $startAt);

            if (!$isCommercialLocked) {
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
            }

            $appointment->load(['consumptions']);
            if ((string) ($appointment->payment_status ?? '') !== 'waived') {
                $totalDue = $this->calculateAppointmentServicesTotalPrice($appointment);
                $currentPaid = round(max(0, (float) ($appointment->paid_amount ?? 0)), 2);
                $normalizedPaymentStatus = $currentPaid <= 0
                    ? 'pending'
                    : (($totalDue > 0 && $currentPaid + 0.0001 < $totalDue) ? 'partial' : 'paid');

                if ((string) ($appointment->payment_status ?? '') !== $normalizedPaymentStatus) {
                    $appointment->payment_status = $normalizedPaymentStatus;
                    $appointment->save();
                }
            }

            if ($editingAppointment && $rollNextAppointments) {
                $shiftedAppointmentsCount = $this->shiftFollowingAppointmentsFromOverlap($appointment, $actor);
            }
        });

        $message = $editingAppointment
            ? 'Cita actualizada correctamente.'
            : 'Cita registrada correctamente.';

        if ($editingAppointment && $shiftedAppointmentsCount > 0) {
            $message .= ' Se reprogramaron automáticamente ' . $shiftedAppointmentsCount . ' cita(s) siguientes y se notificó a los clientes afectados.';
        }

        if ($savedAppointment && $actor) {
            $savedAppointment->refresh();
            $this->notifyAppointmentWorkflow(
                $savedAppointment,
                $editingAppointment ? 'updated' : 'created',
                $actor,
                null
            );
        }

        return redirect()->route('appointments.index', ['date' => $selectedDate->toDateString()])->with('success', $message);
    }

    public function workflowAction(Request $request, Appointment $appointment): JsonResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        if ((int) $appointment->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'La cita no pertenece a esta sede.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['call_customer', 'confirm_attendance', 'cancel', 'no_show', 'reschedule', 'confirm_payment'])],
            'scheduled_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'payment_method_id' => ['nullable', 'integer'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount_mode' => ['nullable', Rule::in(['replace', 'increment'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_currency' => ['nullable', 'string', 'max:10'],
            'payment_currency_original' => ['nullable', 'string', 'max:10'],
            'payment_amount_original' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'require_payment_proof' => ['nullable', 'boolean'],
            'payment_proof_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'create_sale' => ['nullable', 'boolean'],
        ]);

        $actor = auth()->user();
        $result = $this->applyWorkflowAction($appointment, $validated, $actor, false, $request);

        return response()->json($result);
    }

    public function myAppointments(Request $request): JsonResponse
    {
        $customerId = (int) (auth()->id() ?? 0);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Appointment::STATUSES))],
            'payment_status' => ['nullable', Rule::in(array_keys(Appointment::PAYMENT_STATUSES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'view' => ['nullable', Rule::in(['all', 'upcoming', 'history'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $view = (string) ($validated['view'] ?? 'all');
        $limit = (int) ($validated['limit'] ?? 80);
        $now = now();

        $relations = ['service', 'assignedUser', 'paymentMethod.currency', 'salesOrder'];
        if ($this->appointmentServiceItemsTableExists()) {
            $relations[] = 'serviceItems.service';
        }

        $query = Appointment::query()
            ->with($relations)
            ->where('customer_id', $customerId)
            ->when(!empty($validated['status']), fn ($builder) => $builder->where('status', (string) $validated['status']))
            ->when(!empty($validated['payment_status']), fn ($builder) => $builder->where('payment_status', (string) $validated['payment_status']))
            ->when(!empty($validated['from']), fn ($builder) => $builder->whereDate('starts_at', '>=', (string) $validated['from']))
            ->when(!empty($validated['to']), fn ($builder) => $builder->whereDate('starts_at', '<=', (string) $validated['to']));

        if ($view === 'upcoming') {
            $query->where('starts_at', '>=', $now);
        } elseif ($view === 'history') {
            $query->where('starts_at', '<', $now);
        }

        $appointments = $query
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get();

        $summary = [
            'total' => $appointments->count(),
            'upcoming' => $appointments->filter(fn (Appointment $item) => $item->starts_at && $item->starts_at->isFuture())->count(),
            'pending_payment' => $appointments->whereIn('payment_status', ['pending', 'partial'])->count(),
            'confirmed' => $appointments->where('status', 'confirmed')->count(),
            'scheduled' => $appointments->where('status', 'scheduled')->count(),
        ];

        $calendar = $appointments
            ->groupBy(function (Appointment $appointment) {
                return optional($appointment->starts_at)?->toDateString() ?: now()->toDateString();
            })
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'total' => $items->count(),
                    'pending_payment' => $items->whereIn('payment_status', ['pending', 'partial'])->count(),
                    'items' => $items->map(function (Appointment $appointment) {
                        $serviceLabel = $this->resolveAppointmentDisplayName($appointment);
                        return [
                            'id' => (int) $appointment->id,
                            'starts_at' => optional($appointment->starts_at)?->toDateTimeString(),
                            'ends_at' => optional($appointment->ends_at)?->toDateTimeString(),
                            'service' => $serviceLabel,
                            'status' => (string) ($appointment->status ?? 'scheduled'),
                            'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                        ];
                    })->values(),
                ];
            })
            ->values();

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $paymentMethods = PaymentMethod::query()
            ->with('currency')
            ->where('tenant_id', $tenantId)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function (PaymentMethod $method) {
                return [
                    'id' => (int) $method->id,
                    'name' => (string) ($method->name ?? 'Método de pago'),
                    'currency_code' => (string) (optional($method->currency)->code ?? 'USD'),
                    'uses_reference' => (bool) $method->usesReference(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'calendar' => $calendar,
            'filters' => [
                'view' => $view,
                'status' => $validated['status'] ?? null,
                'payment_status' => $validated['payment_status'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'limit' => $limit,
            ],
            'payment_methods' => $paymentMethods,
            'appointments' => $appointments->map(function (Appointment $appointment) {
                $startsAt = $appointment->starts_at;
                $isFuture = $startsAt ? $startsAt->isFuture() : false;
                $isPast = $startsAt ? $startsAt->isPast() : false;
                $service = $appointment->service;
                $professional = $appointment->assignedUser;
                $servicePrice = $this->calculateAppointmentServicesTotalPrice($appointment);
                $paidAmount = round((float) ($appointment->paid_amount ?? 0), 2);
                $pendingAmount = $servicePrice > 0 ? max(0, round($servicePrice - $paidAmount, 2)) : 0;

                return [
                    'id' => (int) $appointment->id,
                    'tenant_id' => (int) $appointment->tenant_id,
                    'service' => $this->resolveAppointmentDisplayName($appointment),
                    'professional' => (string) (optional($professional)->name ?? 'Profesional'),
                    'starts_at' => optional($appointment->starts_at)?->toDateTimeString(),
                    'ends_at' => optional($appointment->ends_at)?->toDateTimeString(),
                    'status' => (string) ($appointment->status ?? 'scheduled'),
                    'status_label' => (string) $appointment->status_label,
                    'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                    'payment_status_label' => (string) $appointment->payment_status_label,
                    'payment_method_id' => $appointment->payment_method_id ? (int) $appointment->payment_method_id : null,
                    'paid_amount' => $paidAmount,
                    'service_price' => $servicePrice,
                    'pending_amount' => $pendingAmount,
                    'payment_currency' => (string) ($appointment->payment_currency ?: 'USD'),
                    'sales_order_id' => $appointment->sales_order_id ? (int) $appointment->sales_order_id : null,
                    'public_order_url' => $appointment->sales_order_id ? url('/publicOrder/' . (int) $appointment->sales_order_id) : null,
                    'can_confirm' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_reschedule' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_cancel' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true),
                    'can_confirm_payment' => in_array((string) ($appointment->payment_status ?? 'pending'), ['pending', 'partial'], true),
                    'can_confirm_now' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true) && $isFuture,
                    'can_reschedule_now' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true) && $isFuture,
                    'can_cancel_now' => in_array((string) $appointment->status, ['scheduled', 'confirmed'], true) && $isFuture,
                    'is_past' => $isPast,
                    'is_future' => $isFuture,
                    'available_slots_url' => url('/api/user/appointments/' . (int) $appointment->id . '/available-slots'),
                ];
            })->values(),
        ]);
    }

    public function customerAvailableSlots(Request $request, Appointment $appointment): JsonResponse
    {
        $customerId = (int) (auth()->id() ?? 0);

        if ((int) ($appointment->customer_id ?? 0) !== $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver disponibilidad de esta cita.',
            ], 403);
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $service = $appointment->service;
        $targetUser = $appointment->assignedUser;
        $serviceItems = $this->resolveAppointmentServicesFromAppointment($appointment);
        $primaryService = $serviceItems->first() ?: $service;
        $totalMinutes = $this->calculateTotalAppointmentMinutes($serviceItems);

        if (!$primaryService || !$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'La cita no tiene servicio o profesional asignado.',
            ], 422);
        }

        $today = now()->startOfDay();
        $selectedDate = !empty($validated['date'])
            ? Carbon::parse((string) $validated['date'])->startOfDay()
            : $today->copy();

        $month = trim((string) ($validated['month'] ?? ''));
        if ($month === '') {
            $month = $selectedDate->format('Y-m');
        }

        try {
            $calendarMonthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $exception) {
            $calendarMonthStart = $selectedDate->copy()->startOfMonth();
        }

        $calendarMonthEnd = $calendarMonthStart->copy()->endOfMonth();
        $slots = !empty($validated['date'])
            ? $this->buildAvailableSlots((int) $appointment->tenant_id, $targetUser, $primaryService, $selectedDate, (int) $appointment->id, $totalMinutes)
            : [];

        $calendar = [];
        $cursor = $calendarMonthStart->copy();
        while ($cursor->lessThanOrEqualTo($calendarMonthEnd)) {
            $daySlots = $this->buildAvailableSlots((int) $appointment->tenant_id, $targetUser, $primaryService, $cursor, (int) $appointment->id, $totalMinutes);
            $calendar[] = [
                'date' => $cursor->toDateString(),
                'slots_count' => count($daySlots),
                'has_slots' => count($daySlots) > 0,
                'is_today' => $cursor->isSameDay($today),
            ];

            $cursor->addDay();
        }

        return response()->json([
            'success' => true,
            'date' => $selectedDate->toDateString(),
            'appointment_id' => (int) $appointment->id,
            'service_id' => (int) ($primaryService->id ?? 0),
            'user_id' => (int) ($targetUser->id ?? 0),
            'calendar_month' => $calendarMonthStart->format('Y-m'),
            'calendar' => $calendar,
            'today' => $today->toDateString(),
            'total_slots' => count($slots),
            'suggested_slots' => array_slice($slots, 0, 6),
            'slots' => $slots,
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
            'paid_amount_mode' => ['nullable', Rule::in(['replace', 'increment'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_currency' => ['nullable', 'string', 'max:10'],
            'payment_currency_original' => ['nullable', 'string', 'max:10'],
            'payment_amount_original' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'require_payment_proof' => ['nullable', 'boolean'],
            'payment_proof_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'create_sale' => ['nullable', 'boolean'],
        ]);

        $actionCheck = $this->canCustomerPerformWorkflowAction($appointment, (string) ($validated['action'] ?? ''));
        if (!$actionCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $actionCheck['message'],
            ], 422);
        }

        $actor = auth()->user();
        $result = $this->applyWorkflowAction($appointment, $validated, $actor, true, $request);

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
            'day_of_weeks' => ['nullable', 'array', 'min:1'],
            'day_of_weeks.*' => ['integer', Rule::in(array_keys(UserScheduleRule::WEEK_DAYS))],
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

        $defaultAssignedUserId = (int) ($this->appointmentServiceAssignedUserIds($service)[0] ?? 0);
        $targetUserId = !empty($validated['user_id'])
            ? (int) $this->appointmentUsersQuery($tenantId)->whereKey((int) $validated['user_id'])->value('id')
            : $defaultAssignedUserId;

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
        $selectedDays = collect($validated['day_of_weeks'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => in_array($value, array_keys(UserScheduleRule::WEEK_DAYS), true))
            ->unique()
            ->sort()
            ->values();

        if ($selectedDays->isEmpty()) {
            $fallbackDay = isset($validated['preferred_day_of_week'])
                ? (int) $validated['preferred_day_of_week']
                : (int) $startDate->dayOfWeek;

            $selectedDays = collect([$fallbackDay]);
        }

        $package = null;
        DB::transaction(function () use (&$package, $tenantId, $validated, $service, $sessionsCount, $repeatEveryWeeks, $targetUser, $customerId, $startDate, $preferredTime, $selectedDays) {
            $package = AppointmentPackage::create([
                'tenant_id' => $tenantId,
                'name' => trim((string) $validated['name']),
                'description' => $validated['description'] ?? null,
                'appointment_service_id' => (int) $service->id,
                'sessions_count' => $sessionsCount,
                'repeat_every_weeks' => $repeatEveryWeeks,
                'preferred_day_of_week' => (int) $selectedDays->first(),
                'preferred_time' => $preferredTime,
                'price' => isset($validated['price']) ? (float) $validated['price'] : (float) ($service->price ?? 0),
                'is_active' => true,
            ]);

            $sessionDates = collect([$startDate->copy()]);
            $cycle = 0;

            while ($sessionDates->count() < $sessionsCount && $cycle < 400) {
                $weekStart = $startDate->copy()->startOfWeek(Carbon::SUNDAY)->addWeeks($cycle * $repeatEveryWeeks);

                foreach ($selectedDays as $dayOfWeek) {
                    $candidateDate = $weekStart->copy()->addDays((int) $dayOfWeek)->startOfDay();
                    if ($candidateDate->lte($startDate)) {
                        continue;
                    }

                    $sessionDates->push($candidateDate);
                    if ($sessionDates->count() >= $sessionsCount) {
                        break;
                    }
                }

                $cycle += 1;
            }

            $sessionDates = $sessionDates
                ->filter(fn ($date) => $date instanceof Carbon)
                ->sortBy(fn (Carbon $date) => $date->timestamp)
                ->values();

            // Safety net: ensure the package always creates the exact number of sessions requested.
            if ($sessionDates->count() < $sessionsCount) {
                $firstDay = (int) $selectedDays->first();
                $fallbackDate = $sessionDates->isNotEmpty()
                    ? $sessionDates->last()->copy()->addWeeks($repeatEveryWeeks)
                    : $startDate->copy();

                while ((int) $fallbackDate->dayOfWeek !== $firstDay) {
                    $fallbackDate->addDay();
                }

                while ($sessionDates->count() < $sessionsCount) {
                    if ($fallbackDate->lte($startDate)) {
                        $fallbackDate->addWeeks($repeatEveryWeeks);
                        continue;
                    }
                    $sessionDates->push($fallbackDate->copy());
                    $fallbackDate->addWeeks($repeatEveryWeeks);
                }
            }

            if ($sessionDates->isEmpty()) {
                $sessionDates->push($startDate->copy());
            }

            foreach ($sessionDates->take($sessionsCount)->values() as $index => $sessionDate) {
                $sessionNumber = $index + 1;
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
                    'workflow_note' => 'Sesión ' . $sessionNumber . ' de ' . $sessionsCount,
                ]);

                AppointmentPackageSession::create([
                    'tenant_id' => $tenantId,
                    'appointment_package_id' => (int) $package->id,
                    'appointment_id' => (int) $appointment->id,
                    'session_number' => $sessionNumber,
                    'scheduled_for' => $startAt,
                    'status' => 'scheduled',
                ]);
            }
        });

        return redirect()
            ->route('appointments.services.index', ['tab' => 'packages'])
            ->with('success', 'Paquete de citas creado correctamente con ' . $sessionsCount . ' sesiones.');
    }

    private function applyWorkflowAction(Appointment $appointment, array $validated, User $actor, bool $fromCustomer, ?Request $request = null): array
    {
        $tenantId = (int) $appointment->tenant_id;
        $action = (string) ($validated['action'] ?? '');
        $note = trim((string) ($validated['note'] ?? '')) ?: null;

        $tenant = Tenant::query()->find((int) $tenantId);
        $firstComeEnabled = (bool) ($tenant?->appointments_first_come_enabled ?? false);

        if ($action === 'reschedule' && (empty($validated['scheduled_date']) || (!$firstComeEnabled && empty($validated['start_time'])))) {
            return [
                'success' => false,
                'message' => 'Para reprogramar debes indicar fecha y hora.',
            ];
        }

        try {
            DB::transaction(function () use ($appointment, $validated, $action, $note, $actor, $fromCustomer, $tenantId, $firstComeEnabled, $request) {
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
                        $serviceItems = $this->resolveAppointmentServicesFromAppointment($appointment);
                        $service = $serviceItems->first();
                        $targetUser = $appointment->assignedUser;
                        $requestedStart = trim((string) ($validated['start_time'] ?? ''));

                        if ($selectedDate->toDateString() < now()->toDateString()) {
                            throw new \RuntimeException('No puedes reprogramar citas para fechas pasadas.');
                        }

                        if (!$service || !$targetUser) {
                            throw new \RuntimeException('No se pudo reprogramar porque faltan datos de servicio o profesional.');
                        }

                        $totalMinutes = $this->calculateTotalAppointmentMinutes($serviceItems);

                        $availableSlots = collect($this->buildAvailableSlots(
                            $tenantId,
                            $targetUser,
                            $service,
                            $selectedDate,
                            (int) $appointment->id,
                            $totalMinutes
                        ));

                        if ($availableSlots->isEmpty()) {
                            throw new \RuntimeException('No hay horarios disponibles para reprogramar esta cita.');
                        }

                        if ($firstComeEnabled || $requestedStart === '') {
                            $requestedStart = (string) ($availableSlots->first()['start'] ?? '');
                        }

                        $startAt = Carbon::parse($selectedDate->toDateString() . ' ' . $requestedStart);

                        if ($this->isRescheduleDateTimeInPast($startAt)) {
                            throw new \RuntimeException('La nueva hora debe ser futura.');
                        }

                        if ($fromCustomer && !$this->passesCustomerRescheduleLeadTime($startAt)) {
                            throw new \RuntimeException('Para reprogramar desde cliente, selecciona una hora con al menos 60 minutos de anticipación.');
                        }

                        if (!$availableSlots->firstWhere('start', $startAt->format('H:i'))) {
                            throw new \RuntimeException('La hora elegida no está disponible para reprogramar esta cita.');
                        }

                        $appointment->rescheduled_from_appointment_id = $appointment->rescheduled_from_appointment_id ?: (int) $appointment->id;
                        $appointment->rescheduled_at = now();
                        $appointment->rescheduled_by_user_id = (int) $actor->id;
                        $appointment->starts_at = $startAt;
                        $appointment->ends_at = $startAt->copy()->addMinutes($totalMinutes);
                        $appointment->confirmation_reminder_sent_at = null;
                        $appointment->status = 'scheduled';
                        $appointment->workflow_tag = 'rescheduled';
                        $appointment->workflow_note = $note ?: 'Cita reprogramada.';
                        $this->syncAppointmentServiceItems($appointment, $serviceItems, $startAt);
                        break;

                    case 'confirm_payment':
                        $rawPaidAmount = isset($validated['paid_amount'])
                            ? (float) $validated['paid_amount']
                            : (float) ($appointment->paid_amount ?? 0);
                        $rawPaidAmount = round(max($rawPaidAmount, 0), 2);

                        $paidAmountMode = (string) ($validated['paid_amount_mode'] ?? 'replace');
                        $paidAmount = $rawPaidAmount;
                        if ($paidAmountMode === 'increment') {
                            $paidAmount = round(max(0, (float) ($appointment->paid_amount ?? 0)) + $rawPaidAmount, 2);
                        }

                        if ($paidAmountMode === 'increment' && $rawPaidAmount <= 0) {
                            throw new \RuntimeException('Debes indicar un abono mayor a 0 para confirmar el pago.');
                        }

                        if ($paidAmountMode !== 'increment' && $paidAmount <= 0) {
                            $servicePrice = $this->calculateAppointmentServicesTotalPrice($appointment);
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

                        $paymentReference = trim((string) ($validated['payment_reference'] ?? $appointment->payment_reference ?? '')) ?: null;
                        if ($paymentMethod && $paymentMethod->usesReference() && !$paymentReference) {
                            throw new \RuntimeException('Este método de pago requiere referencia.');
                        }

                        $requirePaymentProof = (bool) ($validated['require_payment_proof'] ?? false);
                        $proofFile = $request?->file('payment_proof_image');
                        if ($requirePaymentProof && $paymentMethod && $paymentMethod->usesReference() && !$proofFile) {
                            throw new \RuntimeException('Este método de pago requiere comprobante.');
                        }

                        $proofPath = null;
                        $proofUrl = null;
                        if ($proofFile) {
                            $proofPath = ImageStorage::storeUploadedImageAsWebp($proofFile, 'appointment_payment_proofs');
                            $proofUrl = ImageStorage::url($proofPath);
                        }

                        $servicePrice = $this->calculateAppointmentServicesTotalPrice($appointment);
                        $paymentStatus = ($servicePrice > 0 && $paidAmount < $servicePrice)
                            ? 'partial'
                            : 'paid';

                        $paymentCurrency = strtoupper(trim((string) ($validated['payment_currency']
                            ?? $paymentMethod?->currency?->code
                            ?? ($appointment->payment_currency ?: 'USD'))));
                        if ($paymentCurrency === '') {
                            $paymentCurrency = 'USD';
                        }

                        $paymentCurrencyOriginal = strtoupper(trim((string) ($validated['payment_currency_original'] ?? '')));
                        $paymentAmountOriginal = isset($validated['payment_amount_original'])
                            ? round(max(0, (float) $validated['payment_amount_original']), 2)
                            : null;
                        $exchangeRate = isset($validated['exchange_rate'])
                            ? round(max(0, (float) $validated['exchange_rate']), 4)
                            : null;

                        $appointment->paid_amount = $paidAmount;
                        $appointment->payment_method_id = $paymentMethod?->id;
                        $appointment->payment_currency = $paymentCurrency;
                        $appointment->payment_reference = $paymentReference;
                        $appointment->payment_status = $paymentStatus;
                        $appointment->status = 'confirmed';
                        $appointment->attendance_confirmed_at = $appointment->attendance_confirmed_at ?: now();
                        $appointment->attendance_confirmed_by_user_id = $appointment->attendance_confirmed_by_user_id ?: (int) $actor->id;
                        $appointment->workflow_tag = 'payment_confirmed';
                        $appointment->workflow_note = $note ?: ($paymentStatus === 'partial'
                            ? 'Pago parcial registrado en cita.'
                            : 'Pago confirmado en cita.');

                        $appointment->notes = $this->stripAppointmentPaymentMetaLines((string) ($appointment->notes ?? ''));

                        $createSale = (bool) ($validated['create_sale'] ?? !$fromCustomer);
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
            'created' => 'Cita registrada.',
            'updated' => 'Cita actualizada.',
            'call_customer' => 'Se registró la llamada al cliente.',
            'confirm_attendance' => 'La asistencia quedó confirmada.',
            'cancel' => 'La cita fue cancelada.',
            'no_show' => 'Se registró la inasistencia.',
            'reschedule' => 'La cita fue reprogramada correctamente.',
            'confirm_payment' => 'Pago confirmado y cita actualizada.',
            default => 'Cita actualizada.',
        };
    }

    private function canCustomerPerformWorkflowAction(Appointment $appointment, string $action): array
    {
        $appointment->loadMissing(['service']);

        $status = (string) ($appointment->status ?? 'scheduled');
        $paymentStatus = (string) ($appointment->payment_status ?? 'pending');
        $startsAt = $appointment->starts_at;
        $isFuture = $startsAt ? $startsAt->isFuture() : false;

        if ($action === 'confirm_attendance' && !in_array($status, ['scheduled', 'confirmed'], true)) {
            return ['allowed' => false, 'message' => 'Esta cita no admite confirmación de asistencia.'];
        }

        if ($action === 'cancel' && !in_array($status, ['scheduled', 'confirmed'], true)) {
            return ['allowed' => false, 'message' => 'Solo puedes cancelar citas activas.'];
        }

        if (($action === 'cancel' || $action === 'reschedule') && !$isFuture) {
            return ['allowed' => false, 'message' => 'Solo puedes gestionar cambios en citas futuras.'];
        }

        if ($action === 'reschedule' && !in_array($status, ['scheduled', 'confirmed'], true)) {
            return ['allowed' => false, 'message' => 'Solo puedes reprogramar citas activas.'];
        }

        if ($action === 'confirm_payment' && !in_array($paymentStatus, ['pending', 'partial'], true)) {
            return ['allowed' => false, 'message' => 'El pago de esta cita ya fue confirmado.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    private function createSaleOrderFromAppointment(Appointment $appointment, ?PaymentMethod $paymentMethod = null): ?SalesOrder
    {
        if ($appointment->sales_order_id) {
            return SalesOrder::query()->find((int) $appointment->sales_order_id);
        }

        if (!(int) ($appointment->customer_id ?? 0)) {
            return null;
        }

        $appointment->loadMissing(['service.productVariant.product', 'consumptions.variant.product']);
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

        $appointment->consumptions
            ->filter(function (AppointmentConsumption $consumption) {
                return (int) ($consumption->product_variant_id ?? 0) > 0 && (float) ($consumption->quantity ?? 0) > 0;
            })
            ->each(function (AppointmentConsumption $consumption) use ($salesOrder) {
                $consumptionVariant = $consumption->variant;
                if (!$consumptionVariant) {
                    return;
                }

                $quantity = round(max(0, (float) ($consumption->quantity ?? 0)), 2);
                if ($quantity <= 0) {
                    return;
                }

                $unitCost = round((float) ($consumption->unit_cost ?? $consumptionVariant->effective_price ?? $consumptionVariant->price ?? 0), 2);
                $amount = round($quantity * $unitCost, 2);

                if ($amount <= 0) {
                    return;
                }

                SalesOrderDetail::create([
                    'sales_order_id' => (int) $salesOrder->id,
                    'product_variant_id' => (int) $consumptionVariant->id,
                    'quantity' => $quantity,
                    'price' => $unitCost,
                    'amount' => $amount,
                ]);

                $consumptionVariant->stock = max(0, round((float) $consumptionVariant->stock - $quantity, 4));
                $consumptionVariant->save();
            });

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
        $appointment->loadMissing(['service', 'customer', 'assignedUser']);

        $serviceLabel = (string) (optional($appointment->service)->display_name ?? optional($appointment->service)->name ?? 'Servicio');
        $customerLabel = (string) ($appointment->contact_name ?: optional($appointment->customer)->name ?: 'Cliente');
        $startsAt = $appointment->starts_at;
        $dayLabel = $startsAt ? $startsAt->format('d/m/Y') : 'Sin fecha';
        $timeLabel = $startsAt ? $startsAt->format('H:i') : '--:--';

        $payload = [
            'title' => 'Actualización de cita',
            'message' => 'Servicio: ' . $serviceLabel
                . ' · Cliente: ' . $customerLabel
                . ' · Día: ' . $dayLabel
                . ' · Hora: ' . $timeLabel
                . ' · ' . $this->workflowActionMessage($action),
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

    private function shiftFollowingAppointmentsFromOverlap(Appointment $anchorAppointment, ?User $actor = null): int
    {
        $anchorAppointment->loadMissing(['assignedUser']);

        $anchorStartAt = $anchorAppointment->starts_at ? $anchorAppointment->starts_at->copy() : null;
        $anchorEndAt = $anchorAppointment->ends_at ? $anchorAppointment->ends_at->copy() : null;
        if (!$anchorStartAt || !$anchorEndAt) {
            return 0;
        }

        $followingAppointments = Appointment::query()
            ->where('tenant_id', (int) $anchorAppointment->tenant_id)
            ->where('user_id', (int) $anchorAppointment->user_id)
            ->whereDate('starts_at', $anchorStartAt->toDateString())
            ->where('id', '!=', (int) $anchorAppointment->id)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('starts_at', '>=', $anchorStartAt)
            ->orderBy('starts_at')
            ->lockForUpdate()
            ->get();

        $cursor = $anchorEndAt->copy();
        $shiftedCount = 0;

        foreach ($followingAppointments as $affectedAppointment) {
            $originalStartAt = $affectedAppointment->starts_at ? $affectedAppointment->starts_at->copy() : null;
            $originalEndAt = $affectedAppointment->ends_at ? $affectedAppointment->ends_at->copy() : null;
            if (!$originalStartAt || !$originalEndAt) {
                continue;
            }

            $durationMinutes = max(15, $originalStartAt->diffInMinutes($originalEndAt));

            if ($originalStartAt->lt($cursor)) {
                $newStartAt = $cursor->copy();
                $newEndAt = $newStartAt->copy()->addMinutes($durationMinutes);

                $affectedAppointment->starts_at = $newStartAt;
                $affectedAppointment->ends_at = $newEndAt;
                $affectedAppointment->rescheduled_at = now();
                $affectedAppointment->rescheduled_by_user_id = $actor ? (int) $actor->id : null;
                $affectedAppointment->workflow_tag = 'auto_shifted';
                $affectedAppointment->workflow_note = 'Cita reprogramada automáticamente por ajuste de duración en una cita previa.';
                $affectedAppointment->save();

                $serviceItems = $this->resolveAppointmentServicesFromAppointment($affectedAppointment);
                if ($serviceItems->isNotEmpty()) {
                    $this->syncAppointmentServiceItems($affectedAppointment, $serviceItems, $newStartAt);
                }

                if ($actor) {
                    $this->notifyAppointmentAutoShift($affectedAppointment, $originalStartAt, $originalEndAt, $actor, $anchorAppointment);
                }
                $shiftedCount++;
                $cursor = $newEndAt;
                continue;
            }

            if ($originalEndAt->gt($cursor)) {
                $cursor = $originalEndAt->copy();
            }
        }

        return $shiftedCount;
    }

    private function notifyAppointmentAutoShift(Appointment $affectedAppointment, Carbon $oldStartAt, Carbon $oldEndAt, User $actor, Appointment $anchorAppointment): void
    {
        $affectedAppointment->loadMissing(['service', 'customer', 'assignedUser']);
        $anchorAppointment->loadMissing(['service']);

        $serviceLabel = (string) (optional($affectedAppointment->service)->display_name ?? optional($affectedAppointment->service)->name ?? 'Servicio');
        $triggerServiceLabel = (string) (optional($anchorAppointment->service)->display_name ?? optional($anchorAppointment->service)->name ?? 'Servicio previo');
        $customerLabel = (string) ($affectedAppointment->contact_name ?: optional($affectedAppointment->customer)->name ?: 'Cliente');
        $newStartAt = $affectedAppointment->starts_at ? $affectedAppointment->starts_at->copy() : null;
        $newEndAt = $affectedAppointment->ends_at ? $affectedAppointment->ends_at->copy() : null;

        $payload = [
            'title' => 'Cita reprogramada automáticamente',
            'message' => 'Tu cita de ' . $serviceLabel
                . ' fue movida por ajuste operativo en una cita previa (' . $triggerServiceLabel . '). '
                . 'Horario anterior: ' . $oldStartAt->format('d/m/Y H:i') . ' - ' . $oldEndAt->format('H:i')
                . '. Nuevo horario: ' . ($newStartAt ? $newStartAt->format('d/m/Y H:i') : '--')
                . ' - ' . ($newEndAt ? $newEndAt->format('H:i') : '--') . '.',
            'type' => 'warning',
            'tenant_id' => (int) $affectedAppointment->tenant_id,
            'order_id' => $affectedAppointment->sales_order_id ? (int) $affectedAppointment->sales_order_id : null,
            'action' => 'appointment_auto_shifted',
            'meta' => [
                'appointment_id' => (int) $affectedAppointment->id,
                'status' => (string) $affectedAppointment->status,
                'payment_status' => (string) ($affectedAppointment->payment_status ?? 'pending'),
                'customer' => $customerLabel,
                'old_start' => $oldStartAt->toDateTimeString(),
                'old_end' => $oldEndAt->toDateTimeString(),
                'new_start' => $newStartAt?->toDateTimeString(),
                'new_end' => $newEndAt?->toDateTimeString(),
                'actor' => (string) ($actor->name ?? 'Sistema'),
            ],
        ];

        WorkflowNotifier::notifyTenantRoles((int) $affectedAppointment->tenant_id, ['owner', 'administrador', 'admin', 'vendedor'], $payload);
        WorkflowNotifier::notifyUser($affectedAppointment->customer, $payload);
        WorkflowNotifier::notifyUser($affectedAppointment->assignedUser, $payload);
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
        $appointmentOnlyVariantIds = $this->appointmentOnlyVariantIdsForTenant($tenantId);

        return ProductVariant::query()
            ->with('product')
            ->whereNotIn('id', $appointmentOnlyVariantIds)
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->where('is_active', true);
            })
            ->orderBy('product_id')
            ->orderBy('size');
    }

    private function appointmentOnlyVariantIdsForTenant(int $tenantId): array
    {
        if ($tenantId <= 0) {
            return [];
        }

        return AppointmentService::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('product_variant_id')
            ->pluck('product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
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
            $serviceItems = $this->resolveAppointmentServicesFromAppointment($appointment);
            $primaryService = $serviceItems->first() ?: $appointment->service;
            $serviceIds = $serviceItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            if (empty($serviceIds) && $appointment->appointment_service_id) {
                $serviceIds = [(int) $appointment->appointment_service_id];
            }

            return [
                'id' => (int) $appointment->id,
                'service_id' => (int) ($primaryService->id ?? $appointment->appointment_service_id ?? 0),
                'service_ids' => $serviceIds,
                'user_id' => (int) ($appointment->user_id ?? 0),
                'customer_id' => (int) ($appointment->customer_id ?? 0),
                'source' => (string) ($appointment->source ?? ''),
                'date' => $appointment->starts_at->toDateString(),
                'title' => $this->resolveAppointmentDisplayName($appointment, $serviceItems),
                'professional' => (string) ($appointment->assignedUser->name ?? 'Profesional'),
                'customer' => (string) ($appointment->customer->name ?? $appointment->contact_name ?? 'Cliente sin registro'),
                'customer_email' => (string) ($appointment->customer->email ?? ''),
                'customer_dni' => (string) ($appointment->customer->dni ?? ''),
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
                'payment_entries' => $this->resolveAppointmentPaymentEntries($appointment),
                'color_hex' => (string) ($primaryService->color_hex ?? optional($appointment->service)->color_hex ?? '#0f172a'),
                'notes' => $this->stripAppointmentPaymentMetaLines((string) ($appointment->notes ?? '')),
                'consumptions' => $appointment->consumptions
                    ->map(function (AppointmentConsumption $consumption) {
                        return [
                            'variant_id' => (int) ($consumption->product_variant_id ?? 0),
                            'quantity' => round((float) ($consumption->quantity ?? 0), 2),
                            'unit_cost' => round((float) ($consumption->unit_cost ?? 0), 2),
                            'amount' => round((float) ($consumption->amount ?? 0), 2),
                        ];
                    })
                    ->filter(fn (array $item) => (int) ($item['variant_id'] ?? 0) > 0 && (float) ($item['quantity'] ?? 0) > 0)
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    private function stripAppointmentPaymentMetaLines(string $notes): string
    {
        $prefix = '[APPOINTMENT_PAYMENT_META]';

        return collect(preg_split('/\r\n|\r|\n/', (string) $notes) ?: [])
            ->map(fn ($line) => rtrim((string) $line))
            ->filter(fn ($line) => !Str::startsWith(trim((string) $line), $prefix))
            ->implode("\n");
    }

    private function resolveAppointmentPaymentEntries(Appointment $appointment): array
    {
        $prefix = '[APPOINTMENT_PAYMENT_META]';
        $entries = collect();

        $noteLines = preg_split('/\r\n|\r|\n/', (string) ($appointment->notes ?? '')) ?: [];
        foreach ($noteLines as $line) {
            $trimmed = trim((string) $line);
            if (!Str::startsWith($trimmed, $prefix)) {
                continue;
            }

            $json = trim((string) Str::after($trimmed, $prefix));
            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                continue;
            }

            $amount = round(max(0, (float) ($decoded['amount_registered'] ?? $decoded['amount'] ?? 0)), 2);
            if ($amount <= 0) {
                continue;
            }

            $entries->push([
                'payment_method_id' => isset($decoded['payment_method_id']) ? (int) $decoded['payment_method_id'] : null,
                'payment_method_name' => (string) ($decoded['payment_method_name'] ?? $decoded['payment_method'] ?? ''),
                'paid_amount' => $amount,
                'paid_amount_usd' => round(max(0, (float) ($decoded['amount_usd'] ?? 0)), 4),
                'payment_reference' => trim((string) ($decoded['reference'] ?? '')) ?: null,
                'payment_currency' => (string) ($decoded['currency_original'] ?? $decoded['currency_base'] ?? $appointment->payment_currency ?? 'USD'),
                'payment_status' => (string) ($decoded['payment_status'] ?? $appointment->payment_status ?? 'pending'),
                'recorded_at' => (string) ($decoded['recorded_at'] ?? ''),
                'source' => (string) ($decoded['source'] ?? 'legacy_meta'),
            ]);
        }

        if ($entries->isEmpty() && (float) ($appointment->paid_amount ?? 0) > 0) {
            $entries->push([
                'payment_method_id' => $appointment->payment_method_id ? (int) $appointment->payment_method_id : null,
                'payment_method_name' => (string) ($appointment->paymentMethod->name ?? ''),
                'paid_amount' => round((float) ($appointment->paid_amount ?? 0), 2),
                'payment_reference' => trim((string) ($appointment->payment_reference ?? '')) ?: null,
                'payment_currency' => (string) ($appointment->payment_currency ?? 'USD'),
                'payment_status' => (string) ($appointment->payment_status ?? 'pending'),
                'recorded_at' => '',
                'source' => 'legacy_top_level',
            ]);
        }

        return $entries->values()->all();
    }

    private function buildAvailableSlots(int $tenantId, User $targetUser, AppointmentService $service, Carbon $selectedDate, ?int $ignoreAppointmentId = null, ?int $totalDurationMinutes = null): array
    {
        if (!$this->appointmentServiceAllowsUserId($service, (int) $targetUser->id)) {
            return [];
        }

        $tenant = Tenant::query()
            ->select(['id', 'working_days', 'opening_time', 'closing_time'])
            ->find($tenantId);

        if ($tenant && !$this->isDateAllowedByTenantWorkingDays($tenant, $selectedDate)) {
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

        $durationMinutes = max(15, (int) ($totalDurationMinutes ?? ((int) ($service->duration_minutes ?? 60) + (int) ($service->buffer_minutes ?? 0))));
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
            $windowStart = Carbon::parse($selectedDate->toDateString() . ' ' . $rule->start_time);
            $windowEnd = Carbon::parse($selectedDate->toDateString() . ' ' . $rule->end_time);

            if ($tenant) {
                $tenantOpeningTime = trim((string) ($tenant->opening_time ?? ''));
                $tenantClosingTime = trim((string) ($tenant->closing_time ?? ''));

                if ($tenantOpeningTime !== '') {
                    $tenantOpenAt = Carbon::parse($selectedDate->toDateString() . ' ' . substr($tenantOpeningTime, 0, 5));
                    if ($windowStart->lt($tenantOpenAt)) {
                        $windowStart = $tenantOpenAt;
                    }
                }

                if ($tenantClosingTime !== '') {
                    $tenantCloseAt = Carbon::parse($selectedDate->toDateString() . ' ' . substr($tenantClosingTime, 0, 5));
                    if ($windowEnd->gt($tenantCloseAt)) {
                        $windowEnd = $tenantCloseAt;
                    }
                }
            }

            if ($windowStart->gte($windowEnd)) {
                continue;
            }

            $cursor = $windowStart->copy();

            while ($cursor->copy()->addMinutes($durationMinutes) <= $windowEnd) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($durationMinutes);

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

    private function isDateAllowedByTenantWorkingDays(Tenant $tenant, Carbon $selectedDate): bool
    {
        $workingDays = collect($tenant->working_days ?? [])
            ->map(fn ($day) => strtolower(trim((string) $day)))
            ->filter()
            ->values();

        if ($workingDays->isEmpty()) {
            return true;
        }

        $dayMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        $targetDay = $dayMap[(int) $selectedDate->dayOfWeek] ?? '';

        return $targetDay !== '' && $workingDays->contains($targetDay);
    }

    private function resolveAppointmentServicesFromPayload(int $tenantId, User $targetUser, array $validated): Collection
    {
        $serviceIds = collect($validated['appointment_service_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($serviceIds->isEmpty() && !empty($validated['service_ids']) && is_array($validated['service_ids'])) {
            $serviceIds = collect($validated['service_ids'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values();
        }

        if ($serviceIds->isEmpty() && !empty($validated['appointment_service_id'])) {
            $serviceIds = collect([(int) $validated['appointment_service_id']]);
        }

        if ($serviceIds->isEmpty() && !empty($validated['service_id'])) {
            $serviceIds = collect([(int) $validated['service_id']]);
        }

        if ($serviceIds->isEmpty()) {
            return collect();
        }

        $services = AppointmentService::query()
            ->with(['productVariant.product'])
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $serviceIds->all())
            ->get()
            ->keyBy('id');

        $ordered = $serviceIds
            ->map(fn (int $serviceId) => $services->get($serviceId))
            ->filter()
            ->values();

        if ($ordered->count() !== $serviceIds->count()) {
            return collect();
        }

        $invalidAssigned = $ordered->first(function (AppointmentService $service) use ($targetUser) {
            return !$this->appointmentServiceAllowsUserId($service, (int) $targetUser->id);
        });

        if ($invalidAssigned) {
            return collect();
        }

        return $ordered;
    }

    private function resolveAppointmentServicesFromAppointment(Appointment $appointment): Collection
    {
        if ($this->appointmentServiceItemsTableExists()) {
            $appointment->loadMissing(['serviceItems.service', 'service']);
        } else {
            $appointment->loadMissing(['service']);
        }

        $items = collect();
        if ($this->appointmentServiceItemsTableExists()) {
            $items = $appointment->serviceItems
                ->pluck('service')
                ->filter()
                ->values();
        }

        if ($items->isNotEmpty()) {
            return $items;
        }

        if ($appointment->service) {
            return collect([$appointment->service]);
        }

        return collect();
    }

    private function appointmentServiceItemsTableExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = DB::getSchemaBuilder()->hasTable('appointment_service_items');
        } catch (\Throwable $exception) {
            $exists = false;
        }

        return (bool) $exists;
    }

    private function appointmentServiceUserPivotTableExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = DB::getSchemaBuilder()->hasTable('appointment_service_user');
        } catch (\Throwable $exception) {
            $exists = false;
        }

        return (bool) $exists;
    }

    private function normalizeAssignedUserIdsFromRequest(int $tenantId, array $validated): array
    {
        $rawIds = collect($validated['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($rawIds->isEmpty() && !empty($validated['user_id'])) {
            $rawIds = collect([(int) $validated['user_id']]);
        }

        if ($rawIds->isEmpty()) {
            return [];
        }

        return $this->appointmentUsersQuery($tenantId)
            ->whereIn('id', $rawIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function syncAppointmentServiceAssignedUsers(AppointmentService $service, array $assignedUserIds): void
    {
        $normalizedIds = collect($assignedUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($this->appointmentServiceUserPivotTableExists()) {
            $service->assignedUsers()->sync($normalizedIds);
        }

        $primaryAssignedUserId = !empty($normalizedIds) ? (int) $normalizedIds[0] : null;
        if ((int) ($service->user_id ?? 0) !== (int) ($primaryAssignedUserId ?? 0)) {
            $service->user_id = $primaryAssignedUserId;
            $service->save();
        }
    }

    private function appointmentServiceAssignedUserIds(AppointmentService $service): array
    {
        if ($this->appointmentServiceUserPivotTableExists()) {
            $users = $service->relationLoaded('assignedUsers')
                ? $service->assignedUsers
                : $service->assignedUsers()->get(['users.id']);

            $ids = collect($users)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (!empty($ids)) {
                return $ids;
            }
        }

        $legacyUserId = (int) ($service->user_id ?? 0);
        return $legacyUserId > 0 ? [$legacyUserId] : [];
    }

    private function appointmentServiceAllowsUserId(AppointmentService $service, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $assignedUserIds = $this->appointmentServiceAssignedUserIds($service);

        if (empty($assignedUserIds)) {
            return true;
        }

        return in_array($userId, $assignedUserIds, true);
    }

    private function redirectServicesPage(string $tab = 'create'): RedirectResponse
    {
        $resolvedTab = in_array($tab, ['create', 'created'], true) ? $tab : 'create';

        return redirect()->route('appointments.services.index', ['tab' => $resolvedTab]);
    }

    private function calculateTotalAppointmentMinutes(Collection $services): int
    {
        $total = (int) $services->sum(function (AppointmentService $service) {
            $duration = max(15, (int) ($service->duration_minutes ?? 60));
            $buffer = max(0, (int) ($service->buffer_minutes ?? 0));

            return $duration + $buffer;
        });

        return max(15, $total);
    }

    private function calculateAppointmentServicesTotalPrice(Appointment $appointment): float
    {
        $services = $this->resolveAppointmentServicesFromAppointment($appointment);
        $servicesTotal = (float) $services->sum(function (AppointmentService $service) {
            return round((float) ($service->price ?? 0), 2);
        });

        if ($servicesTotal <= 0) {
            $servicesTotal = round((float) (optional($appointment->service)->price ?? 0), 2);
        }

        $consumptionsTotal = $appointment->relationLoaded('consumptions')
            ? (float) $appointment->consumptions->sum(function (AppointmentConsumption $consumption) {
                $amount = (float) ($consumption->amount ?? 0);
                if ($amount > 0) {
                    return round($amount, 2);
                }

                $quantity = (float) ($consumption->quantity ?? 0);
                $unitCost = (float) ($consumption->unit_cost ?? 0);
                return round(max(0, $quantity) * max(0, $unitCost), 2);
            })
            : (float) AppointmentConsumption::query()
                ->where('appointment_id', (int) $appointment->id)
                ->sum('amount');

        $total = $servicesTotal + max(0, round($consumptionsTotal, 2));

        if ($total > 0) {
            return round($total, 2);
        }

        return 0.0;
    }

    private function isRescheduleDateTimeInPast(Carbon $startsAt): bool
    {
        return $startsAt->lessThan(now()->startOfMinute());
    }

    private function passesCustomerRescheduleLeadTime(Carbon $startsAt): bool
    {
        return $startsAt->greaterThanOrEqualTo(now()->addMinutes(60)->startOfMinute());
    }

    private function resolveAppointmentDisplayName(Appointment $appointment, ?Collection $services = null): string
    {
        $services = $services ?: $this->resolveAppointmentServicesFromAppointment($appointment);
        $labels = $services
            ->map(function (AppointmentService $service) {
                return trim((string) ($service->display_name ?? $service->name ?? 'Servicio'));
            })
            ->filter(fn (string $label) => $label !== '')
            ->values();

        if ($labels->isEmpty()) {
            if ($appointment->service) {
                return (string) ($appointment->service->display_name ?? $appointment->service->name ?? 'Servicio');
            }

            return 'Sin servicios';
        }

        if ($labels->count() === 1) {
            return (string) $labels->first();
        }

        return (string) $labels->join(' + ');
    }

    private function syncAppointmentServiceItems(Appointment $appointment, Collection $services, Carbon $startAt): void
    {
        $appointment->serviceItems()->delete();

        $cursor = $startAt->copy();

        foreach ($services->values() as $index => $service) {
            $durationMinutes = max(15, (int) ($service->duration_minutes ?? 60));
            $bufferMinutes = max(0, (int) ($service->buffer_minutes ?? 0));
            $segmentStartsAt = $cursor->copy();
            $segmentEndsAt = $cursor->copy()->addMinutes($durationMinutes + $bufferMinutes);

            AppointmentServiceItem::create([
                'tenant_id' => (int) $appointment->tenant_id,
                'appointment_id' => (int) $appointment->id,
                'appointment_service_id' => (int) $service->id,
                'sequence' => $index + 1,
                'duration_minutes' => $durationMinutes,
                'buffer_minutes' => $bufferMinutes,
                'price' => round((float) ($service->price ?? 0), 2),
                'starts_at' => $segmentStartsAt,
                'ends_at' => $segmentEndsAt,
            ]);

            $cursor = $segmentEndsAt;
        }
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

    private function resolveTruthyRequestFlag(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->resolveTruthyRequestFlag($item)) {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((float) $value) > 0;
        }

        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['1', 'true', 'on', 'yes', 'si', 'sí'], true);
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