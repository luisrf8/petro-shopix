@extends('layouts.app')

@section('title', 'Proyectos')

@push('styles')
<style>
  .projects-hub {
    --projects-blue: #0f5bd3;
    --projects-ink: #152944;
    --projects-muted: #6d7c93;
    --projects-surface: #f0f3f8;
    --projects-line: #d7e0ef;
    background: linear-gradient(160deg, #f3f6fb 0%, #edf2f8 100%);
    border: 1px solid #e2e9f4;
    border-radius: 1rem;
    padding: 1.25rem;
  }

  .projects-hub-top {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
  }

  .projects-hub-title {
    color: var(--projects-ink);
    font-weight: 800;
    margin-bottom: .2rem;
  }

  .projects-hub-subtitle {
    margin: 0;
    color: #4f607a;
  }

  .projects-cta-btn {
    border: none;
    border-radius: .75rem;
    background: var(--projects-blue);
    color: #fff;
    font-weight: 700;
    padding: .68rem 1rem;
    box-shadow: 0 10px 22px rgba(15, 91, 211, .24);
  }

  .projects-create-card {
    border: 1px solid var(--projects-line);
    border-radius: .75rem;
    background: #fff;
  }

  .projects-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
  }

  .project-list-card {
    border: 1px solid #ccd8ec;
    border-radius: .7rem;
    background: #fff;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100%;
  }

  .project-list-card.is-featured {
    grid-column: span 8;
  }

  .project-list-card.is-compact {
    grid-column: span 4;
  }

  .project-cover {
    height: 210px;
    background: linear-gradient(132deg, #8fb6f7 0%, #d8ebff 45%, #9ec4ff 100%);
    position: relative;
    border-bottom: 1px solid #d3def1;
    overflow: hidden;
  }

  .project-cover::before,
  .project-cover::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    opacity: .35;
  }

  .project-cover::before {
    width: 280px;
    height: 280px;
    background: #ffffff;
    right: -90px;
    top: -120px;
  }

  .project-cover::after {
    width: 220px;
    height: 220px;
    background: #2f7aec;
    left: -120px;
    bottom: -130px;
  }

  .project-phase-chip {
    position: absolute;
    right: .8rem;
    top: .8rem;
    border-radius: 999px;
    padding: .15rem .6rem;
    font-size: .65rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 700;
    background: #ffd66d;
    color: #715400;
  }

  .project-content {
    padding: .95rem;
  }

  .project-name {
    color: var(--projects-ink);
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: .5rem;
  }

  .project-name.compact {
    font-size: 1.25rem;
  }

  .project-desc {
    color: #2f4464;
    margin-bottom: .85rem;
    min-height: 44px;
  }

  .project-progress-head {
    display: flex;
    justify-content: space-between;
    gap: .6rem;
    font-size: .78rem;
    margin-bottom: .4rem;
    color: #163257;
    font-weight: 700;
  }

  .project-progress {
    height: 8px;
    border-radius: 999px;
    background: #e2e9f6;
    overflow: hidden;
    margin-bottom: .45rem;
  }

  .project-progress > span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #0c5bd8, #207ef7);
    border-radius: 999px;
  }

  .project-milestones {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .2rem;
    margin-bottom: .95rem;
  }

  .project-milestones span {
    text-align: center;
    color: #7d8da7;
    font-size: .68rem;
  }

  .project-actions {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    align-items: center;
  }

  .project-actions .btn {
    border-radius: .55rem;
  }

  .project-mini-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .65rem;
    margin-top: auto;
    padding: .75rem .95rem .9rem;
  }

  .project-mini-dots {
    display: inline-flex;
    gap: .2rem;
  }

  .project-mini-dots i {
    width: 20px;
    height: 10px;
    border-radius: 999px;
    background: #e6eaf1;
    display: inline-block;
  }

  .project-mini-dots i.active {
    background: var(--projects-blue);
  }

  .projects-grid-empty {
    grid-column: 1 / -1;
  }

  @media (max-width: 1199px) {
    .project-list-card.is-featured,
    .project-list-card.is-compact {
      grid-column: span 6;
    }
  }

  @media (max-width: 991px) {
    .projects-hub-top {
      flex-direction: column;
      align-items: stretch;
    }

    .project-list-card.is-featured,
    .project-list-card.is-compact {
      grid-column: span 12;
    }

    .project-cover {
      height: 180px;
    }
  }
