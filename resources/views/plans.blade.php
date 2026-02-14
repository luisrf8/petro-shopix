@extends('layouts.app')

@section('title', 'Planes')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <!-- Header con título y botón -->
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3">PLANES</h6>
            <button class="btn btn-sm btn-light me-3" data-bs-toggle="modal" data-bs-target="#createPlanModal">
              + Agregar Plan
            </button>
          </div>
        </div>

        <!-- Grid de Cards -->
        <div class="card-body px-4 pb-4">
          <div class="row">
            @foreach($plans as $plan)
            <div class="col-md-4 mb-4">
              <div class="card h-100 shadow">
                <div class="card-body d-flex flex-column">
                  @if($plan->image)
                    <div class="d-flex justify-content-center">
                      <img src="{{ $plan->image }}" alt="{{ $plan->name }}" class="img-fluid mb-3 rounded w-50">
                    </div>
                  @endif
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">{{ $plan->name }}</h5>
                    <div class="mt-auto d-flex justify-content-between align-items-center gap-2 h-100">
                        <i class="material-symbols-rounded btn-edit-plan cursor-pointer text-info"
                        data-id="{{ $plan->id }}"
                        data-name="{{ $plan->name }}"
                        data-price="{{ $plan->price }}"
                        data-duration="{{ $plan->duration_days }}"
                        data-status="{{ $plan->status }}"
                        data-image="{{ $plan->image }}"
                        data-features="{{ json_encode($plan->features) }}">
                          mode_edit
                        </i>
                        <i class="material-symbols-rounded btn-delete-plan cursor-pointer text-danger"
                        data-id="{{ $plan->id }}">horizontal_rule</i>
                    </div>
                  </div>

                  <p><strong>Monto a pagar:</strong> <span class="text-dark">${{ number_format($plan->price,2) }}</span></p>
                  <p><strong>Duración:</strong> {{ $plan->duration_days }} días</p>
                    <h6>Características:</h6>
                    @if($plan->features && is_array($plan->features))
                    <div class="mb-3 d-flex flex-column gap-1">
                        @foreach($plan->features as $feature)
                            <span>✔ {{ $feature }}</span>
                        @endforeach
                    </div>
                    @else
                        <span>No hay características registradas</span>
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
</div>

