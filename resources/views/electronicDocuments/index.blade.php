@extends('layouts.app')

@section('title', 'Monitor de Facturación Digital')

@section('content')
@php
  $isSuperAdmin = (bool) ($isSuperAdmin ?? false);
  $canRetry = (bool) ($canRetry ?? false);
  $indexRoute = $isSuperAdmin ? 'electronic.documents.index' : 'sales.electronic.documents.tenant';
@endphp
<style>
  .electronic-documents-toolbar {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.85rem;
  }

  .electronic-documents-toolbar > div {
    grid-column: span 2;
  }

  .electronic-documents-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
  }

  .electronic-documents-table-wrap {
    overflow-x: auto;
    padding-bottom: 0.35rem;
    -webkit-overflow-scrolling: touch;
    cursor: grab;
  }

  .electronic-documents-table-wrap.is-dragging {
    cursor: grabbing;
    user-select: none;
  }

  .electronic-documents-table {
    min-width: 1760px;
  }

  .code-cell {
    max-width: 280px;
  }

  .code-preview {
    display: inline-block;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    font-family: monospace;
    font-size: 0.8rem;
  }

  @media (max-width: 991.98px) {
    .electronic-documents-toolbar > div {
      grid-column: span 6;
    }
  }

  @media (max-width: 575.98px) {
    .electronic-documents-toolbar > div {
      grid-column: span 12;
    }
  }
