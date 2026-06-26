@extends('layouts.app')

@section('title', 'Nómina y Equipo de Trabajo')

@section('content')
<div class="container-fluid py-2">
  @if(session('success'))
    <div class="alert alert-success text-white bg-gradient-success" role="alert">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger text-white bg-gradient-danger" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de Nómina</h6>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-12 col-xl-6">
          <div class="card border h-100">
            <div class="card-header pb-0"><h6 class="mb-0">Equipo de trabajo</h6></div>
            <div class="card-body">
              <form method="POST" action="{{ route('projects.module.team.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-md-6">
                  <label class="form-label">Usuario existente (opcional)</label>
                  <select name="user_id" class="form-control border border-1 p-2">
                    <option value="">Sin usuario asociado</option>
                    @foreach($users as $user)
                      <option value="{{ $user->id }}">{{ $user->name }} {{ $user->email ? '(' . $user->email . ')' : '' }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nombre si es externo</label>
                  <input type="text" name="full_name" class="form-control border border-1 p-2">
                </div>
                <div class="col-md-4"><label class="form-label">Rol</label><input type="text" name="role" class="form-control border border-1 p-2"></div>
                <div class="col-md-4"><label class="form-label">Frecuencia de pago</label><select name="payment_frequency" class="form-control border border-1 p-2" required><option value="daily">Diario</option><option value="weekly">Semanal</option><option value="package">Paquete</option><option value="monthly">Mensual</option></select></div>
                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" name="email" class="form-control border border-1 p-2"></div>
                <div class="col-md-4"><label class="form-label">Teléfono (+código país)</label><input type="text" name="phone" class="form-control border border-1 p-2" placeholder="+584141234567" pattern="^\+[1-9]\d{6,14}$"></div>
                <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
                <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Guardar integrante</button></div>
              </form>

              <div class="table-responsive">
                <table class="table table-sm align-items-center mb-0">
                  <thead><tr><th>Nombre</th><th>Rol</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr></thead>
                  <tbody>
                    @forelse($teamMembers as $member)
                      <tr>
                        <td>{{ $member->full_name }}</td>
                        <td>{{ $member->role ?: '-' }}</td>
                        <td>{{ $member->email ?: '-' }} {{ $member->phone ? '| ' . $member->phone : '' }}</td>
                        <td>
                          @if($member->terminated_at)
                            <span class="badge bg-danger">Despedido</span>
                          @else
                            <span class="badge {{ $member->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member->is_active ? 'Activo' : 'Inactivo' }}</span>
                          @endif
                        </td>
                        <td class="d-flex gap-2">
                          @if($member->is_active)
                            <form method="POST" action="{{ route('projects.module.team.status', $member) }}">
                              @csrf
                              <input type="hidden" name="action" value="inactive">
                              <button class="btn btn-outline-secondary btn-sm mb-0" type="submit">Inactivar</button>
                            </form>
                            <form method="POST" action="{{ route('projects.module.team.status', $member) }}" class="d-flex gap-1">
                              @csrf
                              <input type="hidden" name="action" value="terminate">
                              <input type="text" name="termination_reason" class="form-control form-control-sm border border-1 p-2" placeholder="Motivo despido">
                              <button class="btn btn-outline-danger btn-sm mb-0" type="submit">Despedir</button>
                            </form>
                          @else
                            <form method="POST" action="{{ route('projects.module.team.status', $member) }}">
                              @csrf
                              <input type="hidden" name="action" value="reactivate">
                              <button class="btn btn-outline-success btn-sm mb-0" type="submit">Reactivar</button>
                            </form>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted">Sin integrantes.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="card border h-100">
            <div class="card-header pb-0"><h6 class="mb-0">Registro de pagos</h6></div>
            <div class="card-body">
              @if(($upcomingPayments ?? collect())->isNotEmpty())
                <div class="alert alert-warning text-dark" role="alert">
                  <strong>Pagos próximos:</strong>
                  <ul class="mb-0 mt-2">
                    @foreach($upcomingPayments as $item)
                      <li>{{ $item['member_name'] }} ({{ strtoupper($item['frequency']) }}) - próximo pago {{ $item['next_payment_at']->format('d/m/Y') }} @if($item['days_left'] < 0) (vencido) @elseif($item['days_left'] === 0) (hoy) @else ({{ $item['days_left'] }} días) @endif</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form method="GET" action="{{ route('projects.module.payroll.index') }}" class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">Filtrar período</label><select name="period" class="form-control border border-1 p-2"><option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option><option value="week" {{ ($period ?? 'all') === 'week' ? 'selected' : '' }}>Semana actual</option><option value="month" {{ ($period ?? 'all') === 'month' ? 'selected' : '' }}>Mes actual</option><option value="package" {{ ($period ?? 'all') === 'package' ? 'selected' : '' }}>Paquete</option></select></div>
                <div class="col-md-4"><label class="form-label">Tipo pago</label><select name="payment_type" class="form-control border border-1 p-2"><option value="all" {{ ($paymentTypeFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option><option value="daily" {{ ($paymentTypeFilter ?? 'all') === 'daily' ? 'selected' : '' }}>Diario</option><option value="weekly" {{ ($paymentTypeFilter ?? 'all') === 'weekly' ? 'selected' : '' }}>Semanal</option><option value="monthly" {{ ($paymentTypeFilter ?? 'all') === 'monthly' ? 'selected' : '' }}>Mensual</option><option value="package" {{ ($paymentTypeFilter ?? 'all') === 'package' ? 'selected' : '' }}>Paquete</option><option value="contract" {{ ($paymentTypeFilter ?? 'all') === 'contract' ? 'selected' : '' }}>Contrato</option></select></div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-dark w-100 mb-0" type="submit">Filtrar</button></div>
              </form>

              <form method="POST" action="{{ route('projects.module.payrolls.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo pago</label>
                  <select name="payment_type" class="form-control border border-1 p-2" required>
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                    <option value="monthly">Mensual</option>
                    <option value="package">Paquete</option>
                    <option value="contract">Contrato</option>
                  </select>
                </div>
                <div class="col-md-4"><label class="form-label">Monto</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control border border-1 p-2" required></div>
                <div class="col-md-4">
                  <label class="form-label">Moneda</label>
                  <select name="currency_code" class="form-control border border-1 p-2">
                    <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }}</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="BS">BS</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Integrante (opcional)</label>
                  <select name="team_member_id" class="form-control border border-1 p-2">
                    <option value="">Sin integrante</option>
                    @foreach($teamMembers as $member)
                      <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Proyecto (opcional)</label>
                  <select name="project_id" class="form-control border border-1 p-2">
                    <option value="">Sin proyecto</option>
                    @foreach($projects as $project)
                      <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4"><label class="form-label">Fecha pago</label><input type="date" name="paid_at" class="form-control border border-1 p-2" required></div>
                <div class="col-md-8"><label class="form-label">Notas</label><input type="text" name="notes" class="form-control border border-1 p-2"></div>
                <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Registrar pago</button></div>
              </form>

              <div class="table-responsive">
                <table class="table table-sm align-items-center mb-0">
                  <thead><tr><th>Fecha</th><th>Tipo</th><th>Integrante</th><th>Proyecto</th><th>Monto</th><th>Próx. pago aprox.</th></tr></thead>
                  <tbody>
                    @forelse($payrollEntries as $payroll)
                      <tr>
                        <td>{{ optional($payroll->paid_at)->format('d/m/Y') }}</td>
                        <td>{{ strtoupper($payroll->payment_type) }}</td>
                        <td>{{ $payroll->teamMember->full_name ?? '-' }}</td>
                        <td>{{ $payroll->project->name ?? '-' }}</td>
                        <td>{{ number_format((float) $payroll->amount, 2) }} {{ $payroll->currency_code }}</td>
                        <td>{{ $payroll->next_payment_at ? $payroll->next_payment_at->format('d/m/Y') : 'No aplica' }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="6" class="text-center text-muted">Sin pagos.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
