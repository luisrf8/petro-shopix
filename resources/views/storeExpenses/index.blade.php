@extends('layouts.app')

@section('title', 'Gastos de Tienda')

@section('content')
<div class="container-fluid py-2">
  @php
    $currencyLabels = ['USD' => '$', 'EUR' => '€', 'BS' => 'Bs'];
  @endphp
  <div class="row">
    <div class="col-md-6 col-xl-3 mb-4"><div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Total filtrado Bs</p><h4 class="mb-0">Bs {{ number_format($totalExpenses, 2) }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3 mb-4"><div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Mes actual Bs</p><h4 class="mb-0">Bs {{ number_format($monthExpenses, 2) }}</h4></div></div></div>
  </div>

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Registro de gastos de la tienda</h6>
        <button type="button" class="btn btn-sm btn-light mb-0 me-3" id="storeExpenseCreateTrigger" data-bs-toggle="modal" data-bs-target="#createExpenseModal">+ Registrar gasto</button>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="px-3 pt-3">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-4"><label class="form-label">Buscar</label><input type="text" name="search" value="{{ $search }}" class="form-control border border-1 p-2" placeholder="Concepto, proveedor o método"></div>
          <div class="col-md-2"><label class="form-label">Categoría</label><select name="category" class="form-control border border-1 p-2"><option value="">Todas</option>@foreach($categories as $categoryOption)<option value="{{ $categoryOption }}" {{ $category === $categoryOption ? 'selected' : '' }}>{{ $categoryOption }}</option>@endforeach</select></div>
          <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control border border-1 p-2"></div>
          <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control border border-1 p-2"></div>
          <div class="col-auto"><button type="submit" class="btn btn-dark mb-0">Filtrar</button></div>
        </form>
      </div>

      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0" id="storeExpensesTable">
          <thead><tr><th>Fecha</th><th>Concepto</th><th>Categoría</th><th>Proveedor</th><th>Moneda</th><th>Monto original</th><th>Tasa Bs</th><th>Monto Bs</th><th>Estado</th><th>Registrado por</th><th>Acciones</th></tr></thead>
          <tbody>
            @forelse($expenses as $expense)
              <tr>
                <td>{{ optional($expense->spent_at)->format('d/m/Y') }}</td>
                <td><div class="d-flex flex-column"><span class="font-weight-bold text-sm">{{ $expense->title }}</span><span class="text-xs text-secondary">{{ $expense->description ?: '-' }}</span></div></td>
                <td>{{ $expense->category ?: '-' }}</td>
                <td>{{ $expense->provider_name ?: '-' }}</td>
                <td>{{ $expense->currency_code ?: 'USD' }}</td>
                <td>{{ ($currencyLabels[$expense->currency_code ?? 'USD'] ?? '$') }}{{ number_format((float) ($expense->amount_original ?? $expense->amount), 2) }}</td>
                <td>{{ number_format((float) ($expense->exchange_rate_to_bs ?? 1), 4) }}</td>
                <td>Bs {{ number_format((float) ($expense->amount_bs ?? $expense->amount), 2) }}</td>
                <td><span class="badge {{ $expense->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $expense->status === 'paid' ? 'Pagado' : ucfirst($expense->status) }}</span></td>
                <td>{{ $expense->creator->name ?? '-' }}</td>
                <td><button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                  data-expense-id="{{ $expense->id }}"
                  data-expense-title="{{ $expense->title }}"
                  data-expense-category="{{ $expense->category }}"
                  data-expense-description="{{ $expense->description }}"
                  data-expense-currency-code="{{ $expense->currency_code ?? 'USD' }}"
                  data-expense-amount-original="{{ $expense->amount_original ?? $expense->amount }}"
                  data-expense-exchange-rate-to-bs="{{ $expense->exchange_rate_to_bs ?? 1 }}"
                  data-expense-amount-bs="{{ $expense->amount_bs ?? $expense->amount }}"
                  data-expense-spent-at="{{ optional($expense->spent_at)->format('Y-m-d') }}"
                  data-expense-payment-method="{{ $expense->payment_method }}"
                  data-expense-provider-name="{{ $expense->provider_name }}"
                  data-expense-status="{{ $expense->status }}">Editar</button></td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-center text-muted py-4">No hay gastos registrados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-3 pb-3 d-flex justify-content-center">{{ $expenses->links() }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="createExpenseModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Registrar gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="{{ route('store-expenses.store') }}">@csrf<div class="modal-body">
    <div class="mb-3"><label class="form-label">Concepto</label><input type="text" name="title" class="form-control border border-1 p-2" required></div>
    <div class="mb-3"><label class="form-label">Categoría</label><input type="text" name="category" class="form-control border border-1 p-2" placeholder="Servicios, renta, nómina, transporte..."></div>
    <div class="mb-3"><label class="form-label">Descripción</label><textarea name="description" class="form-control border border-1 p-2" rows="3"></textarea></div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Moneda</label>
        <select name="currency_code" id="createExpenseCurrencyCode" class="form-control border border-1 p-2" required>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="BS">BS</option>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Fecha</label>
        <input type="date" name="spent_at" value="{{ now()->toDateString() }}" class="form-control border border-1 p-2" required>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Monto original</label>
        <input type="number" step="0.0001" min="0.0001" name="amount_original" id="createExpenseAmountOriginal" class="form-control border border-1 p-2" required data-decimal-friendly="true">
      </div>
      <div class="col-md-6 mb-3" id="createExpenseRateWrap">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Tasa Bs</span>
          <button type="button" class="btn btn-link btn-sm p-0 mb-0" id="createExpenseUseInternalRate">Usar tasa interna</button>
        </label>
        <input type="number" step="0.0001" min="0.0001" name="exchange_rate_to_bs" id="createExpenseExchangeRateToBs" class="form-control border border-1 p-2" placeholder="Tasa del día">
        <small class="text-muted d-block mt-1">USD interna: {{ number_format((float) ($usdRateToBs ?? 0), 4) }} | EUR interna: {{ number_format((float) ($euroRateToBs ?? 0), 4) }}</small>
      </div>
    </div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Monto Bs calculado</label><input type="text" id="createExpenseAmountBsPreview" class="form-control border border-1 p-2" readonly value="Bs 0.00"></div><div class="col-md-6 mb-3"><label class="form-label">Método de pago</label><input type="text" name="payment_method" class="form-control border border-1 p-2"></div></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Proveedor</label><input list="expenseProviderOptions" name="provider_name" class="form-control border border-1 p-2"></div><div class="col-md-6 mb-3"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="paid">Pagado</option><option value="pending">Pendiente</option></select></div></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Guardar</button></div></form>
</div></div></div>

<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="editExpenseForm">@csrf @method('PUT')<div class="modal-body">
    <div class="mb-3"><label class="form-label">Concepto</label><input type="text" name="title" id="editExpenseTitle" class="form-control border border-1 p-2" required></div>
    <div class="mb-3"><label class="form-label">Categoría</label><input type="text" name="category" id="editExpenseCategory" class="form-control border border-1 p-2"></div>
    <div class="mb-3"><label class="form-label">Descripción</label><textarea name="description" id="editExpenseDescription" class="form-control border border-1 p-2" rows="3"></textarea></div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Moneda</label>
        <select name="currency_code" id="editExpenseCurrencyCode" class="form-control border border-1 p-2" required>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="BS">BS</option>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Fecha</label>
        <input type="date" name="spent_at" id="editExpenseSpentAt" class="form-control border border-1 p-2" required>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Monto original</label>
        <input type="number" step="0.0001" min="0.0001" name="amount_original" id="editExpenseAmountOriginal" class="form-control border border-1 p-2" required data-decimal-friendly="true">
      </div>
      <div class="col-md-6 mb-3" id="editExpenseRateWrap">
        <label class="form-label d-flex justify-content-between align-items-center">
          <span>Tasa Bs</span>
          <button type="button" class="btn btn-link btn-sm p-0 mb-0" id="editExpenseUseInternalRate">Usar tasa interna</button>
        </label>
        <input type="number" step="0.0001" min="0.0001" name="exchange_rate_to_bs" id="editExpenseExchangeRateToBs" class="form-control border border-1 p-2" placeholder="Tasa del día">
      </div>
    </div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Monto Bs calculado</label><input type="text" id="editExpenseAmountBsPreview" class="form-control border border-1 p-2" readonly value="Bs 0.00"></div><div class="col-md-6 mb-3"><label class="form-label">Método de pago</label><input type="text" name="payment_method" id="editExpensePaymentMethod" class="form-control border border-1 p-2"></div></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Proveedor</label><input list="expenseProviderOptions" name="provider_name" id="editExpenseProviderName" class="form-control border border-1 p-2"></div><div class="col-md-6 mb-3"><label class="form-label">Estado</label><select name="status" id="editExpenseStatus" class="form-control border border-1 p-2"><option value="paid">Pagado</option><option value="pending">Pendiente</option></select></div></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Guardar cambios</button></div></form>
</div></div></div>

<datalist id="expenseProviderOptions">@foreach($providers as $provider)<option value="{{ $provider->name }}"></option>@endforeach</datalist>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const editExpenseModal = document.getElementById('editExpenseModal');
  const editExpenseForm = document.getElementById('editExpenseForm');
  const createCurrencyInput = document.getElementById('createExpenseCurrencyCode');
  const createAmountInput = document.getElementById('createExpenseAmountOriginal');
  const createRateInput = document.getElementById('createExpenseExchangeRateToBs');
  const createAmountBsPreview = document.getElementById('createExpenseAmountBsPreview');
  const createUseInternalRateButton = document.getElementById('createExpenseUseInternalRate');
  const editCurrencyInput = document.getElementById('editExpenseCurrencyCode');
  const editAmountInput = document.getElementById('editExpenseAmountOriginal');
  const editRateInput = document.getElementById('editExpenseExchangeRateToBs');
  const editAmountBsPreview = document.getElementById('editExpenseAmountBsPreview');
  const editUseInternalRateButton = document.getElementById('editExpenseUseInternalRate');
  const usdRateToBs = Number(@json((float) ($usdRateToBs ?? 0)));
  const euroRateToBs = Number(@json((float) ($euroRateToBs ?? 0)));

  const resolveInternalRate = (currencyCode) => {
    const code = String(currencyCode || 'USD').toUpperCase();
    if (code === 'BS') {
      return 1;
    }
    if (code === 'EUR') {
      return euroRateToBs > 0 ? euroRateToBs : 0;
    }
    return usdRateToBs > 0 ? usdRateToBs : 0;
  };

  const updateExpensePreview = (currencyInput, amountInput, rateInput, previewInput) => {
    if (!currencyInput || !amountInput || !rateInput || !previewInput) {
      return;
    }

    const currencyCode = String(currencyInput.value || 'USD').toUpperCase();
    const amount = Number(amountInput.value || 0);
    const internalRate = resolveInternalRate(currencyCode);

    if (currencyCode === 'BS') {
      rateInput.value = '1.0000';
      rateInput.readOnly = true;
      previewInput.value = `Bs ${amount.toFixed(2)}`;
      return;
    }

    rateInput.readOnly = false;
    if (!rateInput.value && internalRate > 0) {
      rateInput.value = internalRate.toFixed(4);
    }

    const rate = Number(rateInput.value || 0);
    const amountBs = rate > 0 ? (amount * rate) : 0;
    previewInput.value = `Bs ${amountBs.toFixed(2)}`;
  };

  const applyCurrencyVisibility = (currencyInput, rateInput, amountInput, previewInput) => {
    if (!currencyInput || !rateInput || !amountInput || !previewInput) {
      return;
    }

    const currencyCode = String(currencyInput.value || 'USD').toUpperCase();
    rateInput.closest('.col-md-6')?.classList.toggle('d-none', currencyCode === 'BS');
    if (currencyCode === 'BS') {
      rateInput.value = '1.0000';
    }
    updateExpensePreview(currencyInput, amountInput, rateInput, previewInput);
  };

  createCurrencyInput?.addEventListener('change', () => applyCurrencyVisibility(createCurrencyInput, createRateInput, createAmountInput, createAmountBsPreview));
  createAmountInput?.addEventListener('input', () => updateExpensePreview(createCurrencyInput, createAmountInput, createRateInput, createAmountBsPreview));
  createRateInput?.addEventListener('input', () => updateExpensePreview(createCurrencyInput, createAmountInput, createRateInput, createAmountBsPreview));
  createUseInternalRateButton?.addEventListener('click', () => {
    const internalRate = resolveInternalRate(createCurrencyInput?.value || 'USD');
    if (internalRate > 0) {
      createRateInput.value = internalRate.toFixed(4);
      updateExpensePreview(createCurrencyInput, createAmountInput, createRateInput, createAmountBsPreview);
    }
  });

  editExpenseModal?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !editExpenseForm) {
      return;
    }

    const expenseId = button.getAttribute('data-expense-id');
    editExpenseForm.action = `/store-expenses/${expenseId}`;
    document.getElementById('editExpenseTitle').value = button.getAttribute('data-expense-title') || '';
    document.getElementById('editExpenseCategory').value = button.getAttribute('data-expense-category') || '';
    document.getElementById('editExpenseDescription').value = button.getAttribute('data-expense-description') || '';
    editCurrencyInput.value = button.getAttribute('data-expense-currency-code') || 'USD';
    editAmountInput.value = button.getAttribute('data-expense-amount-original') || '';
    editRateInput.value = button.getAttribute('data-expense-exchange-rate-to-bs') || '';
    editAmountBsPreview.value = `Bs ${Number(button.getAttribute('data-expense-amount-bs') || 0).toFixed(2)}`;
    applyCurrencyVisibility(editCurrencyInput, editRateInput, editAmountInput, editAmountBsPreview);
    document.getElementById('editExpenseSpentAt').value = button.getAttribute('data-expense-spent-at') || '';
    document.getElementById('editExpensePaymentMethod').value = button.getAttribute('data-expense-payment-method') || '';
    document.getElementById('editExpenseProviderName').value = button.getAttribute('data-expense-provider-name') || '';
    document.getElementById('editExpenseStatus').value = button.getAttribute('data-expense-status') || 'paid';
  });

  editCurrencyInput?.addEventListener('change', () => applyCurrencyVisibility(editCurrencyInput, editRateInput, editAmountInput, editAmountBsPreview));
  editAmountInput?.addEventListener('input', () => updateExpensePreview(editCurrencyInput, editAmountInput, editRateInput, editAmountBsPreview));
  editRateInput?.addEventListener('input', () => updateExpensePreview(editCurrencyInput, editAmountInput, editRateInput, editAmountBsPreview));
  editUseInternalRateButton?.addEventListener('click', () => {
    const internalRate = resolveInternalRate(editCurrencyInput?.value || 'USD');
    if (internalRate > 0) {
      editRateInput.value = internalRate.toFixed(4);
      updateExpensePreview(editCurrencyInput, editAmountInput, editRateInput, editAmountBsPreview);
    }
  });

  applyCurrencyVisibility(createCurrencyInput, createRateInput, createAmountInput, createAmountBsPreview);
});
</script>
@endpush