</style>
<div class="container-fluid py-2">
  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Monitor de documentos electrónicos</h6>
      </div>
    </div>
    <div class="card-body">
      <form method="GET" class="electronic-documents-toolbar align-items-end">
        @if($isSuperAdmin)
          <div>
            <label class="form-label">Tienda</label>
            <select name="tenant_id" class="form-control border border-1 p-2">
              <option value="0">Todas</option>
              @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ (int) $tenantId === (int) $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
        @endif
        <div>
          <label class="form-label">Estado</label>
          <select name="status" class="form-control border border-1 p-2">
            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
            <option value="issued" {{ $status === 'issued' ? 'selected' : '' }}>Emitidos</option>
            <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Fallidos</option>
            <option value="annulled" {{ $status === 'annulled' ? 'selected' : '' }}>Anulados</option>
          </select>
        </div>
        <div>
          <label class="form-label">Serie</label>
          <input type="text" name="serie" value="{{ $serie }}" class="form-control border border-1 p-2">
        </div>
        <div>
          <label class="form-label">Código</label>
          <input type="text" name="code" value="{{ $code }}" class="form-control border border-1 p-2">
        </div>
        <div>
          <label class="form-label">Desde</label>
          <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control border border-1 p-2">
        </div>
        <div>
          <label class="form-label">Hasta</label>
          <input type="date" name="to_date" value="{{ $toDate }}" class="form-control border border-1 p-2">
        </div>
        <div>
          <label class="form-label">Solo con errores</label>
          <select name="error_only" class="form-control border border-1 p-2">
            <option value="0" {{ !$errorOnly ? 'selected' : '' }}>No</option>
            <option value="1" {{ $errorOnly ? 'selected' : '' }}>Sí</option>
          </select>
        </div>
        <div class="electronic-documents-actions" style="grid-column: 1 / -1;">
          <button type="submit" class="btn btn-dark mb-0">Filtrar</button>
          <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary mb-0">Limpiar</a>
          <a href="{{ route($indexRoute, array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-success mb-0">Exportar CSV</a>
        </div>
      </form>

      <div class="electronic-documents-table-wrap mt-3">
        <table class="table align-items-center mb-0 electronic-documents-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Tienda</th>
              <th>Tipo de Doc</th>
              <th>Serie</th>
              <th>Nro. Documento</th>
              <th>Control</th>
              <th>Doc. Afectado</th>
              <th>Tasa</th>
              <th>Usuario</th>
              <th>Total</th>
              <th>Estado</th>
              <th>CUFE</th>
              <th>QR</th>
              <th>Orden</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $row)
              <tr class="{{ $row->is_annulled ? 'table-danger' : '' }}">
                <td>{{ $row->display_date }}</td>
                <td>{{ $row->display_time }}</td>
                <td>{{ $row->tenant->name ?? 'N/A' }}</td>
                <td>{{ $row->display_document_type }}</td>
                <td>{{ $row->serie ?: '-' }}</td>
                <td>{{ $row->numero_documento ?: '-' }}</td>
                <td>{{ $row->display_control_number }}</td>
                <td>{{ $row->affected_document }}</td>
                <td>{{ $row->display_tax_rate }}</td>
                <td>{{ $row->display_user }}</td>
                <td>{{ number_format((float) ($row->display_total_amount ?? 0), 2) }}</td>
                <td>
                  <span class="badge badge-sm {{ $row->is_annulled ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                    {{ $row->is_annulled ? 'Anulada' : ($row->estado_documento ?: 'Activa') }}
                  </span>
                  @if($row->mensaje)
                    <div><small class="text-muted">{{ $row->mensaje }}</small></div>
                  @endif
                </td>
                <td class="code-cell">
                  @if($row->cufe)
                    <span class="code-preview" title="{{ $row->cufe }}">{{ $row->cufe }}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0 ms-1 js-copy-value" data-copy-value="{{ $row->cufe }}">Copiar</button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="code-cell">
                  @if($row->qr_string)
                    <span class="code-preview" title="{{ $row->qr_string }}">{{ $row->qr_string }}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0 ms-1 js-copy-value" data-copy-value="{{ $row->qr_string }}">Copiar</button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>#{{ $row->sales_order_id }}</td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('sales.showByOrder', $row->sales_order_id) }}" class="btn btn-outline-dark btn-sm mb-0">Ver orden</a>
                    @if(!$row->is_annulled)
                      <a href="{{ route('sales.orders.pdfs', ['id' => $row->sales_order_id, 'type' => 'invoice']) }}" class="btn btn-outline-secondary btn-sm mb-0">PDF</a>
                      <a href="{{ route('sales.orders.pdfs', ['id' => $row->sales_order_id, 'type' => 'invoice']) }}?disposition=inline" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm mb-0">Imprimir</a>
                    @else
                      <span class="btn btn-outline-danger btn-sm mb-0 disabled" aria-disabled="true">Factura anulada</span>
                    @endif
                    @if($canRetry)
                      <form method="POST" action="{{ route('electronic.documents.retry', $row->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm mb-0">Reintentar</button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">No hay documentos electrónicos para los filtros aplicados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        <h6 class="mb-3">Notas fiscales relacionadas</h6>
        <div class="electronic-documents-table-wrap">
          <table class="table align-items-center mb-0 electronic-documents-table">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tienda</th>
                <th>Tipo de Doc</th>
                <th>Serie</th>
                <th>Nro. Documento</th>
                <th>Control</th>
                <th>Doc. Afectado</th>
                <th>Tasa</th>
                <th>Usuario</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Orden</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($adjustmentRows as $row)
                <tr>
                  <td>{{ $row->display_date }}</td>
                  <td>{{ $row->display_time }}</td>
                  <td>{{ $row->tenant->name ?? 'N/A' }}</td>
                  <td>{{ $row->display_document_type }}</td>
                  <td>{{ $row->serie ?: '-' }}</td>
                  <td>{{ $row->numero_documento ?: '-' }}</td>
                  <td>{{ $row->display_control_number }}</td>
                  <td>{{ $row->affected_document }}</td>
                  <td>{{ $row->display_tax_rate }}</td>
                  <td>{{ $row->display_user }}</td>
                  <td>{{ number_format((float) ($row->display_total_amount ?? 0), 2) }}</td>
                  <td>
                    <span class="badge badge-sm bg-gradient-info">{{ $row->estado_documento ?: 'Registrada' }}</span>
                    @if($row->mensaje)
                      <div><small class="text-muted">{{ $row->mensaje }}</small></div>
                    @endif
                  </td>
                  <td>#{{ $row->sales_order_id }}</td>
                  <td>
                    <a href="{{ $row->download_url }}" class="btn btn-outline-dark btn-sm mb-0">Descargar PDF</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="14" class="text-center text-muted py-4">No hay notas fiscales relacionadas para los filtros aplicados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $rows->links() }}
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('click', function (event) {
    var button = event.target.closest('.js-copy-value');
    if (!button) {
      return;
    }

    var value = button.getAttribute('data-copy-value') || '';
    if (value === '') {
      return;
    }

    navigator.clipboard.writeText(value).then(function () {
      var original = button.textContent;
      button.textContent = 'Copiado';
      setTimeout(function () {
        button.textContent = original;
      }, 1200);
    }).catch(function () {
      button.textContent = 'Error';
    });
  });

  (function () {
    var wrappers = document.querySelectorAll('.electronic-documents-table-wrap');

    wrappers.forEach(function (wrap) {
      if (wrap.dataset.dragScrollBound === '1') {
        return;
      }
      wrap.dataset.dragScrollBound = '1';

      var isDragging = false;
      var startX = 0;
      var startScrollLeft = 0;

      wrap.addEventListener('mousedown', function (event) {
        if (event.button !== 0) {
          return;
        }

        isDragging = true;
        startX = event.pageX;
        startScrollLeft = wrap.scrollLeft;
        wrap.classList.add('is-dragging');
      });

      wrap.addEventListener('mouseleave', function () {
        isDragging = false;
        wrap.classList.remove('is-dragging');
      });

      wrap.addEventListener('mouseup', function () {
        isDragging = false;
        wrap.classList.remove('is-dragging');
      });

      wrap.addEventListener('mousemove', function (event) {
        if (!isDragging) {
          return;
        }

        event.preventDefault();
        var delta = event.pageX - startX;
        wrap.scrollLeft = startScrollLeft - delta;
      });
    });
  })();
