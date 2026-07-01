@extends('layouts.app')

@section('title', 'Categorías')

@push('styles')
<style>
  .pm-header {
    margin-bottom: 0.4rem;
  }

  .pm-rate-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
  }

  .pm-rate-item {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.25;
  }

  .pm-action-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    width: 100%;
    justify-content: flex-start;
  }

  .pm-action-group .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    white-space: nowrap;
    min-height: 42px;
  }

  .pm-currency-chip {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.8rem;
    padding: 0.72rem 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    width: 100%;
    background: var(--bs-body-bg);
    font-weight: 600;
    min-height: 52px;
  }

  .pm-currency-row {
    display: flex;
    gap: 0.55rem;
    flex-wrap: nowrap;
  }

  .pm-currency-col {
    flex: 1 1 0;
    min-width: 0;
  }

  .pm-method-chip {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.85rem;
    padding: 0.68rem 0.78rem;
    background: var(--bs-body-bg);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    min-height: 58px;
  }

  .pm-method-main {
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
  }

  .pm-method-name {
    font-weight: 600;
    margin: 0;
    line-height: 1;
  }

  .pm-method-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin-left: auto;
  }

  .pm-method-edit {
    color: var(--bs-dark);
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
  }

  .pm-toggle-link {
    border: 0;
    background: transparent;
    padding: 0;
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1;
  }

  .pm-toggle-link.text-success,
  .pm-toggle-link.text-danger {
    opacity: 0.95;
  }

  @media (max-width: 768px) {
    .pm-header {
      margin-top: 0.3rem;
      margin-bottom: 0.75rem;
    }

    .pm-rate-list {
      width: 100%;
      gap: 0.35rem;
    }

    .pm-rate-item {
      font-size: 1.45rem;
      width: 100%;
    }

    .pm-action-group {
      display: grid;
      grid-template-columns: 1fr;
      width: 100%;
      gap: 0.45rem;
    }

    .pm-action-group .btn {
      width: 100%;
    }

    .pm-currency-row {
      flex-wrap: wrap;
    }

    .pm-currency-col {
      flex: 1 1 100%;
    }

    .pm-method-chip {
      min-height: 56px;
      padding: 0.64rem 0.7rem;
    }

    .pm-method-name {
      font-size: 0.9rem;
    }
  }
</style>
@endpush