<!-- Modal Crear Plan -->
<div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="createPlanForm" class="modal-content" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Crear Nuevo Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="createPlanName" class="form-label">Nombre</label>
          <input type="text" class="form-control border border-1 p-2" id="createPlanName" name="name" required>
        </div>
        <div class="mb-3">
          <label for="createPlanPrice" class="form-label">Precio</label>
          <input type="number" step="0.01" class="form-control border border-1 p-2" id="createPlanPrice" name="price" required>
        </div>
        <div class="mb-3">
          <label for="createPlanDuration" class="form-label">Duración (días)</label>
          <input type="number" class="form-control border border-1 p-2" id="createPlanDuration" name="duration_days" required>
        </div>
        <div class="mb-3">
          <label for="createPlanImage" class="form-label">Imagen (archivo opcional)</label>
          <div class="mb-2 text-center">
            <img id="createPlanImagePreview" src="" alt="Vista previa de imagen" class="img-fluid rounded" style="max-height:140px; display:none;">
          </div>
          <input type="file" class="form-control border border-1 p-2" id="createPlanImage" name="image" accept="image/*">
        </div>
        <div class="mb-3">
          <label for="createPlanStatus" class="form-label">Estado</label>
          <select class="form-select border border-1 p-2" id="createPlanStatus" name="status">
            <option value="0">Disponible</option>
            <option value="1">Inactivo</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Características</label>
          <div id="createFeaturesContainer" class="d-flex flex-column gap-2"></div>
          <div class="mt-2">
            <button type="button" class="btn btn-outline-dark btn-sm" id="addCreateFeatureBtn">+ Agregar característica</button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar Plan -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editPlanForm" class="modal-content" enctype="multipart/form-data">
      @csrf
      <input type="hidden" id="editPlanId" name="id">
      <div class="modal-header">
        <h5 class="modal-title">Editar Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="editPlanName" class="form-label">Nombre</label>
          <input type="text" class="form-control border border-1 p-2" id="editPlanName" name="name" required>
        </div>
        <div class="mb-3">
          <label for="editPlanPrice" class="form-label">Precio</label>
          <input type="number" step="0.01" class="form-control border border-1 p-2" id="editPlanPrice" name="price" required>
        </div>
        <div class="mb-3">
          <label for="editPlanDuration" class="form-label">Duración (días)</label>
          <input type="number" class="form-control border border-1 p-2" id="editPlanDuration" name="duration_days" required>
        </div>
        <div class="mb-3">
          <label for="editPlanImage" class="form-label">Imagen (archivo opcional)</label>
          <div class="mb-2 text-center">
            <img id="editPlanCurrentImage" src="" alt="Imagen actual del plan" class="img-fluid rounded" style="max-height:140px; display:none;">
            <small id="editPlanNoImage" class="text-muted d-block">Este plan no tiene imagen actual</small>
            <img id="editPlanNewImagePreview" src="" alt="Vista previa nueva imagen" class="img-fluid rounded mt-2" style="max-height:140px; display:none;">
          </div>
          <input type="file" class="form-control border border-1 p-2" id="editPlanImage" name="image" accept="image/*">
        </div>
        <div class="mb-3">
          <label for="editPlanStatus" class="form-label">Estado</label>
          <select class="form-select border border-1 p-2" id="editPlanStatus" name="status">
            <option value="0">Disponible</option>
            <option value="1">Inactivo</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Características</label>
          <div id="editFeaturesContainer" class="d-flex flex-column gap-2"></div>
          <div class="mt-2">
            <button type="button" class="btn btn-outline-dark btn-sm" id="addEditFeatureBtn">+ Agregar característica</button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-info">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function createFeatureRow(value = '') {
    const row = document.createElement('div');
    row.className = 'input-group';
    row.innerHTML = `
      <input type="text" class="form-control border border-1 p-2 feature-input" placeholder="Ej: Soporte 24/7" value="${value.replace(/"/g, '&quot;')}">
      <button type="button" class="btn btn-outline-danger remove-feature-btn">-</button>
    `;

    row.querySelector('.remove-feature-btn').addEventListener('click', () => {
      row.remove();
    });

    return row;
  }

  function getFeaturesFromContainer(containerId) {
    return Array.from(document.querySelectorAll(`#${containerId} .feature-input`))
      .map(input => input.value.trim())
      .filter(Boolean);
  }

  function setFeaturesInContainer(containerId, features) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';

    const items = Array.isArray(features) && features.length ? features : [''];
    items.forEach(feature => {
      container.appendChild(createFeatureRow(feature));
    });
  }

  document.getElementById('addCreateFeatureBtn').addEventListener('click', () => {
    document.getElementById('createFeaturesContainer').appendChild(createFeatureRow(''));
  });

  document.getElementById('addEditFeatureBtn').addEventListener('click', () => {
    document.getElementById('editFeaturesContainer').appendChild(createFeatureRow(''));
  });

  setFeaturesInContainer('createFeaturesContainer', ['']);

  function bindImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    input.addEventListener('change', event => {
      const file = event.target.files?.[0];
      if (!file) {
        preview.src = '';
        preview.style.display = 'none';
        return;
      }

      const reader = new FileReader();
      reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  }

  bindImagePreview('createPlanImage', 'createPlanImagePreview');
  bindImagePreview('editPlanImage', 'editPlanNewImagePreview');

  // Crear Plan
  document.getElementById('createPlanForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const createFeatures = getFeaturesFromContainer('createFeaturesContainer');
    formData.set('features', createFeatures.join(','));

    fetch(`/api/plans`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
      body: formData
    })
    .then(res => res.json())
    .then(() => { 
      alert('Plan creado correctamente'); 
      location.reload(); 
    })
    .catch(err => { console.error(err); alert('Error al crear el plan'); });
  });

  // Abrir modal con datos de edición
  document.querySelectorAll('.btn-edit-plan').forEach(button => {
    button.addEventListener('click', function () {
      document.getElementById('editPlanId').value = this.dataset.id;
      document.getElementById('editPlanName').value = this.dataset.name;
      document.getElementById('editPlanPrice').value = this.dataset.price;
      document.getElementById('editPlanDuration').value = this.dataset.duration;
      document.getElementById('editPlanStatus').value = this.dataset.status ?? '0';

      const currentImage = document.getElementById('editPlanCurrentImage');
      const noImageText = document.getElementById('editPlanNoImage');
      const imagePath = this.dataset.image;

      if (imagePath) {
        currentImage.src = imagePath;
        currentImage.style.display = 'block';
        noImageText.style.display = 'none';
      } else {
        currentImage.src = '';
        currentImage.style.display = 'none';
        noImageText.style.display = 'block';
      }

      const editNewPreview = document.getElementById('editPlanNewImagePreview');
      const editImageInput = document.getElementById('editPlanImage');
      editImageInput.value = '';
      editNewPreview.src = '';
      editNewPreview.style.display = 'none';

      let features = [];
      try {
        const parsed = JSON.parse(this.dataset.features || '[]');
        features = Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        features = (this.dataset.features || '').split(',').map(item => item.trim());
      }

      setFeaturesInContainer('editFeaturesContainer', features);

      new bootstrap.Modal(document.getElementById('editPlanModal')).show();
    });
  });

  // Guardar cambios al editar
  document.getElementById('editPlanForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const planId = document.getElementById('editPlanId').value;
    const formData = new FormData(this);
    const editFeatures = getFeaturesFromContainer('editFeaturesContainer');
    formData.set('features', editFeatures.join(','));

    fetch(`/api/plans/${planId}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
      body: formData
    })
    .then(res => res.json())
    .then(() => { alert('Plan actualizado'); location.reload(); })
    .catch(err => { console.error(err); alert('Error al actualizar el plan'); });
  });

  // Eliminar plan
  document.querySelectorAll('.btn-delete-plan').forEach(button => {
    button.addEventListener('click', function () {
      if(!confirm("¿Eliminar este plan?")) return;
      const planId = this.dataset.id;
      fetch(`/api/plans/${planId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
      })
      .then(() => null)
      .then(() => { alert('Plan eliminado'); location.reload(); })
      .catch(err => { console.error(err); alert('Error al eliminar el plan'); });
    });
  });
</script>
@endpush