</style>
@endpush

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

  @php
    $activeProjects = $projects->where('phase', '!=', 'fin')->count();
    $phaseLabels = [
      'inicio' => 'Inicio',
      'desarrollo' => 'Desarrollo',
      'fin' => 'Final',
    ];
  @endphp

  <div class="card my-4">
    <div class="card-body p-3">
      <div class="projects-hub">
        <div class="projects-hub-top">
          <div>
            <h4 class="projects-hub-title mb-1">Gestor de Proyecto y Soporte</h4>
            <p class="projects-hub-subtitle">Tienes {{ $activeProjects }} proyecto(s) activo(s) requiriendo tu atencion.</p>
          </div>
          <button class="projects-cta-btn" type="button" data-bs-toggle="collapse" data-bs-target="#createProjectPanel" aria-expanded="false" aria-controls="createProjectPanel">
            + Crear Nuevo Proyecto
          </button>
        </div>

        <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="createProjectPanel">
          <div class="projects-create-card">
            <div class="card-body">
              <form method="POST" action="{{ route('projects.module.projects.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control border border-1 p-2" required></div>
                <div class="col-md-2"><label class="form-label">Fase</label><select name="phase" class="form-control border border-1 p-2" required><option value="inicio">Inicio</option><option value="desarrollo">Desarrollo</option><option value="fin">Fin</option></select></div>
                <div class="col-md-3"><label class="form-label">Inicio</label><input type="date" name="starts_at" class="form-control border border-1 p-2"></div>
                <div class="col-md-3"><label class="form-label">Fin</label><input type="date" name="ends_at" class="form-control border border-1 p-2"></div>
                <div class="col-md-4 d-flex align-items-end">
                  <div class="form-check form-switch border rounded-3 px-3 py-2 w-100">
                    <input class="form-check-input" type="checkbox" role="switch" id="createProjectPublicLanding" name="is_public_landing" value="1" checked>
                    <label class="form-check-label fw-semibold" for="createProjectPublicLanding">Mostrar en landing publica</label>
                    <div class="small text-muted">Si esta activo, el proyecto puede verse en la vitrina publica mientras este en curso.</div>
                  </div>
                </div>
                <div class="col-md-3"><label class="form-label">Presupuesto</label><input type="number" name="budget_amount" min="0" step="0.01" class="form-control border border-1 p-2"></div>
                <div class="col-md-2"><label class="form-label">Moneda</label><select name="currency_code" class="form-control border border-1 p-2"><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
                <div class="col-md-7"><label class="form-label">Cotizacion origen (opcional)</label><select name="quotation_id" class="form-control border border-1 p-2"><option value="">Sin cotizacion asociada</option>@foreach($quotations as $quotation)<option value="{{ $quotation->id }}">#{{ $quotation->id }} - {{ $quotation->title }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Descripcion</label><textarea name="description" rows="2" class="form-control border border-1 p-2"></textarea></div>
                <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
                <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Guardar proyecto</button></div>
              </form>
            </div>
          </div>
        </div>

        <div class="projects-grid">
          @forelse($projects as $project)
            @php
              $isFeatured = $loop->first;
              $phaseKey = strtolower((string) $project->phase);
              $phaseLabel = $phaseLabels[$phaseKey] ?? strtoupper((string) $project->phase);
              $tasksTotal = (int) ($project->tasks_total_count ?? $project->tasks->count());
              $tasksDone = (int) ($project->tasks_done_count ?? $project->tasks->where('status', 'done')->count());
              $progress = $tasksTotal > 0 ? (int) round(($tasksDone / $tasksTotal) * 100) : 0;
            @endphp

            <article class="project-list-card {{ $isFeatured ? 'is-featured' : 'is-compact' }}">
              <div class="project-cover">
                <span class="project-phase-chip">{{ $phaseLabel }}</span>
                <span class="project-phase-chip" style="left: .8rem; right: auto; background: {{ $project->is_public_landing ? '#d9f99d' : '#e5e7eb' }}; color: {{ $project->is_public_landing ? '#355e00' : '#4b5563' }};">{{ $project->is_public_landing ? 'PUBLICO' : 'INTERNO' }}</span>
              </div>

              <div class="project-content">
                <h5 class="project-name {{ $isFeatured ? '' : 'compact' }}">{{ $project->name }}</h5>
                <p class="project-desc">{{ $project->description ?: 'Proyecto sin descripcion registrada.' }}</p>

                <div class="project-progress-head">
                  <span>Progreso del Proyecto</span>
                  <span>{{ $progress }}% Completado</span>
                </div>
                <div class="project-progress"><span style="width: {{ $progress }}%;"></span></div>
                <div class="project-milestones">
                  <span>Inicio</span>
                  <span>Desarrollo</span>
                  <span>Final</span>
                </div>

                <div class="project-actions">
                  <a href="{{ route('projects.module.projects.show', $project) }}" class="btn btn-outline-primary btn-sm mb-0">Ver Proyecto</a>
                  <form method="POST" action="{{ route('projects.module.projects.phase', $project) }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    <select name="phase" class="form-control border border-1 p-2 form-control-sm">
                      <option value="inicio" {{ $project->phase === 'inicio' ? 'selected' : '' }}>Inicio</option>
                      <option value="desarrollo" {{ $project->phase === 'desarrollo' ? 'selected' : '' }}>Desarrollo</option>
                      <option value="fin" {{ $project->phase === 'fin' ? 'selected' : '' }}>Fin</option>
                    </select>
                    <button class="btn btn-outline-dark btn-sm mb-0" type="submit">Actualizar</button>
                  </form>
                </div>
              </div>

              @if(!$isFeatured)
                <div class="project-mini-footer">
                  <span class="text-xs text-muted">{{ optional($project->starts_at)->format('M y') ?: 'Sin fecha' }} - {{ optional($project->ends_at)->format('M y') ?: 'Abierto' }}</span>
                  <span class="project-mini-dots"><i class="active"></i><i></i><i></i></span>
                </div>
              @endif
            </article>
          @empty
            <div class="projects-grid-empty"><div class="alert alert-info mb-0">No hay proyectos registrados.</div></div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
