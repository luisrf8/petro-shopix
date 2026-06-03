@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
  <div class="card my-4">
    <div class="card-header bg-gradient-dark text-white p-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 text-white">Documentacion de Prueba</h6>
      <span class="badge bg-light text-dark">Super Administrador</span>
    </div>
    <div class="card-body p-3">
      <p class="text-sm text-secondary mb-0">
        Esta seccion centraliza los documentos de prueba ya generados para descarga directa.
      </p>
    </div>
  </div>

  <div class="card my-4">
    <div class="card-body px-0 pb-2">
      <div class="table-responsive">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Documento</th>
              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Archivo</th>
              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Tamano</th>
              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Actualizado</th>
              <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Accion</th>
            </tr>
          </thead>
          <tbody>
            @forelse($documents as $document)
              <tr>
                <td class="ps-3">
                  <h6 class="mb-0 text-sm">{{ $document['title'] }}</h6>
                  <p class="text-xs text-secondary mb-0">{{ $document['description'] }}</p>
                </td>
                <td>
                  <p class="text-xs font-weight-bold mb-0">{{ $document['filename'] }}</p>
                </td>
                <td class="align-middle text-center text-sm">
                  @if($document['exists'] && !is_null($document['size']))
                    <span class="text-secondary text-xs font-weight-bold">{{ number_format($document['size'] / 1024, 2) }} KB</span>
                  @else
                    <span class="badge bg-gradient-secondary">No disponible</span>
                  @endif
                </td>
                <td class="align-middle text-center text-sm">
                  @if($document['updated_at'])
                    <span class="text-secondary text-xs font-weight-bold">{{ $document['updated_at'] }}</span>
                  @else
                    <span class="text-secondary text-xs font-weight-bold">-</span>
                  @endif
                </td>
                <td class="align-middle text-center">
                  @if($document['exists'])
                    <a href="{{ route('documentation.download', ['document' => $document['key']]) }}" class="btn btn-sm btn-outline-dark mb-0">
                      Descargar
                    </a>
                  @else
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-0" disabled>
                      Sin archivo
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-secondary text-sm">No hay documentos configurados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
