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
        <div class="col-12">
          <div class="card border h-100" id="payrollTeamCard">
            <div class="card-header pb-0 d-flex align-items-center justify-content-between">
              <h6 class="mb-0">Equipo de trabajo</h6>
              <button class="btn btn-outline-secondary btn-sm mb-0"
                      type="button"
                      data-section-toggle="payroll-team-content"
                      aria-expanded="true"
                      aria-controls="payroll-team-content">
                Ocultar
              </button>
            </div>
            <div id="payroll-team-content">
            <div class="card-body">
              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-dark mb-0" data-bs-toggle="modal" data-bs-target="#teamMemberModal">Agregar integrante</button>
              </div>

              <div class="table-responsive">
                <table class="table table-sm align-items-center mb-0" id="payrollTeamTable">
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
                        <td>
                          <div class="d-flex flex-wrap gap-2 align-items-center">
                          @if($member->is_active)
                            <button
                              type="button"
                              class="btn btn-outline-secondary btn-sm mb-0 d-inline-flex align-items-center gap-1"
                              data-bs-toggle="modal"
                              data-bs-target="#teamStatusActionModal"
                              data-action-url="{{ route('projects.module.team.status', $member) }}"
                              data-action-value="inactive"
                              data-modal-title="Inactivar integrante"
                              data-modal-message="Se inactivará este integrante y dejará de aparecer como activo en el equipo."
                              data-submit-label="Confirmar inactivación"
                              data-submit-class="btn-secondary"
                              data-reason-label="Motivo de inactivación"
                              data-reason-placeholder="Ej: licencia, pausa temporal, cambio de turno">
                              <i class="material-symbols-rounded text-sm">pause_circle</i>
                              <span>Inactivar</span>
                            </button>
                            <button
                              type="button"
                              class="btn btn-outline-danger btn-sm mb-0 d-inline-flex align-items-center gap-1"
                              data-bs-toggle="modal"
                              data-bs-target="#teamStatusActionModal"
                              data-action-url="{{ route('projects.module.team.status', $member) }}"
                              data-action-value="terminate"
                              data-modal-title="Despedir integrante"
                              data-modal-message="Este integrante quedará marcado como despedido."
                              data-submit-label="Confirmar despido"
                              data-submit-class="btn-danger"
                              data-reason-label="Motivo de despido"
                              data-reason-placeholder="Ej: fin de contrato, bajo desempeño, reestructuración">
                              <i class="material-symbols-rounded text-sm">person_off</i>
                              <span>Despedir</span>
                            </button>
                          @else
                            <form method="POST" action="{{ route('projects.module.team.status', $member) }}">
                              @csrf
                              <input type="hidden" name="action" value="reactivate">
                              <button class="btn btn-outline-success btn-sm mb-0 d-inline-flex align-items-center gap-1" type="submit">
                                <i class="material-symbols-rounded text-sm">person_check</i>
                                <span>Reactivar</span>
                              </button>
                            </form>
                          @endif
                          </div>
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
        </div>

        <div class="col-12">
          <div class="card border h-100" id="payrollPaymentsCard">
            <div class="card-header pb-0 d-flex align-items-center justify-content-between">
              <h6 class="mb-0">Registro de pagos</h6>
              <button class="btn btn-outline-secondary btn-sm mb-0"
                      type="button"
                      data-section-toggle="payroll-payments-content"
                      aria-expanded="false"
                      aria-controls="payroll-payments-content">
                Mostrar
              </button>
            </div>
            <div id="payroll-payments-content" class="d-none">
            <div class="card-body">
              @if(($upcomingPayments ?? collect())->isNotEmpty())
                <div class="alert alert-warning text-dark" role="alert">
                  <strong>Pagos próximos:</strong>
                  <ul class="mb-0 mt-2">
                    @foreach($upcomingPayments as $item)
                      <li>{{ $item['member_name'] }} ({{ ['daily' => 'Diario', 'weekly' => 'Semanal', 'fortnightly' => 'Quincenal', 'monthly' => 'Mensual', 'package' => 'Paquete', 'contract' => 'Contrato'][$item['frequency']] ?? strtoupper($item['frequency']) }}) - próximo pago {{ $item['next_payment_at']->format('d/m/Y') }} @if($item['days_left'] < 0) (vencido) @elseif($item['days_left'] === 0) (hoy) @else ({{ $item['days_left'] }} días) @endif</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form method="GET" action="{{ route('projects.module.payroll.index') }}" class="row g-2 mb-3">
                <div class="col-md-6"><label class="form-label">Filtrar período</label><select name="period" class="form-control border border-1 p-2"><option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option><option value="week" {{ ($period ?? 'all') === 'week' ? 'selected' : '' }}>Semana actual</option><option value="month" {{ ($period ?? 'all') === 'month' ? 'selected' : '' }}>Mes actual</option><option value="package" {{ ($period ?? 'all') === 'package' ? 'selected' : '' }}>Paquete</option></select></div>
                <div class="col-md-4"><label class="form-label">Tipo pago</label><select name="payment_type" class="form-control border border-1 p-2"><option value="all" {{ ($paymentTypeFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option><option value="daily" {{ ($paymentTypeFilter ?? 'all') === 'daily' ? 'selected' : '' }}>Diario</option><option value="weekly" {{ ($paymentTypeFilter ?? 'all') === 'weekly' ? 'selected' : '' }}>Semanal</option><option value="fortnightly" {{ ($paymentTypeFilter ?? 'all') === 'fortnightly' ? 'selected' : '' }}>Quincenal</option><option value="monthly" {{ ($paymentTypeFilter ?? 'all') === 'monthly' ? 'selected' : '' }}>Mensual</option><option value="package" {{ ($paymentTypeFilter ?? 'all') === 'package' ? 'selected' : '' }}>Paquete</option><option value="contract" {{ ($paymentTypeFilter ?? 'all') === 'contract' ? 'selected' : '' }}>Contrato</option></select></div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-dark w-100 mb-0" type="submit">Filtrar</button></div>
              </form>
              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-dark mb-0" id="payrollOpenPaymentModalBtn" data-bs-toggle="modal" data-bs-target="#payrollPaymentModal">Registrar nuevo pago</button>
              </div>

              <div class="table-responsive">
                <table class="table table-sm align-items-center mb-0">
                  <thead><tr><th>Fecha</th><th>Tipo</th><th>Integrante</th><th>Proyecto</th><th>Monto base</th><th>Total a pagar</th><th>Próx. pago aprox.</th><th>Comprobante</th></tr></thead>
                  <tbody>
                    @forelse($payrollEntries as $payroll)
                      <tr>
                        @php
                          $entryCurrency = strtoupper((string) ($payroll->currency_code ?? 'USD'));
                          if (in_array($entryCurrency, ['VES', 'VED', 'VEF', 'BSD'], true)) {
                            $entryCurrency = 'BS';
                          }

                          $entryRateToBs = (float) ($payroll->exchange_rate_to_bs ?? 0);
                          $amountValue = (float) ($payroll->amount ?? 0);
                          $totalValue = (float) ($payroll->total_to_pay ?? $payroll->amount ?? 0);

                          $amountBsValue = (float) ($payroll->amount_bs ?? 0);
                          if ($amountBsValue <= 0 && $entryCurrency === 'BS') {
                            $amountBsValue = $amountValue;
                          } elseif ($amountBsValue <= 0 && $entryRateToBs > 0) {
                            $amountBsValue = $amountValue * $entryRateToBs;
                          }

                          $totalBsValue = (float) ($payroll->total_to_pay_bs ?? 0);
                          if ($totalBsValue <= 0 && $entryCurrency === 'BS') {
                            $totalBsValue = $totalValue;
                          } elseif ($totalBsValue <= 0 && $entryRateToBs > 0) {
                            $totalBsValue = $totalValue * $entryRateToBs;
                          }

                          $amountDualText = number_format($amountValue, 2) . ' ' . $entryCurrency . ' / ' . ($amountBsValue > 0 ? ('Bs ' . number_format($amountBsValue, 2)) : 'Bs N/D');
                          $totalDualText = number_format($totalValue, 2) . ' ' . $entryCurrency . ' / ' . ($totalBsValue > 0 ? ('Bs ' . number_format($totalBsValue, 2)) : 'Bs N/D');
                        @endphp
                        <td>{{ optional($payroll->paid_at)->format('d/m/Y') }}</td>
                        <td>{{ ['daily' => 'Diario', 'weekly' => 'Semanal', 'fortnightly' => 'Quincenal', 'monthly' => 'Mensual', 'package' => 'Paquete', 'contract' => 'Contrato'][$payroll->payment_type] ?? strtoupper($payroll->payment_type) }}</td>
                        <td>{{ $payroll->teamMember->full_name ?? '-' }}</td>
                        <td>{{ $payroll->project->name ?? '-' }}</td>
                        <td>{{ $amountDualText }}</td>
                        <td>{{ $totalDualText }}</td>
                        <td>{{ $payroll->next_payment_at ? $payroll->next_payment_at->format('d/m/Y') : 'No aplica' }}</td>
                        <td>
                          @php
                            $payrollPdfUrl = route('projects.module.payrolls.receipt', ['payroll' => $payroll->id]);
                          @endphp
                          <div class="d-flex gap-1">
                            <a href="{{ $payrollPdfUrl }}" target="_blank" class="btn btn-outline-dark btn-sm mb-0">PDF</a>
                            <button
                              type="button"
                              class="btn btn-outline-success btn-sm mb-0 payroll-share-btn"
                              data-pdf-url="{{ $payrollPdfUrl }}"
                              data-payroll-id="{{ $payroll->id }}">
                              WS
                            </button>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="8" class="text-center text-muted">Sin pagos.</td></tr>
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
</div>

<div class="modal fade" id="teamMemberModal" tabindex="-1" aria-labelledby="teamMemberModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teamMemberModalLabel">Agregar integrante de nómina</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('projects.module.team.store') }}" class="row g-3" id="team-member-form">
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
          <div class="col-md-4"><label class="form-label">Frecuencia de pago</label><select name="payment_frequency" class="form-control border border-1 p-2" required><option value="daily">Diario</option><option value="weekly">Semanal</option><option value="fortnightly">Quincenal</option><option value="package">Paquete</option><option value="monthly">Mensual</option></select></div>
          <div class="col-md-4"><label class="form-label">Correo</label><input type="email" name="email" class="form-control border border-1 p-2"></div>
          <div class="col-md-4"><label class="form-label">Teléfono (+código país)</label><input type="text" name="phone" class="form-control border border-1 p-2" placeholder="+584141234567" pattern="^\+[1-9]\d{6,14}$"></div>
          <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
          <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Guardar integrante</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="payrollPaymentModal" tabindex="-1" aria-labelledby="payrollPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="payrollPaymentModalLabel">Registrar nuevo pago de nómina</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('projects.module.payrolls.store') }}" class="row g-3" id="payroll-flow-form">
          @csrf
          <div class="col-12">
            <label class="form-label">Selecciona integrante</label>
            <select name="team_member_id" class="form-control border border-1 p-2" required>
              <option value="">Selecciona integrante</option>
              @foreach($teamMembers as $member)
                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Proyecto (opcional)</label>
            <select name="project_id" class="form-control border border-1 p-2">
              <option value="">Sin proyecto</option>
              @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Tipo pago</label>
            <select name="payment_type" class="form-control border border-1 p-2" required>
              <option value="daily">Diario</option>
              <option value="weekly">Semanal</option>
              <option value="fortnightly">Quincenal</option>
              <option value="monthly">Mensual</option>
              <option value="package">Paquete</option>
              <option value="contract">Contrato</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Moneda</label>
            <select name="currency_code" class="form-control border border-1 p-2">
              <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }}</option>
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="BS">BS</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Fecha pago</label>
            <input type="date" name="paid_at" class="form-control border border-1 p-2" value="{{ now()->toDateString() }}" required>
          </div>

          <div class="col-12">
            <div class="border rounded p-3">
              <h6 class="mb-3">Agregar item</h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label">Tipo</label>
                  <select class="form-control border border-1 p-2" id="payroll-item-type">
                    <option value="pago">Pago</option>
                    <option value="descuento">Descuento</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Monto</label>
                  <input type="number" min="0.01" step="0.01" class="form-control border border-1 p-2" id="payroll-item-amount" placeholder="0.00">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Descripción</label>
                  <input type="text" class="form-control border border-1 p-2" id="payroll-item-description" placeholder="Detalle del pago o descuento">
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-outline-dark w-100 mb-0" id="payroll-add-item-btn">Agregar</button>
                </div>
              </div>

              <div class="table-responsive mt-3">
                <table class="table table-sm align-items-center mb-0" id="payroll-items-table">
                  <thead>
                    <tr>
                      <th>Tipo</th>
                      <th>Monto</th>
                      <th>Descripción</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr id="payroll-items-empty-row">
                      <td colspan="4" class="text-center text-muted">Sin items agregados.</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="row g-2 mt-2">
                <div class="col-12"><strong>Total pagos:</strong> <span id="payroll-total-payments">0.00</span></div>
                <div class="col-12"><strong>Total descuentos:</strong> <span id="payroll-total-deductions">0.00</span></div>
                <div class="col-12"><strong>Total a pagar:</strong> <span id="payroll-total-net">0.00</span></div>
              </div>
            </div>
          </div>

          <input type="hidden" name="amount" id="payroll-amount-hidden" value="0">
          <input type="hidden" name="total_to_pay" id="payroll-total-hidden" value="0">
          <input type="hidden" name="payment_reason" id="payroll-payment-reason-hidden" value="">
          <input type="hidden" name="deduction_reason" id="payroll-deduction-reason-hidden" value="">
          <input type="hidden" name="payroll_items_json" id="payroll-items-json-hidden" value="">

          <div class="col-12"><label class="form-label">Notas</label><input type="text" name="notes" class="form-control border border-1 p-2"></div>
          <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Registrar pago</button></div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="teamStatusActionModal" tabindex="-1" aria-labelledby="teamStatusActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="teamStatusActionModalLabel">Actualizar estado de integrante</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="POST" id="teamStatusActionForm">
        @csrf
        <input type="hidden" name="action" id="teamStatusActionValue" value="">
        <div class="modal-body">
          <p class="text-sm mb-3" id="teamStatusActionMessage">Confirma esta acción sobre el integrante.</p>
          <div class="mb-0">
            <label for="teamStatusActionReason" class="form-label" id="teamStatusActionReasonLabel">Motivo</label>
            <input
              type="text"
              name="termination_reason"
              id="teamStatusActionReason"
              class="form-control border border-1 p-2"
              placeholder="Indica el motivo"
              maxlength="255"
              required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn mb-0" id="teamStatusActionSubmitBtn">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    document.querySelectorAll('button[data-section-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-section-toggle');
        const target = targetId ? document.getElementById(targetId) : null;
        if (!target) {
          return;
        }

        const isHidden = target.classList.contains('d-none');
        if (isHidden) {
          target.classList.remove('d-none');
          button.textContent = 'Ocultar';
          button.setAttribute('aria-expanded', 'true');
          return;
        }

        target.classList.add('d-none');
        button.textContent = 'Mostrar';
        button.setAttribute('aria-expanded', 'false');
      });
    });

    const form = document.getElementById('payroll-flow-form');
    const typeInput = document.getElementById('payroll-item-type');
    const amountInput = document.getElementById('payroll-item-amount');
    const descriptionInput = document.getElementById('payroll-item-description');
    const addButton = document.getElementById('payroll-add-item-btn');
    const itemsTableBody = document.querySelector('#payroll-items-table tbody');
    const emptyRow = document.getElementById('payroll-items-empty-row');

    const totalPaymentsEl = document.getElementById('payroll-total-payments');
    const totalDeductionsEl = document.getElementById('payroll-total-deductions');
    const totalNetEl = document.getElementById('payroll-total-net');

    const amountHidden = document.getElementById('payroll-amount-hidden');
    const totalHidden = document.getElementById('payroll-total-hidden');
    const paymentReasonHidden = document.getElementById('payroll-payment-reason-hidden');
    const deductionReasonHidden = document.getElementById('payroll-deduction-reason-hidden');
    const itemsJsonHidden = document.getElementById('payroll-items-json-hidden');
    const payrollPaymentModal = document.getElementById('payrollPaymentModal');
    const teamMemberForm = document.getElementById('team-member-form');
    const teamMemberModal = document.getElementById('teamMemberModal');
    const teamStatusActionModal = document.getElementById('teamStatusActionModal');
    const teamStatusActionForm = document.getElementById('teamStatusActionForm');
    const teamStatusActionValue = document.getElementById('teamStatusActionValue');
    const teamStatusActionMessage = document.getElementById('teamStatusActionMessage');
    const teamStatusActionSubmitBtn = document.getElementById('teamStatusActionSubmitBtn');
    const teamStatusActionReason = document.getElementById('teamStatusActionReason');
    const teamStatusActionReasonLabel = document.getElementById('teamStatusActionReasonLabel');
    const teamStatusActionModalLabel = document.getElementById('teamStatusActionModalLabel');
    const payrollShareButtons = document.querySelectorAll('.payroll-share-btn');

    if (!form || !typeInput || !amountInput || !descriptionInput || !addButton || !itemsTableBody) {
      return;
    }

    payrollShareButtons.forEach((button) => {
      button.addEventListener('click', async () => {
        const pdfUrl = button.getAttribute('data-pdf-url') || '';
        const payrollId = button.getAttribute('data-payroll-id') || '';

        if (!pdfUrl) {
          return;
        }

        try {
          const response = await fetch(pdfUrl, {
            credentials: 'same-origin',
          });

          if (!response.ok) {
            throw new Error('No se pudo obtener el comprobante.');
          }

          const pdfBlob = await response.blob();
          const pdfFile = new File([pdfBlob], `comprobante-nomina-${payrollId}.pdf`, { type: 'application/pdf' });

          if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
            await navigator.share({
              title: `Comprobante de nomina #${payrollId}`,
              text: `Comprobante de nomina #${payrollId}`,
              files: [pdfFile],
            });
            return;
          }

          const objectUrl = URL.createObjectURL(pdfBlob);
          const fallbackLink = document.createElement('a');
          fallbackLink.href = objectUrl;
          fallbackLink.download = `comprobante-nomina-${payrollId}.pdf`;
          fallbackLink.click();
          setTimeout(() => URL.revokeObjectURL(objectUrl), 1500);
        } catch (error) {
          console.error(error);
          window.open(pdfUrl, '_blank', 'noopener');
        }
      });
    });

    if (teamStatusActionModal && teamStatusActionForm) {
      teamStatusActionModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
          return;
        }

        const actionUrl = trigger.getAttribute('data-action-url') || '';
        const actionValue = trigger.getAttribute('data-action-value') || '';
        const modalTitle = trigger.getAttribute('data-modal-title') || 'Actualizar estado de integrante';
        const modalMessage = trigger.getAttribute('data-modal-message') || 'Confirma esta acción sobre el integrante.';
        const submitLabel = trigger.getAttribute('data-submit-label') || 'Confirmar';
        const submitClass = trigger.getAttribute('data-submit-class') || 'btn-dark';
        const reasonLabel = trigger.getAttribute('data-reason-label') || 'Motivo';
        const reasonPlaceholder = trigger.getAttribute('data-reason-placeholder') || 'Indica el motivo';

        teamStatusActionForm.setAttribute('action', actionUrl);
        teamStatusActionValue.value = actionValue;
        teamStatusActionModalLabel.textContent = modalTitle;
        teamStatusActionMessage.textContent = modalMessage;
        teamStatusActionReasonLabel.textContent = reasonLabel;
        teamStatusActionReason.placeholder = reasonPlaceholder;
        teamStatusActionReason.value = '';
        teamStatusActionSubmitBtn.textContent = submitLabel;
        teamStatusActionSubmitBtn.className = `btn mb-0 ${submitClass}`;
      });
    }

    const items = [];

    const formatMoney = (value) => Number(value || 0).toFixed(2);
    const escapeHtml = (value) => String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const render = () => {
      itemsTableBody.querySelectorAll('tr[data-item-row="1"]').forEach((row) => row.remove());

      if (items.length === 0 && emptyRow) {
        emptyRow.classList.remove('d-none');
      }

      items.forEach((item, index) => {
        if (emptyRow) {
          emptyRow.classList.add('d-none');
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-item-row', '1');
        tr.innerHTML = `
          <td>${item.type === 'pago' ? 'Pago' : 'Descuento'}</td>
          <td>${formatMoney(item.amount)}</td>
          <td>${escapeHtml(item.description)}</td>
          <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm mb-0" data-remove-index="${index}">Quitar</button></td>
        `;
        itemsTableBody.appendChild(tr);
      });

      const totalPayments = items
        .filter((item) => item.type === 'pago')
        .reduce((sum, item) => sum + Number(item.amount || 0), 0);
      const totalDeductions = items
        .filter((item) => item.type === 'descuento')
        .reduce((sum, item) => sum + Number(item.amount || 0), 0);
      const totalNet = totalPayments - totalDeductions;

      totalPaymentsEl.textContent = formatMoney(totalPayments);
      totalDeductionsEl.textContent = formatMoney(totalDeductions);
      totalNetEl.textContent = formatMoney(totalNet);

      amountHidden.value = String(totalPayments);
      totalHidden.value = String(totalNet);
      paymentReasonHidden.value = items.filter((item) => item.type === 'pago').map((item) => item.description).join(' | ');
      deductionReasonHidden.value = items.filter((item) => item.type === 'descuento').map((item) => item.description).join(' | ');
      itemsJsonHidden.value = JSON.stringify(items);
    };

    const resetPayrollFlow = () => {
      items.splice(0, items.length);
      form.reset();
      const paidAtField = form.querySelector('input[name="paid_at"]');
      if (paidAtField) {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        paidAtField.value = `${yyyy}-${mm}-${dd}`;
      }
      render();
    };

    addButton.addEventListener('click', () => {
      const type = String(typeInput.value || '').trim().toLowerCase();
      const amount = Number.parseFloat(amountInput.value || '0');
      const description = String(descriptionInput.value || '').trim();

      if (!['pago', 'descuento'].includes(type)) {
        alert('Selecciona un tipo de item válido.');
        return;
      }

      if (!Number.isFinite(amount) || amount <= 0) {
        alert('El monto del item debe ser mayor que 0.');
        return;
      }

      if (description === '') {
        alert('Debes indicar la descripción del item.');
        return;
      }

      items.push({
        type,
        amount,
        description,
      });

      amountInput.value = '';
      descriptionInput.value = '';
      render();
    });

    itemsTableBody.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-remove-index]');
      if (!button) {
        return;
      }

      const index = Number(button.getAttribute('data-remove-index'));
      if (!Number.isInteger(index) || index < 0 || index >= items.length) {
        return;
      }

      items.splice(index, 1);
      render();
    });

    form.addEventListener('submit', (event) => {
      if (items.length === 0) {
        event.preventDefault();
        alert('Debes agregar al menos un item de pago o descuento.');
        return;
      }

      const totalNet = Number(totalHidden.value || '0');
      if (!Number.isFinite(totalNet) || totalNet <= 0) {
        event.preventDefault();
        alert('El total a pagar debe ser mayor que 0. Revisa la sumatoria de items.');
      }
    });

    payrollPaymentModal?.addEventListener('hidden.bs.modal', () => {
      resetPayrollFlow();
    });

    teamMemberModal?.addEventListener('hidden.bs.modal', () => {
      teamMemberForm?.reset();
    });

    render();
  })();
</script>
@endpush
