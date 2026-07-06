@extends('layouts.app')

@section('title', 'Ficha de Proyecto')

@push('styles')
<style>
  .project-show-shell {
    --project-blue: #0f5bd3;
    --project-blue-soft: #eaf2ff;
    --project-ink: #10213b;
    --project-muted: #6b7a90;
    --project-line: #d7dfec;
    --project-warning: #e89b0a;
    --project-ok: #12a46f;
    --project-bg: linear-gradient(160deg, #f2f6fc 0%, #eef2f7 100%);
    background: var(--project-bg);
    border-radius: 1rem;
    padding: 1.4rem;
    border: 1px solid #e0e7f3;
  }

  .project-show-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    border-bottom: 1px solid var(--project-line);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
  }

  .project-badge-status {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-size: .68rem;
    font-weight: 700;
    color: #836500;
    background: #ffe8ad;
    border-radius: 999px;
    padding: .2rem .65rem;
  }

  .project-hero-title {
    color: var(--project-ink);
    font-weight: 800;
    font-size: clamp(1.3rem, 1.12rem + 1vw, 2rem);
    line-height: 1.1;
    margin: .55rem 0;
  }

  .project-hero-subtitle {
    color: #42536d;
    max-width: 64ch;
    margin-bottom: 0;
  }

  .project-hero-actions {
    display: flex;
    align-items: start;
    gap: .6rem;
    flex-wrap: wrap;
  }

  .project-pill-btn {
    border-radius: .7rem;
    font-weight: 600;
    padding: .58rem .95rem;
    border: 1px solid #c5d4f1;
    background: #fff;
    color: var(--project-ink);
  }

  .project-pill-btn--primary {
    border-color: var(--project-blue);
    background: var(--project-blue);
    color: #fff;
    box-shadow: 0 8px 18px rgba(15, 91, 211, 0.25);
  }

  .project-roadmap {
    border: 1px solid var(--project-line);
    border-radius: .75rem;
    background: rgba(255, 255, 255, 0.7);
    padding: .9rem;
    margin-bottom: 1rem;
  }

  .project-roadmap h6 {
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #4e5f7a;
    font-size: .67rem;
    margin-bottom: .85rem;
  }

  .project-roadmap-track {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .5rem;
    position: relative;
  }

  .project-roadmap-track::before {
    content: '';
    position: absolute;
    left: 8%;
    right: 8%;
    top: 21px;
    height: 2px;
    background: #cad6ec;
  }

  .project-step {
    position: relative;
    text-align: center;
    z-index: 1;
  }

  .project-step-dot {
    width: 42px;
    height: 42px;
    border-radius: .8rem;
    margin: 0 auto .45rem;
    display: grid;
    place-items: center;
    font-weight: 800;
    border: 1px solid #bfcee8;
    color: #6e7f99;
    background: #f4f8ff;
  }

  .project-step.done .project-step-dot {
    background: var(--project-blue);
    border-color: var(--project-blue);
    color: #fff;
  }

  .project-step.current .project-step-dot {
    background: #f7fbff;
    border-color: var(--project-blue);
    color: var(--project-blue);
    box-shadow: 0 0 0 4px rgba(15, 91, 211, .14);
  }

  .project-step strong {
    display: block;
    color: #22314a;
    font-size: .83rem;
    line-height: 1.1;
  }

  .project-step small {
    color: #61728e;
    font-size: .7rem;
  }

  .project-step.current small {
    color: var(--project-warning);
    font-weight: 700;
  }

  .project-step.done small {
    color: var(--project-ok);
    font-weight: 700;
  }

  .project-info-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 1rem;
  }

  .project-panel {
    border: 1px solid var(--project-line);
    border-radius: .75rem;
    background: #fff;
  }

  .project-panel-head {
    padding: .9rem 1rem;
    border-bottom: 1px solid #e7edf7;
    display: flex;
    justify-content: space-between;
    gap: .8rem;
    align-items: center;
  }

  .project-panel-head h6 {
    margin: 0;
    color: var(--project-ink);
    font-weight: 700;
  }

  .project-panel-body {
    padding: 1rem;
  }

  .project-video-thumb {
    width: 100%;
    height: clamp(180px, 32vw, 320px);
    object-fit: cover;
    border-radius: .65rem;
    display: block;
    border: 1px solid #dce6f7;
  }

  .project-video-placeholder {
    width: 100%;
    height: clamp(180px, 32vw, 320px);
    border-radius: .65rem;
    border: 1px dashed #b8c9e6;
    background: linear-gradient(135deg, #eef5ff, #f8fbff);
    display: grid;
    place-items: center;
    color: #43608b;
    text-align: center;
    padding: 1rem;
    font-weight: 600;
  }

  .project-video-caption {
    margin-top: .6rem;
    color: #20324d;
    font-size: .88rem;
    font-weight: 600;
  }

  .project-team-row,
  .project-doc-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .8rem;
    padding: .45rem 0;
    border-bottom: 1px solid #eef2f8;
  }

  .project-team-row:last-child,
  .project-doc-row:last-child {
    border-bottom: none;
  }

  .project-team-avatar {
    width: 32px;
    height: 32px;
    border-radius: .58rem;
    display: grid;
    place-items: center;
    font-size: .72rem;
    font-weight: 800;
    background: #e9f0ff;
    color: var(--project-blue);
  }

  .project-meta-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    margin-bottom: 1rem;
  }

  .project-meta-pill {
    border: 1px solid #d8e2f2;
    border-radius: .6rem;
    padding: .45rem .7rem;
    background: #fff;
  }

  .project-phase-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
    justify-content: space-between;
    padding: .95rem 1rem;
    border: 1px solid var(--project-line);
    border-radius: .75rem;
    background: rgba(255, 255, 255, 0.82);
    margin-bottom: 1rem;
  }

  .project-phase-actions-copy {
    color: #435675;
    font-size: .9rem;
    margin: 0;
  }

  .project-phase-actions-controls {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
  }

  .project-gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
  }

  .project-phase-gallery {
    border: 1px solid var(--project-line);
    border-radius: .85rem;
    background: #fff;
    overflow: hidden;
  }

  .project-phase-gallery-head {
    padding: .9rem 1rem;
    border-bottom: 1px solid #e8eef8;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
  }

  .project-phase-gallery-head h6 {
    margin: 0;
    color: var(--project-ink);
  }

  .project-phase-gallery-body {
    padding: 1rem;
  }

  .project-gallery-empty {
    border: 1px dashed #c9d7ec;
    border-radius: .7rem;
    min-height: 180px;
    display: grid;
    place-items: center;
    text-align: center;
    color: #61728f;
    background: #f8fbff;
    padding: 1rem;
  }

  .project-media-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
  }

  .project-media-card {
    border: 1px solid #dbe4f2;
    border-radius: .75rem;
    overflow: hidden;
    background: #fff;
  }

  .project-media-preview {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
    background: #eef4ff;
  }

  .project-media-card video.project-media-preview {
    object-fit: cover;
  }

  .project-media-meta {
    padding: .7rem .75rem;
  }

  .project-media-title {
    color: #20324d;
    font-weight: 700;
    margin-bottom: .2rem;
  }

  .project-doc-list {
    margin-top: .85rem;
    display: grid;
    gap: .5rem;
  }

  .project-doc-chip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    border: 1px solid #e2e9f5;
    border-radius: .65rem;
    padding: .55rem .7rem;
    background: #fbfdff;
  }

  @media (max-width: 991px) {
    .project-show-shell {
      padding: 1rem;
    }

    .project-show-hero {
      grid-template-columns: 1fr;
    }

    .project-info-grid {
      grid-template-columns: 1fr;
    }

    .project-gallery-grid {
      grid-template-columns: 1fr;
    }

    .project-media-grid {
      grid-template-columns: 1fr;
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
    $phaseLabels = [
      'inicio' => 'Inicio',
      'desarrollo' => 'Desarrollo',
      'fin' => 'Finalizacion',
    ];

    $phaseKey = strtolower((string) $project->phase);
    $phaseLabel = $phaseLabels[$phaseKey] ?? strtoupper((string) $project->phase);

    $phaseOrder = ['inicio', 'desarrollo', 'fin'];
    $phaseIndex = array_search($phaseKey, $phaseOrder, true);
    $phaseIndex = $phaseIndex === false ? 0 : $phaseIndex;
    $nextPhase = $phaseOrder[$phaseIndex + 1] ?? null;

    $processAssets = $assetsByType->get('process_image', collect());
    $finalAssets = $assetsByType->get('final_image', collect());
    $referenceAssets = $assetsByType->get('reference_image', collect());
    $documentationAssets = $assetsByType->get('documentation', collect())->whereNotNull('file_path')->values();
    $currentPhaseGallery = $phaseGallery->get($phaseKey, ['media' => collect(), 'documents' => collect(), 'entries' => collect(), 'label' => $phaseLabel]);
    $videoAsset = $currentPhaseGallery['media']->first() ?: $processAssets->first() ?: $finalAssets->first() ?: $referenceAssets->first();
    $assignedTeam = $project->assignments->filter(fn ($assignment) => $assignment->teamMember)->take(5)->values();
  @endphp

  <div class="card my-4">
    <div class="card-body p-3">
      <div class="project-show-shell">
        <div class="project-show-hero">
          <div>
            <span class="project-badge-status">{{ $project->phase === 'fin' ? 'Finalizado' : $phaseLabel }}</span>
            <h1 class="project-hero-title">{{ $project->name }}</h1>
            <p class="project-hero-subtitle">
              {{ $project->description ?: 'Optimizacion de la experiencia de usuario y modernizacion de la infraestructura tecnica para la plataforma global de ventas.' }}
            </p>
          </div>
          <div class="project-hero-actions">
            <a href="{{ route('projects.module.projects.index') }}" class="project-pill-btn text-decoration-none">Volver a proyectos</a>
            @if($project->phase !== 'fin')
              <form method="POST" action="{{ route('projects.module.projects.complete', $project) }}">
                @csrf
                <button type="submit" class="project-pill-btn project-pill-btn--primary">Finalizar Proyecto</button>
              </form>
            @endif
          </div>
        </div>

        <div class="project-meta-pills">
          <div class="project-meta-pill"><strong>Fase:</strong> {{ $phaseLabel }}</div>
          <div class="project-meta-pill"><strong>Landing:</strong> {{ $project->is_public_landing ? 'Visible al publico' : 'Solo uso interno' }}</div>
          <div class="project-meta-pill"><strong>Inicio:</strong> {{ optional($project->starts_at)->format('d/m/Y') ?: '-' }}</div>
          <div class="project-meta-pill"><strong>Fin:</strong> {{ optional($project->ends_at)->format('d/m/Y') ?: '-' }}</div>
          <div class="project-meta-pill"><strong>Presupuesto:</strong> {{ number_format((float) $project->budget_amount, 2) }} {{ $project->currency_code }}</div>
        </div>

        <section class="project-roadmap">
          <h6>Cronograma del proyecto</h6>
          <div class="project-roadmap-track">
            @foreach($phaseOrder as $index => $step)
              @php
                $stateClass = $index < $phaseIndex ? 'done' : ($index === $phaseIndex ? 'current' : '');
                $stepStatus = $index < $phaseIndex ? 'Finalizado' : ($index === $phaseIndex ? 'En Progreso' : 'Pendiente');
              @endphp
              <div class="project-step {{ $stateClass }}">
                <div class="project-step-dot">{{ $index + 1 }}</div>
                <strong>{{ $phaseLabels[$step] }}</strong>
                <small>{{ $stepStatus }}</small>
              </div>
            @endforeach
          </div>
        </section>

        <section class="project-phase-actions">
          <div>
            <h6 class="mb-1">Control de fase</h6>
            <p class="project-phase-actions-copy">Puedes adelantar la fase actual, cerrar el proyecto y revisar la evidencia capturada por cada etapa.</p>
          </div>
          <div class="project-phase-actions-controls">
            <form method="POST" action="{{ route('projects.module.projects.visibility', $project) }}" class="d-flex gap-2 align-items-center">
              @csrf
              <div class="form-check form-switch border rounded-3 px-3 py-2 bg-white">
                <input class="form-check-input" type="checkbox" role="switch" id="projectPublicLandingSwitch" name="is_public_landing" value="1" {{ $project->is_public_landing ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="form-check-label fw-semibold" for="projectPublicLandingSwitch">Visible en landing</label>
              </div>
            </form>

            @if($nextPhase)
              <form method="POST" action="{{ route('projects.module.projects.phase', $project) }}">
                @csrf
                <input type="hidden" name="phase" value="{{ $nextPhase }}">
                <button type="submit" class="project-pill-btn project-pill-btn--primary">Adelantar a {{ $phaseLabels[$nextPhase] }}</button>
              </form>
            @endif

            <form method="POST" action="{{ route('projects.module.projects.phase', $project) }}" class="d-flex gap-2 align-items-center">
              @csrf
              <select name="phase" class="form-control border border-1 p-2">
                @foreach($phaseOrder as $phaseOption)
                  <option value="{{ $phaseOption }}" {{ $phaseOption === $phaseKey ? 'selected' : '' }}>{{ $phaseLabels[$phaseOption] }}</option>
                @endforeach
              </select>
              <button type="submit" class="project-pill-btn">Cambiar fase</button>
            </form>

            @if($project->phase !== 'fin')
              <form method="POST" action="{{ route('projects.module.projects.complete', $project) }}">
                @csrf
                <button type="submit" class="project-pill-btn">Marcar finalizado</button>
              </form>
            @endif
          </div>
        </section>

        <section class="project-info-grid mb-4">
          <article class="project-panel">
            <header class="project-panel-head">
              <h6>Bitacora de la fase {{ $currentPhaseGallery['label'] }}</h6>
              <small class="text-muted">Ultima actualizacion: {{ optional($videoAsset?->created_at)->format('d/m/Y H:i') ?: 'Sin registros' }}</small>
            </header>
            <div class="project-panel-body">
              @if($videoAsset && $videoAsset->file_path)
                @php $heroExt = strtolower(pathinfo((string) $videoAsset->file_path, PATHINFO_EXTENSION)); @endphp
                @if(in_array($heroExt, ['mp4', 'webm', 'mov'], true))
                  <video class="project-video-thumb" controls preload="metadata">
                    <source src="{{ route('projects.module.projects.assets.file', $videoAsset) }}">
                  </video>
                @else
                  <a href="{{ route('projects.module.projects.assets.file', $videoAsset) }}" target="_blank" class="text-decoration-none">
                    <img src="{{ route('projects.module.projects.assets.file', $videoAsset) }}" alt="Bitacora de proyecto" class="project-video-thumb">
                  </a>
                @endif
                <div class="project-video-caption">{{ $videoAsset->title ?: 'Actualizacion de fase del proyecto' }}</div>
              @else
                <div class="project-video-placeholder">No hay evidencia visual cargada.<br>Sube imagenes o videos en la seccion de informacion adicional.</div>
              @endif
            </div>
          </article>

          <aside class="d-grid gap-3">
            <article class="project-panel">
              <header class="project-panel-head">
                <h6>Equipo asignado</h6>
              </header>
              <div class="project-panel-body">
                @forelse($assignedTeam as $assignment)
                  @php
                    $name = (string) ($assignment->teamMember->full_name ?? 'N/A');
                    $initials = collect(explode(' ', $name))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                  @endphp
                  <div class="project-team-row">
                    <div class="d-flex align-items-center gap-2">
                      <div class="project-team-avatar">{{ $initials !== '' ? $initials : 'TM' }}</div>
                      <div>
                        <div class="fw-semibold text-sm">{{ $name }}</div>
                        <small class="text-muted">{{ $assignment->teamMember->role ?: 'Integrante de proyecto' }}</small>
                      </div>
                    </div>
                    <span class="badge bg-light text-dark border">{{ strtoupper((string) ($assignment->member_status ?: 'active')) }}</span>
                  </div>
                @empty
                  <p class="text-muted mb-0">Sin integrantes asignados aun.</p>
                @endforelse
              </div>
            </article>

            <article class="project-panel">
              <header class="project-panel-head">
                <h6>Documentos</h6>
                <small>{{ $documentationAssets->count() }} archivo(s)</small>
              </header>
              <div class="project-panel-body">
                @forelse($documentationAssets->take(5) as $document)
                  <div class="project-doc-row">
                    <div>
                      <div class="fw-semibold text-sm">{{ $document->title ?: 'Documento del proyecto' }}</div>
                      <small class="text-muted">{{ optional($document->created_at)->format('d/m/Y H:i') ?: '-' }}</small>
                    </div>
                    <a href="{{ route('projects.module.projects.assets.file', $document) }}" target="_blank" class="btn btn-outline-primary btn-sm mb-0">Abrir</a>
                  </div>
                @empty
                  <p class="text-muted mb-0">No hay documentos cargados.</p>
                @endforelse
              </div>
            </article>
          </aside>
        </section>

        <section class="mb-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">Galeria por fases</h5>
              <p class="text-sm text-muted mb-0">Aqui puedes ver lo que se hizo en cada fase con imagenes, videos y documentos.</p>
            </div>
          </div>

          <div class="project-gallery-grid">
            @foreach($phaseOrder as $phaseSection)
              @php
                $gallery = $phaseGallery->get($phaseSection);
                $mediaItems = $gallery['media'];
                $documentItems = $gallery['documents'];
                $sectionIndex = array_search($phaseSection, $phaseOrder, true);
                $phaseStatusLabel = $phaseSection === $phaseKey ? 'Actual' : ($sectionIndex < $phaseIndex ? 'Completada' : 'Pendiente');
              @endphp
              <article class="project-phase-gallery">
                <header class="project-phase-gallery-head">
                  <div>
                    <h6>{{ $gallery['label'] }}</h6>
                    <small class="text-muted">{{ $mediaItems->count() }} media | {{ $documentItems->count() }} documento(s)</small>
                  </div>
                  <span class="badge bg-light text-dark border">{{ strtoupper($phaseStatusLabel) }}</span>
                </header>
                <div class="project-phase-gallery-body">
                  @if($mediaItems->isEmpty() && $documentItems->isEmpty())
                    <div class="project-gallery-empty">Sin evidencia registrada en esta fase.</div>
                  @else
                    @if($mediaItems->isNotEmpty())
                      <div class="project-media-grid">
                        @foreach($mediaItems as $media)
                          @php $mediaExt = strtolower(pathinfo((string) $media->file_path, PATHINFO_EXTENSION)); @endphp
                          <div class="project-media-card">
                            @if(in_array($mediaExt, ['mp4', 'webm', 'mov'], true))
                              <video class="project-media-preview" controls preload="metadata">
                                <source src="{{ route('projects.module.projects.assets.file', $media) }}">
                              </video>
                            @else
                              <a href="{{ route('projects.module.projects.assets.file', $media) }}" target="_blank">
                                <img src="{{ route('projects.module.projects.assets.file', $media) }}" alt="{{ $media->title ?: 'Media del proyecto' }}" class="project-media-preview">
                              </a>
                            @endif
                            <div class="project-media-meta">
                              <div class="project-media-title">{{ $media->title ?: 'Registro visual' }}</div>
                              <small class="text-muted d-block">{{ optional($media->happened_at)->format('d/m/Y') ?: optional($media->created_at)->format('d/m/Y') ?: '-' }}</small>
                              @if($media->notes)
                                <small class="text-muted d-block mt-1">{{ $media->notes }}</small>
                              @endif
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @endif

                    @if($documentItems->isNotEmpty())
                      <div class="project-doc-list">
                        @foreach($documentItems as $document)
                          <div class="project-doc-chip">
                            <div>
                              <div class="fw-semibold text-sm">{{ $document->title ?: 'Documento de fase' }}</div>
                              <small class="text-muted">{{ optional($document->created_at)->format('d/m/Y H:i') ?: '-' }}</small>
                            </div>
                            <a href="{{ route('projects.module.projects.assets.file', $document) }}" target="_blank" class="btn btn-outline-primary btn-sm mb-0">Abrir</a>
                          </div>
                        @endforeach
                      </div>
                    @endif
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        </section>

        <div class="row g-3 mb-4">
          <div class="col-12 col-md-4">
            <div class="card border h-100">
              <div class="card-body py-3">
                <p class="text-sm text-muted mb-1">Total Pagado (Ingresos)</p>
                <h5 class="mb-0 text-success">{{ number_format((float) $totalPaid, 2) }} {{ $project->currency_code }}</h5>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card border h-100">
              <div class="card-body py-3">
                <p class="text-sm text-muted mb-1">Total Gastado (Gastos + Nomina)</p>
                <h5 class="mb-0 text-danger">{{ number_format((float) $totalSpent, 2) }} {{ $project->currency_code }}</h5>
                <p class="text-xs text-muted mb-0 mt-1">Gastos: {{ number_format((float) $totalExpenses, 2) }} | Nomina: {{ number_format((float) $totalPayrollPaid, 2) }}</p>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="card border h-100">
              <div class="card-body py-3">
                <p class="text-sm text-muted mb-1">Rentabilidad</p>
                <h5 class="mb-0 {{ $profitabilityAmount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $profitabilityAmount, 2) }} {{ $project->currency_code }}</h5>
                <p class="text-xs text-muted mb-0 mt-1">{{ number_format((float) $profitabilityPercent, 2) }}%</p>
              </div>
            </div>
          </div>
        </div>

        <div class="card border mb-4">
          <div class="card-header pb-0"><h6 class="mb-0">Tareas del proyecto</h6></div>
          <div class="card-body">
            <form method="POST" action="{{ route('projects.module.projects.tasks.store', $project) }}" class="row g-3 mb-3">
              @csrf
              <div class="col-md-3"><label class="form-label">Titulo</label><input type="text" name="title" class="form-control border border-1 p-2" required></div>
              <div class="col-md-3"><label class="form-label">Responsable (opcional)</label><select name="responsible_team_member_id" class="form-control border border-1 p-2"><option value="">Sin responsable</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>
              <div class="col-md-2"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="todo">Pendiente</option><option value="in_progress">En progreso</option><option value="done">Finalizada</option></select></div>
              <div class="col-md-2"><label class="form-label">Vence</label><input type="date" name="due_date" class="form-control border border-1 p-2"></div>
              <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-dark mb-0 w-100">Agregar tarea</button></div>
              <div class="col-12"><label class="form-label">Detalle (opcional)</label><input type="text" name="description" class="form-control border border-1 p-2"></div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm align-items-center mb-0">
                <thead><tr><th>Tarea</th><th>Responsable</th><th>Estado</th><th>Vence</th><th>Accion</th></tr></thead>
                <tbody>
                  @forelse($project->tasks as $task)
                    <tr>
                      <td>{{ $task->title }}</td>
                      <td>{{ $task->responsibleMember->full_name ?? 'Sin responsable' }}</td>
                      <td>{{ strtoupper($task->status) }}</td>
                      <td>{{ optional($task->due_date)->format('d/m/Y') ?: '-' }}</td>
                      <td>
                        <form method="POST" action="{{ route('projects.module.projects.tasks.status', $task) }}" class="d-flex gap-2">
                          @csrf
                          <select name="status" class="form-control border border-1 p-2 form-control-sm">
                            <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>Pendiente</option>
                            <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>En progreso</option>
                            <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Finalizada</option>
                          </select>
                          <button class="btn btn-outline-dark btn-sm mb-0" type="submit">Actualizar</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center text-muted">Sin tareas registradas para este proyecto.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card border mb-4">
          <div class="card-header pb-0"><h6 class="mb-0">Agregar informacion adicional del proyecto</h6></div>
          <div class="card-body">
            <form method="POST" action="{{ route('projects.module.projects.assets.store', $project) }}" enctype="multipart/form-data" class="row g-3">
              @csrf
              <div class="col-md-3">
                <label class="form-label">Tipo de registro</label>
                <select name="asset_type" class="form-control border border-1 p-2" required>
                  <option value="reference_image">Imagen de referencia</option>
                  <option value="process_image">Imagen del proceso</option>
                  <option value="task_image">Imagen de tarea</option>
                  <option value="final_image">Imagen finalizada</option>
                  <option value="documentation">Documentacion</option>
                  <option value="expense">Gasto</option>
                  <option value="payment">Pago</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Fase asociada</label>
                <select name="phase" class="form-control border border-1 p-2">
                  @foreach($phaseOrder as $phaseOption)
                    <option value="{{ $phaseOption }}" {{ $phaseOption === $phaseKey ? 'selected' : '' }}>{{ $phaseLabels[$phaseOption] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Tarea relacionada (opcional)</label>
                <select name="task_id" class="form-control border border-1 p-2">
                  <option value="">Sin tarea especifica</option>
                  @foreach($project->tasks as $task)
                    <option value="{{ $task->id }}">{{ $task->title }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3"><label class="form-label">Monto (si aplica)</label><input type="number" min="0" step="0.01" name="amount" class="form-control border border-1 p-2"></div>
              <div class="col-md-3"><label class="form-label">Moneda (si aplica)</label><select name="currency_code" class="form-control border border-1 p-2"><option value="">--</option><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
              <div class="col-md-4"><label class="form-label">Titulo</label><input type="text" name="title" class="form-control border border-1 p-2" placeholder="Titulo/etiqueta"></div>
              <div class="col-md-4"><label class="form-label">Fecha</label><input type="date" name="happened_at" class="form-control border border-1 p-2"></div>
              <div class="col-md-4"><label class="form-label">Archivo (imagen, video o documento)</label><input type="file" name="asset_file" class="form-control border border-1 p-2" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.pdf,.doc,.docx,.xls,.xlsx"></div>
              <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
              <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Guardar registro</button></div>
            </form>
          </div>
        </div>

        <div class="row g-4">
          @foreach([
            'reference_image' => 'Imagenes de referencia',
            'process_image' => 'Imagenes del proceso',
            'task_image' => 'Imagenes de tareas',
            'final_image' => 'Imagenes finalizadas',
            'documentation' => 'Documentacion',
            'expense' => 'Gastos',
            'payment' => 'Pagos',
          ] as $key => $label)
            <div class="col-12 col-xl-6">
              <div class="card border h-100">
                <div class="card-header pb-0"><h6 class="mb-0">{{ $label }}</h6></div>
                <div class="card-body">
                  @php $records = $assetsByType->get($key, collect()); @endphp
                  @if($records->isEmpty())
                    <p class="text-muted mb-0">Sin registros.</p>
                  @else
                    <div class="table-responsive">
                      <table class="table table-sm mb-0">
                        <thead><tr><th>Fecha</th><th>Titulo</th><th>Detalle</th><th>Archivo</th></tr></thead>
                        <tbody>
                          @foreach($records as $record)
                            <tr>
                              <td>{{ optional($record->happened_at)->format('d/m/Y') ?: '-' }}</td>
                              <td>{{ $record->title ?: '-' }}</td>
                              <td>
                                @if(!is_null($record->amount))
                                  {{ number_format((float) $record->amount, 2) }} {{ $record->currency_code ?: '' }}
                                @endif
                                @if($record->task)
                                  <div class="text-xs text-muted">Tarea: {{ $record->task->title }}</div>
                                @endif
                                @if($record->notes)
                                  <div class="text-xs text-muted">{{ $record->notes }}</div>
                                @endif
                              </td>
                              <td>
                                @if($record->file_path)
                                  @php $fileExt = strtolower(pathinfo((string) $record->file_path, PATHINFO_EXTENSION)); @endphp
                                  @if(in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'], true))
                                    <div class="mb-1">
                                      <img src="{{ route('projects.module.projects.assets.file', $record) }}" alt="{{ $record->title ?: 'imagen' }}" style="max-width: 120px; max-height: 80px; object-fit: cover; border-radius: 6px;">
                                    </div>
                                  @endif
                                  <a href="{{ route('projects.module.projects.assets.file', $record) }}" target="_blank">Abrir</a>
                                @else
                                  -
                                @endif
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