</script>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Tienda</th>
              <th>Tipo de Doc</th>
              <th>Serie</th>
              <th>Nro. Documento</th>
              <th>Control</th>
              <th>Doc. Afectado</th>
              <th>Tasa</th>
              <th>Usuario</th>
              <th>Total</th>
              <th>Estado</th>
              <th>CUFE</th>
              <th>QR</th>
              <th>Orden</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $row)
              <tr class="{{ $row->is_annulled ? 'table-danger' : '' }}">
                <td>{{ $row->display_date }}</td>
                <td>{{ $row->display_time }}</td>
                <td>{{ $row->tenant->name ?? 'N/A' }}</td>
                <td>{{ $row->display_document_type }}</td>
                <td>{{ $row->serie ?: '-' }}</td>
                <td>{{ $row->numero_documento ?: '-' }}</td>
                <td>{{ $row->display_control_number }}</td>
                <td>{{ $row->affected_document }}</td>
                <td>{{ $row->display_tax_rate }}</td>
                <td>{{ $row->display_user }}</td>
                <td>{{ number_format((float) ($row->display_total_amount ?? 0), 2) }}</td>
                <td>
                  <span class="badge badge-sm {{ $row->is_annulled ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                    {{ $row->is_annulled ? 'Anulada' : ($row->estado_documento ?: 'Activa') }}
                  </span>
                  @if($row->mensaje)
                    <div><small class="text-muted">{{ $row->mensaje }}</small></div>
                  @endif
                </td>
                <td class="code-cell">
                  @if($row->cufe)
                    <span class="code-preview" title="{{ $row->cufe }}">{{ $row->cufe }}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0 ms-1 js-copy-value" data-copy-value="{{ $row->cufe }}">Copiar</button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="code-cell">
                  @if($row->qr_string)
                    <span class="code-preview" title="{{ $row->qr_string }}">{{ $row->qr_string }}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0 ms-1 js-copy-value" data-copy-value="{{ $row->qr_string }}">Copiar</button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>#{{ $row->sales_order_id }}</td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('sales.showByOrder', $row->sales_order_id) }}" class="btn btn-outline-dark btn-sm mb-0">Ver orden</a>
                    @if(!$row->is_annulled)
                      <a href="{{ route('sales.orders.pdfs', ['id' => $row->sales_order_id, 'type' => 'invoice']) }}" class="btn btn-outline-secondary btn-sm mb-0">PDF</a>
                      <a href="{{ route('sales.orders.pdfs', ['id' => $row->sales_order_id, 'type' => 'invoice']) }}?disposition=inline" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm mb-0">Imprimir</a>
                    @else
                      <span class="btn btn-outline-danger btn-sm mb-0 disabled" aria-disabled="true">Factura anulada</span>
                    @endif
                    @if($canRetry)
                      <form method="POST" action="{{ route('electronic.documents.retry', $row->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm mb-0">Reintentar</button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">No hay documentos electrónicos para los filtros aplicados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $rows->links() }}
      </div>
    </div>
  </div>
</div>
<script>
  document.addEventListener('click', function (event) {
    var button = event.target.closest('.js-copy-value');
    if (!button) {
      return;
    }

    var value = button.getAttribute('data-copy-value') || '';
    if (value === '') {
      return;
    }

    navigator.clipboard.writeText(value).then(function () {
      var original = button.textContent;
      button.textContent = 'Copiado';
      setTimeout(function () {
        button.textContent = original;
      }, 1200);
    }).catch(function () {
      button.textContent = 'Error';
    });
  });
</script>
@endsection
