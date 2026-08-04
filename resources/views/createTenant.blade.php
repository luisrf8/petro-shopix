@extends('layouts.app')

@section('title', 'Crear Sede')

@section('content')
  <div class="container">
    <div class="card shadow-sm my-4">
      <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
        <div class="bg-gradient-dark text-white shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-center align-items-center">
          <h1 class="text-white">Crear Nueva Sede</h1>
        </div>
      </div>
      <div class="card-body">
        @if (session('status'))
          <div class="alert alert-success text-white" role="alert">
            {{ session('status') }}
          </div>
        @endif

        @if ($errors->any())
          <div class="alert alert-danger text-white" role="alert">
            <strong>No se pudo crear la sede.</strong>
            <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('tenants.store') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Nombre de la sede</label>
            <input
              type="text"
              name="name"
              id="name"
              class="form-control border border-radius-lg p-2"
              placeholder="Ej: Sede Caracas Centro"
              value="{{ old('name') }}"
              required
            >
          </div>

          <div class="mb-3">
            <label for="city" class="form-label">Localidad</label>
            <input
              type="text"
              name="city"
              id="city"
              class="form-control border border-radius-lg p-2"
              placeholder="Ej: Caracas, Altamira"
              value="{{ old('city') }}"
              required
            >
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Descripción</label>
            <textarea
              name="description"
              id="description"
              rows="4"
              class="form-control border border-radius-lg p-2"
              placeholder="Describe esta sede"
              required
            >{{ old('description') }}</textarea>
          </div>

          <div class="alert alert-light border mb-3">
            Esta pantalla solo crea la sede base. Luego puedes anexar usuarios y asignar roles desde Gestión de usuarios.
          </div>

          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success px-4">Crear Sede</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
