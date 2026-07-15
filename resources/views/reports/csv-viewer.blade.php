@extends('layouts.app')

@section('title', 'Visor CSV')

@section('content')
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Visor CSV Online</h6>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-1">{{ $reportName ?: 'Reporte CSV' }}</h6>
                    <p class="text-sm text-muted mb-0">
                        @if(!empty($startDate) || !empty($endDate))
                            Rango: {{ $startDate ?: '-' }} a {{ $endDate ?: '-' }}
                        @else
                            Rango: sin filtro de fechas
                        @endif
                    </p>
                </div>
                <a href="{{ $csvUrl }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm mb-0">Abrir CSV crudo</a>
            </div>

            <div id="csvViewerState" class="alert alert-light border text-sm mb-3">Cargando datos del reporte...</div>

            <div class="table-responsive border rounded">
                <table class="table table-sm mb-0 align-items-center" id="csvViewerTable">
                    <thead id="csvViewerHead"></thead>
                    <tbody id="csvViewerBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csvUrl = @json($csvUrl);
    var state = document.getElementById('csvViewerState');
    var head = document.getElementById('csvViewerHead');
    var body = document.getElementById('csvViewerBody');

    if (!csvUrl || !state || !head || !body) {
        return;
    }

    var setState = function (message, typeClass) {
        state.textContent = message;
        state.className = 'alert text-sm mb-3 ' + typeClass;
    };

    var createCell = function (tag, text) {
        var element = document.createElement(tag);
        element.textContent = text == null ? '' : String(text);
        return element;
    };

    fetch(csvUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'text/csv'
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('No se pudo cargar el CSV. Codigo ' + response.status + '.');
            }
            return response.text();
        })
        .then(function (csvText) {
            var parsed = Papa.parse(csvText, {
                skipEmptyLines: true
            });

            if (!parsed || !Array.isArray(parsed.data) || parsed.data.length === 0) {
                setState('El CSV no contiene filas para mostrar.', 'alert-warning border');
                return;
            }

            var rows = parsed.data;
            var headers = rows.shift() || [];

            head.innerHTML = '';
            body.innerHTML = '';

            var headRow = document.createElement('tr');
            headers.forEach(function (value) {
                headRow.appendChild(createCell('th', value));
            });
            head.appendChild(headRow);

            rows.forEach(function (row) {
                var tr = document.createElement('tr');
                headers.forEach(function (_, index) {
                    tr.appendChild(createCell('td', row[index] || ''));
                });
                body.appendChild(tr);
            });

            setState('CSV cargado correctamente. Filas: ' + rows.length + '.', 'alert-success border');
        })
        .catch(function (error) {
            setState(error.message || 'Error al abrir el CSV.', 'alert-danger border');
        });
});
</script>
@endsection