@section('content')
    <div class="container-fluid py-2">
      <div class="row align-items-center g-2 pm-header">
        <div class="col-12">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div class="pm-rate-list">
              <h6 class="pm-rate-item">Tasa USD: <span id="currentDollarRate">{{$dollarRate ? number_format($dollarRate->rate, 2) : 'N/A'}}</span> VES / USD</h6>
              <h6 class="pm-rate-item">Tasa EUR: <span id="currentEuroRate">{{$euroRate ? number_format($euroRate->rate, 2) : 'N/A'}}</span> VES / EUR</h6>
            </div>
            <div class="pm-action-group">
              <button class="btn btn-outline-dark mb-0" data-bs-toggle="modal" data-bs-target="#rateHistoryModal">
                <i class="material-symbols-rounded text-sm">history</i>&nbsp;&nbsp;Historial y exportaciones
              </button>
              <button class="btn bg-gradient-success mb-0" data-bs-toggle="modal" data-bs-target="#updateDollarRateModal">
                <i class="material-symbols-rounded text-sm">currency_exchange</i>&nbsp;&nbsp;Actualizar Tasa del Dólar
              </button>
              <button class="btn bg-gradient-info mb-0" data-bs-toggle="modal" data-bs-target="#updateEuroRateModal">
                <i class="material-symbols-rounded text-sm">currency_exchange</i>&nbsp;&nbsp;Actualizar Tasa del Euro
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="rateHistoryModal" tabindex="-1" aria-labelledby="rateHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <div>
                <h5 class="modal-title" id="rateHistoryModalLabel">Historial de tasas</h5>
                <small class="text-muted">Consulta el histórico BCV y exporta en Excel, CSV o PDF.</small>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="row g-4 mb-3">
                <div class="col-12 col-xl-6">
                  <div class="card h-100">
                    <div class="card-header pb-0 p-3 d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-0">Histórico BCV USD</h6>
                        <p class="text-sm text-muted mb-0">Registro de solo lectura. No se puede editar ni eliminar.</p>
                      </div>
                      <span class="badge bg-gradient-dark">Inmutable</span>
                    </div>
                    <div class="card-body px-0 pt-3 pb-0">
                      <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                          <thead>
                            <tr>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha BCV</th>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tasa</th>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Registrada</th>
                            </tr>
                          </thead>
                          <tbody>
                            @forelse($dollarRateHistory as $rateItem)
                              <tr>
                                <td><p class="text-sm mb-0 px-3">{{ optional($rateItem->date)->format('d/m/Y') ?? 'Sin fecha' }}</p></td>
                                <td><p class="text-sm mb-0">{{ number_format((float) $rateItem->rate, 4) }} Bs</p></td>
                                <td><p class="text-sm text-secondary mb-0">{{ optional($rateItem->created_at)->format('d/m/Y H:i') ?? 'Sin registro' }}</p></td>
                              </tr>
                            @empty
                              <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin histórico registrado.</td>
                              </tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-xl-6">
                  <div class="card h-100">
                    <div class="card-header pb-0 p-3 d-flex justify-content-between align-items-center">
                      <div>
                        <h6 class="mb-0">Histórico BCV EUR</h6>
                        <p class="text-sm text-muted mb-0">Registro de solo lectura. No se puede editar ni eliminar.</p>
                      </div>
                      <span class="badge bg-gradient-dark">Inmutable</span>
                    </div>
                    <div class="card-body px-0 pt-3 pb-0">
                      <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                          <thead>
                            <tr>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha BCV</th>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tasa</th>
                              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Registrada</th>
                            </tr>
                          </thead>
                          <tbody>
                            @forelse($euroRateHistory as $rateItem)
                              <tr>
                                <td><p class="text-sm mb-0 px-3">{{ optional($rateItem->date)->format('d/m/Y') ?? 'Sin fecha' }}</p></td>
                                <td><p class="text-sm mb-0">{{ number_format((float) $rateItem->rate, 4) }} Bs</p></td>
                                <td><p class="text-sm text-secondary mb-0">{{ optional($rateItem->created_at)->format('d/m/Y H:i') ?? 'Sin registro' }}</p></td>
                              </tr>
                            @empty
                              <tr>
                                <td colspan="3" class="text-center text-muted py-4">Sin histórico registrado.</td>
                              </tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
                <a href="{{ route('paymentMethods.rates.export', 'excel') }}" class="btn btn-outline-success btn-sm">Exportar Excel</a>
                <a href="{{ route('paymentMethods.rates.export', 'csv') }}" class="btn btn-outline-primary btn-sm">Exportar CSV</a>
                <a href="{{ route('paymentMethods.rates.export', 'pdf') }}" class="btn btn-outline-dark btn-sm">Exportar PDF</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Monedas Section -->
      <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4 mb-4">
          <div class="card-header pb-0 p-3">
            <div class="row">
              <div class="col-6 d-flex align-items-center">
                <h6 class="mb-0">Monedas</h6>
              </div>
              <div class="col-6 text-end">
                <!-- <button class="btn bg-gradient-black mb-0" data-bs-toggle="modal" data-bs-target="#createCurrencyModal">
                  <i class="material-symbols-rounded text-sm">add</i>&nbsp;&nbsp;Nueva Moneda
                </button> -->
              </div>
            </div>
          </div>
          <div class="card-body p-3">
            <!-- Lista de Monedas -->
            <div class="pm-currency-row">
              @foreach($currencies as $currency)
                <div class="pm-currency-col">
                  <div class="pm-currency-chip">
                    <i class="material-symbols-rounded text-sm">payments</i>
                    <span>{{ $currency->name }} / {{$currency->code}}</span>
                    <!-- <button class="btn btn-sm toggle-status-currency-btn pt-4 {{ $currency->status ? 'text-success' : 'text-danger'}}" 
                        data-id="{{ $currency->id }}" 
                        data-status="{{ $currency->status ? 'active' : 'inactive' }}">
                          {{ $currency->status ? 'Inactivar' : 'Activar' }}
                    </button>
                    <i class="material-symbols-rounded ms-auto text-dark cursor-pointer btn-edit-currency" 
                    data-bs-toggle="modal" 
                    data-bs-target="#editCurrency" 
                    data-method-id="{{ $currency->id }}"
                    data-name="{{ $currency->name }}"
                    data-code="{{ $currency->code }}"
                    title="Editar Moneda">edit</i> -->
                  </div>
                </div>
              @endforeach
            </div>
                      <form id="updateBaseCurrencyForm" class="d-flex align-items-center gap-2 justify-content-md-end">
            @csrf
            <label for="baseCurrency" class="mb-0 fw-semibold">Moneda madre:</label>
            <select class="form-select border border-1 p-2" id="baseCurrency" name="base_currency" style="max-width: 180px;">
              <option value="USD" {{ ($baseCurrencyCode ?? 'USD') === 'USD' ? 'selected' : '' }}>Dólar (USD)</option>
              <option value="EUR" {{ ($baseCurrencyCode ?? 'USD') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
            </select>
            <button type="submit" class="btn btn-outline-dark mb-0">Guardar</button>
          </form>
          </div>

        </div>
      </div>

      <!-- Modal: Actualizar Tasa del Precio del Euro -->
      <div class="modal fade" id="updateEuroRateModal" tabindex="-1" aria-labelledby="updateEuroRateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="updateEuroRateModalLabel">Actualizar Tasa del Euro</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="updateEuroRateForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="euroRate" class="form-label">Tasa de Cambio</label>
                  <input type="number" step="0.01" min="0.01" class="form-control border border-1 p-2" id="euroRate" name="rate" required data-decimal-friendly="true">
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Actualizar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Métodos de Pago Section -->
      <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
          <div class="card-header pb-0 p-3">
            <div class="row">
              <div class="col-6 d-flex align-items-center">
                <h6 class="mb-0">Métodos de Pago</h6>
              </div>
              <div class="col-6 text-end">
                <button class="btn bg-gradient-black mb-0" data-bs-toggle="modal" data-bs-target="#createPaymentMethodModal">
                  <i class="material-symbols-rounded text-sm">add</i>&nbsp;&nbsp;Nuevo Método de Pago
                </button>
              </div>
            </div>
          </div>
          <div class="card-body p-3">
            @foreach($groupedPaymentMethods as $currencyName => $methods)
              <h6 class="mb-2">{{ $currencyName }}</h6>
              <div class="row g-2">
              @foreach($methods as $method)
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                  <div class="pm-method-chip">
                    @php
                        $qrImages = isset($method->qr_image) && is_string($method->qr_image) ? json_decode($method->qr_image, true) : [];
                    @endphp
                    <img src="{{ count($qrImages) > 0 ? (\App\Support\ImageStorage::url($qrImages[0]) ?? '') : '' }}" 
                        alt="Imagen del producto" 
                        class="d-none" 
                        style="width: 20%; height: 20%; object-fit: cover; border-radius: inherit;">
                    <div class="pm-method-main">
                      <p class="pm-method-name">{{ $method->name }}</p>
                      <span class="badge bg-gradient-{{ $method->usesReference() ? 'info' : 'secondary' }}">{{ $method->usesReference() ? 'Posee referencia' : 'Sin referencia' }}</span>
                      <span class="badge bg-gradient-{{ $method->requiresProofImage() ? 'danger' : 'light text-dark' }}">{{ $method->requiresProofImage() ? 'Comprobante requerido' : 'Comprobante opcional' }}</span>
                    </div>

                    <div class="pm-method-actions">
                      <i class="material-symbols-rounded pm-method-edit btn-edit-method" 
                        title="Editar Método"
                        data-bs-toggle="modal" 
                        data-bs-target="#editPaymentMethod" 
                        data-method-id="{{ $method->id }}"
                        data-qr="{{ count($qrImages) > 0 ? (\App\Support\ImageStorage::url($qrImages[0]) ?? '') : '' }}"
                        data-name="{{ $method->name }}"
                        data-admin_name="{{ $method->admin_name }}"
                        data-currency="{{ $method->currency_id }}"
                        data-bank="{{ $method->bank }}"
                        data-dni="{{ $method->dni }}"
                        data-description="{{ $method->description }}"
                        data-has-reference="{{ $method->usesReference() ? '1' : '0' }}"
                        data-requires-proof-image="{{ $method->requiresProofImage() ? '1' : '0' }}">edit</i>
                      <button class="pm-toggle-link toggle-status-btn {{ $method->status ? 'text-success' : 'text-danger'}}" 
                          data-id="{{ $method->id }}" 
                          data-status="{{ $method->status ? 'active' : 'inactive' }}">
                            {{ $method->status ? 'Inactivar' : 'Activar' }}
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
              </div>
            @endforeach
          </div>
        </div>
      </div>


      <!-- Modal: Crear Moneda -->
      <div class="modal fade" id="createCurrencyModal" tabindex="-1" aria-labelledby="createCurrencyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createCurrencyModalLabel">Crear Moneda</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="createCurrencyForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="currencyName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="currencyName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="currencyCode" class="form-label">Código</label>
                  <input type="text" class="form-control border border-1 p-2" id="currencyCode" name="code" required>
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Actualizar Tasa del Precio del Dólar -->
      <div class="modal fade" id="updateDollarRateModal" tabindex="-1" aria-labelledby="updateDollarRateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="updateDollarRateModalLabel">Actualizar Tasa del Dólar</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="updateDollarRateForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="dollarRate" class="form-label">Tasa de Cambio</label>
                  <input type="number" step="0.01" min="0.01" class="form-control border border-1 p-2" id="dollarRate" name="rate" required data-decimal-friendly="true">
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Actualizar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Crear Método de Pago -->
      <div class="modal fade" id="createPaymentMethodModal" tabindex="-1" aria-labelledby="createPaymentMethodModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createPaymentMethodModalLabel">Crear Método de Pago</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="createPaymentMethodForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="paymentMethodName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="paymentMethodName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="paymentMethodCurrency" class="form-label">Moneda</label>
                  <select class="form-select border border-1 p-2" id="paymentMethodCurrency" name="currency" required>
                    <option value="" disabled selected>Selecciona una moneda</option>
                    @foreach($currencies as $currency)
                      <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label for="paymentMethodBenefit" class="form-label">Beneficiario</label>
                  <input type="text" class="form-control border border-1 p-2" id="paymentMethodBenefit" name="admin_name">
                </div>
                <div class="mb-3">
                  <label for="paymentMethodDni" class="form-label">DNI</label>
                  <input type="text" class="form-control border border-1 p-2" id="paymentMethodDni" name="dni">
                </div>
                <div class="mb-3">
                  <label for="paymentMethodBank" class="form-label">Banco</label>
                  <input type="text" class="form-control border border-1 p-2" id="paymentMethodBank" name="bank">
                </div>
                <div class="mb-3">
                  <label for="paymentMethodDescription" class="form-label">Descripción</label>
                  <input type="text" class="form-control border border-1 p-2" id="paymentMethodDescription" name="description">
                </div>
                <div class="mb-3">
                  <input type="hidden" name="has_reference" value="0">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="paymentMethodHasReference" name="has_reference" value="1" checked>
                    <label class="form-check-label" for="paymentMethodHasReference">Posee referencia</label>
                  </div>
                  <small class="text-muted">Activa esta opción para métodos como transferencia. Desactívala para efectivo o punto de venta.</small>
                </div>
                <div class="mb-3">
                  <input type="hidden" name="requires_proof_image" value="0">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="paymentMethodRequiresProofImage" name="requires_proof_image" value="1" checked>
                    <label class="form-check-label" for="paymentMethodRequiresProofImage">Comprobante de imagen requerido</label>
                  </div>
                  <small class="text-muted">Define si este método obliga subir imagen del comprobante en la venta.</small>
                </div>
                <div class="mb-3 d-flex flex-column">
                  <label for="paymentMethodQr" class="form-label">QR</label>
                    <input type="file" class="form-control border border-1 p-2 " id="image" name="image" accept="image/*">
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal para editar Método de Pago -->
      <div class="modal fade" id="editPaymentMethod" tabindex="-1" aria-labelledby="editPaymentMethod" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editPaymentMethodModalLabel">Editar Método de Pago</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="editPaymentMethodForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="editMethodId" name="id">
                <div class="mb-3">
                  <label for="editPaymentMethodName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="editPaymentMethodName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="editPaymentMethodCurrency" class="form-label">Moneda</label>
                  <select class="form-select border border-1 p-2" id="editPaymentMethodCurrency" name="currency" required>
                    <option value="" disabled selected>Selecciona una moneda</option>
                    @foreach($currencies as $currency)
                      <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label for="editPaymentMethodBenefit" class="form-label">Beneficiario</label>
                  <input type="text" class="form-control border border-1 p-2" id="editPaymentMethodBenefit" name="admin_name">
                </div>
                <div class="mb-3">
                  <label for="editPaymentMethodDni" class="form-label">DNI</label>
                  <input type="text" class="form-control border border-1 p-2" id="editPaymentMethodDni" name="dni">
                </div>
                <div class="mb-3">
                  <label for="editPaymentMethodBank" class="form-label">Banco</label>
                  <input type="text" class="form-control border border-1 p-2" id="editPaymentMethodBank" name="bank">
                </div>
                <div class="mb-3">
                  <label for="editPaymentMethodDescription" class="form-label">Descripción</label>
                  <input type="text" class="form-control border border-1 p-2" id="editPaymentMethodDescription" name="description">
                </div>
                <div class="mb-3">
                  <input type="hidden" name="has_reference" value="0">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="editPaymentMethodHasReference" name="has_reference" value="1" checked>
                    <label class="form-check-label" for="editPaymentMethodHasReference">Posee referencia</label>
                  </div>
                  <small class="text-muted">Si está apagado, el flujo de venta no exigirá referencia para este método.</small>
                </div>
                <div class="mb-3">
                  <input type="hidden" name="requires_proof_image" value="0">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="editPaymentMethodRequiresProofImage" name="requires_proof_image" value="1" checked>
                    <label class="form-check-label" for="editPaymentMethodRequiresProofImage">Comprobante de imagen requerido</label>
                  </div>
                  <small class="text-muted">Si está apagado, el comprobante será opcional en la venta para este método.</small>
                </div>
                <div class="mb-3 d-flex flex-column">
                  <label for="editPaymentMethodQr" class="form-label">QR</label>
                  <img id="editPaymentMethodQrImage" src="" alt="Imagen del producto" class="d-none d-flex justify-content-center" style="width: 20%; height: 20%; object-fit: cover; border-radius: inherit;">
                  <span id="editPaymentMethodQrIcon" class="text-muted">Sin QR cargado</span>
                  <button type="button" class="btn btn-outline-danger btn-sm mt-2 d-none" id="btnRemoveQrImage">Eliminar QR</button>
                  <label for="img" class="form-label">Cambiar QR</label>
                    <input type="file" class="form-control border border-1 p-2 " id="image" name="image" accept="image/*">
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal para editar Moneda -->
      <div class="modal fade" id="editCurrency" tabindex="-1" aria-labelledby="editCurrency" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editCurrencyModalLabel">Editar Moneda</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="editCurrencyForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="editCurrencyId" name="id">
                <div class="mb-3">
                  <label for="editCurrencyName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="editCurrencyName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="editCurrencyCode" class="form-label">Código</label>
                  <input type="text" class="form-control border border-1 p-2" id="editCurrencyCode" name="code" required>
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

  <script>
    const authUser = @json($authUser);
    const tenantId = Number(authUser.tenant_id);
    const baseCurrencyLabel = document.getElementById('baseCurrency');
    document.getElementById('createCurrencyForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario
      let formData = new FormData(this); // Crear un FormData con los datos del formulario
      console.log("formData", formData)
      formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id
      fetch('api/currencies/create', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
        console.log("response", response)
        if (response.status === 201) { // Valida el código de estado HTTP
          // alert('Moneda creada correctamente');
          window.location.reload();
        } else {
          throw new Error('Error al crear la Moneda');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al crear la Moneda');
      });
    });


    document.getElementById('createPaymentMethodForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario
      let formData = new FormData(this); // Crear un FormData con los datos del formulario
      formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id
      console.log("formData", formData)
      fetch('api/payment-methods/create', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
        if (response.status === 201) { // Valida el código de estado HTTP
          alert('Método de pago creado correctamente');
          window.location.reload();
        } else {
          throw new Error('Error al crear Método de pago');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al Método de pago');
      });
    });
    // Actualizar la tasa del dólar
    document.getElementById('updateDollarRateForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita que el formulario se envíe de manera convencional
      let formData = new FormData(this); // Crear un FormData con los datos del formulario
      formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id
      console.log("formData", formData)
      fetch('api/dollar-rate/update', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
        .then(response => {
          console.log("response", response)
          if (response.status === 201) {
            alert('Tasa del dólar actualizada exitosamente');
            // $('#updateDollarRateModal').modal('hide'); // Cierra el modal
            location.reload(); // Recarga la página para mostrar los cambios
          } else {
            alert('Hubo un error al actualizar la tasa del dólar');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Hubo un error inesperado. Intente nuevamente');
        })
      });

    document.getElementById('updateEuroRateForm').addEventListener('submit', function(event) {
      event.preventDefault();
      let formData = new FormData(this);
      formData.append('tenant_id', tenantId);

      fetch('api/euro-rate/update', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
        .then(response => {
          if (response.status === 201) {
            alert('Tasa del euro actualizada exitosamente');
            location.reload();
          } else {
            alert('Hubo un error al actualizar la tasa del euro');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Hubo un error inesperado. Intente nuevamente');
        });
    });

    document.getElementById('updateBaseCurrencyForm').addEventListener('submit', function(event) {
      event.preventDefault();

      let formData = new FormData(this);
      formData.append('tenant_id', tenantId);

      fetch('api/tenant-base-currency/update', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
        if (response.status === 200) {
          const selectedCurrency = baseCurrencyLabel ? baseCurrencyLabel.value : 'USD';
          alert(`Moneda madre actualizada a ${selectedCurrency}`);
          location.reload();
        } else {
          alert('Hubo un error al actualizar la moneda madre');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Hubo un error inesperado. Intente nuevamente');
      });
    });
      // Evento para llenar el modal con los datos de la categoría seleccionada
      document.querySelectorAll('.btn-edit-method').forEach(button => {
        button.addEventListener('click', function () {
          const methodId = this.getAttribute('data-method-id');
          const methodName = this.getAttribute('data-name') || ''; // Valor por defecto si es null
          const methodAdmin = this.getAttribute('data-admin_name') || '';
          const methodCurrency = this.getAttribute('data-currency') || '';
          const methodBank = this.getAttribute('data-bank') || '';
          const methodDni = this.getAttribute('data-dni') || '';
          const methodDescription = this.getAttribute('data-description') || '';
          const methodHasReference = this.getAttribute('data-has-reference') === '1';
          const methodRequiresProofImage = this.getAttribute('data-requires-proof-image') === '1';
          const methodQr = this.getAttribute('data-qr') || null;

          // Asigna valores al formulario del modal
          document.getElementById('editMethodId').value = methodId;
          document.getElementById('editPaymentMethodName').value = methodName;
          document.getElementById('editPaymentMethodDni').value = methodDni;
          document.getElementById('editPaymentMethodBenefit').value = methodAdmin;
          document.getElementById('editPaymentMethodBank').value = methodBank;
          document.getElementById('editPaymentMethodDescription').value = methodDescription;
          document.getElementById('editPaymentMethodHasReference').checked = methodHasReference;
          document.getElementById('editPaymentMethodRequiresProofImage').checked = methodRequiresProofImage;

          // Selecciona la moneda si está disponible
          const currencySelect = document.getElementById('editPaymentMethodCurrency');
          if (methodCurrency) {
            currencySelect.value = methodCurrency;
          } else {
            currencySelect.selectedIndex = 0; // Selecciona el placeholder por defecto
          }

          // Muestra la imagen del QR si existe o el ícono de foto si no
          const qrImage = document.getElementById('editPaymentMethodQrImage');
          const qrIcon = document.getElementById('editPaymentMethodQrIcon');
          const qrDelete = document.getElementById('btnRemoveQrImage');

          if (methodQr) {
            qrImage.src = methodQr; // Actualiza la URL de la imagen
            qrImage.classList.remove('d-none');
            qrDelete?.classList.remove('d-none');
            qrIcon?.classList.add('d-none');
          } else {
            qrDelete?.classList.add('d-none');
            qrImage.classList.add('d-none'); // Esconde la imagen
            qrIcon?.classList.remove('d-none');
          }
        });
      });
      // Evento para llenar el modal con los datos de la categoría seleccionada
      document.querySelectorAll('.btn-edit-currency').forEach(button => {
        button.addEventListener('click', function () {
          const methodId = this.getAttribute('data-method-id');
          const methodName = this.getAttribute('data-name') || '';
          const methodBank = this.getAttribute('data-code') || '';
          document.getElementById('editCurrencyId').value = methodId;
          document.getElementById('editCurrencyName').value = methodName;
          document.getElementById('editCurrencyCode').value = methodBank;
        });
      });
      document.getElementById('editCurrencyForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('editCurrencyId').value;
        let formData = new FormData(this);
        console.log("formData",formData)
        fetch(`/api/currencies/${id}/update`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
          },
          body: formData,
        })
          .then(data => {
            if (data.status === 200) {
              alert('moneda actualizada');
              window.location.reload();
            } else {
              alert('Hubo un problema al actualizar.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      });
      document.querySelectorAll('.toggle-status-currency-btn').forEach(button => {
      button.addEventListener('click', function () {
        console.log("hola")
        const categoryId = this.getAttribute('data-id');
        const currentStatus = this.getAttribute('data-status');
        // Alternar el estado
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const reason = newStatus === 'inactive' ? window.shopixRequestActionReason('Indica el motivo para inactivar la moneda.') : '';
        if (newStatus === 'inactive' && !reason) {
          return;
        }

        // Hacer la petición AJAX para cambiar el estado
        fetch(`api/currencies/${categoryId}/currencyToggleStatus`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ is_active: newStatus === 'active' ? 1 : 0, action_reason: reason })
        })
        .then(response => {
          if (response.status === 200) { // Valida el código de estado HTTP
            alert('Categoría actualizada correctamente');
            window.location.reload();
          } else {
            throw new Error('Error al actualizar la categoría');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Ocurrió un error al actualizar la Categoría');
        });
        })
      });
      document.querySelectorAll('.toggle-status-btn').forEach(button => {
      button.addEventListener('click', function () {
        console.log("hola")
        const categoryId = this.getAttribute('data-id');
        const currentStatus = this.getAttribute('data-status');
        // Alternar el estado
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const reason = newStatus === 'inactive' ? window.shopixRequestActionReason('Indica el motivo para inactivar el método de pago.') : '';
        if (newStatus === 'inactive' && !reason) {
          return;
        }

        // Hacer la petición AJAX para cambiar el estado
        fetch(`api/payment-methods/${categoryId}/toggleStatus`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ is_active: newStatus === 'active' ? 1 : 0, action_reason: reason })
        })
        .then(response => {
          if (response.status === 200) { // Valida el código de estado HTTP
            alert('Categoría actualizada correctamente');
            window.location.reload();
          } else {
            throw new Error('Error al actualizar la categoría');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Ocurrió un error al actualizar la Categoría');
        });
        })
      });
      document.getElementById('btnRemoveQrImage')?.addEventListener('click', function () {
        const methodId = document.getElementById('editMethodId').value;

        if (confirm('¿Estás seguro de que deseas eliminar este QR?')) {
          fetch(`/api/payment-methods/remove-qr/${methodId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert(data.message);
              // Actualizar la interfaz
              document.getElementById('editPaymentMethodQrImage').classList.add('d-none');
              document.getElementById('editPaymentMethodQrImage').src = '';
              document.getElementById('editPaymentMethodQrIcon')?.classList.remove('d-none');
              document.getElementById('btnRemoveQrImage')?.classList.add('d-none');
            } else {
              alert('Hubo un problema al eliminar el QR.');
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
        }
      });
      document.getElementById('editPaymentMethodForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('editMethodId').value;
        let formData = new FormData(this);
        console.log("formData",formData)
        fetch(`/api/payment-methods/${id}/edit`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json'
          },
          body: formData,
        })
          .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
              throw new Error(data.message || 'Hubo un problema al actualizar el método de pago.');
            }

            return data;
          })
          .then(data => {
            alert(data.message);
            window.location.reload();
          })
          .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Hubo un problema al actualizar el método de pago.');
          });
      });


  </script>
@endpush
