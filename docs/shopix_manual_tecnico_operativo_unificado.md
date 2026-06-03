# SHOPIX - Manual Tecnico Operativo Unificado

Version: 1.0  
Fecha: 2026-05-28  
Estado: Vigente segun rutas y controladores actuales

## 1. Objetivo del documento

Unificar en un solo documento:

- El enfoque comercial (brochure).
- La documentacion funcional general.
- El manual tecnico con consumo real de endpoints.
- El levantamiento operativo alineado al flujo tecnico de implementacion.

Este documento describe lo que el sistema hace hoy, con base en rutas y controladores reales del proyecto.

## 2. Resumen ejecutivo (comercial + funcional)

Shopix es una plataforma para operar negocios tipo tienda, servicio o mixto desde un mismo entorno:

- Venta de productos (catalogo, variantes, stock, pedidos, pagos).
- Agenda y servicios (profesionales, disponibilidad, workflow de citas).
- Operacion interna (usuarios, roles, almacenes, cuentas por cobrar/pagar, reportes).
- Operacion publica por tenant slug (vitrina, checkout, disponibilidad de citas).

Resultado esperado para cliente final:

- Arranque mas rapido con levantamiento estructurado.
- Menor dispersion operativa.
- Control real de operacion comercial, inventario y atencion.

## 3. Alcance tecnico real

### 3.1 Superficies de consumo

- API bajo prefijo `api/*`.
- Rutas web autenticadas (sesion + middleware por rol/plan).
- Rutas publicas por tenant slug `/{tenant:slug}/*`.

### 3.2 Modulos operativos activos

- Tenant y configuracion de tienda.
- Productos, categorias y variantes.
- Metodos de pago y monedas.
- Ventas, ordenes y despacho.
- Citas (servicios, horarios, workflow).
- Almacenes y movimientos de inventario.
- Cuentas por pagar y abonos.
- Reportes y documentos PDF/Excel.

## 4. Autenticacion y contexto de consumo

### 4.1 API

- Publico: endpoints de catalogo y algunos endpoints de alta/actualizacion estan expuestos sin `auth.jwt`.
- Protegido: bloque en `Route::middleware(['auth.jwt'])` para perfil cliente/ecommerce.

### 4.2 Web

- Requiere sesion (`auth`) y middlewares operativos (`backoffice.access`, restricciones por plan, estado de tenant y rol).

### 4.3 Tenant publico

- Operaciones de storefront por slug para catalogo, metodos de pago, disponibilidad y checkout pro.

## 5. Consumo real de endpoints (por flujo)

## 5.1 Tenant y onboarding

### Importacion de levantamiento DOCX

- `POST /tenant-import-setup-docx`
  - Controlador: `TenantController@importSetupDocument`
  - Input requerido: `setup_docx` (archivo `.docx`, max 5 MB)
  - Salida: `payload` estructurado + `summary` de filas parseadas.

### Actualizacion de tienda con payload importado

- `POST /tenant-update`
  - Controlador: `TenantController@updateTenant`
  - Soporta `import_payload` (string JSON) para aplicar datos levantados.

### Alta publica de tenant

- `POST /tenants-public`
  - Controlador: `TenantController@storePublic`
  - Campos claves: identidad, ubicacion, tipo de negocio, owner, plan, terminos.

### Plan payments

- `POST /tenant-store/plan-payment-request`
- `POST /tenants/{tenant}/plan-payments/{payment}/approve`
- `POST /tenants/{tenant}/plan-payments/{payment}/cutoff`
- `POST /tenants/{tenant}/plan-payments/{payment}/reject`

## 5.2 Catalogo tienda

### Lectura

- `GET /api/products`
- `GET /api/get-products`
- `GET /api/products/{id}`
- `GET /api/products/all`
- `GET /products`

### Escritura

- `POST /create-product`
- `POST /api/products/{id}`
- `DELETE /api/products/{id}`
- `POST /api/products/import-catalog`
- `POST /api/products/{product}/generate-codes`
- `POST /api/addImage/{productId}`
- `DELETE /api/product/remove-image/{imageId}`

### Categorias

- `GET /api/categories`
- `POST /api/create-category`
- `POST /api/categories/{category}`
- `POST /api/categories/{id}/toggle-status`

### Variantes

- `POST /api/variants/store`
- `PUT /api/variants/{productVariant}`
- `PUT /api/variants/{productVariant}/barcode`
- `POST /api/variants/{productVariant}/generate-codes`

## 5.3 Metodos de pago y monedas

### Metodos de pago

- `GET /api/payment-methods`
- `GET /api/payment-methods/ecomm`
- `POST /api/payment-methods/create`
- `POST /api/payment-methods/{id}/edit`
- `POST /api/payment-methods/{id}/toggleStatus`
- `POST /api/payment-methods/update-qr/{id}`
- `POST /api/payment-methods/remove-qr/{id}`

### Monedas y tasas

- `POST /api/currencies/create`
- `POST /api/currencies/{id}/update`
- `POST /api/currencies/{id}/currencyToggleStatus`
- `POST /api/dollar-rate/update`
- `POST /api/euro-rate/update`
- `GET /api/dollarRate`
- `POST /api/tenant-base-currency/update`

## 5.4 Ventas, ordenes y despacho

### Venta

- `POST /create-sale`
- `POST /api/create-sale/ecomm`
- `POST /{tenant}/checkout/pro`

### Ordenes y estado

- `GET /sales-orders`
- `GET /sales-orders/pending-delivery`
- `GET /sales/{id}`
- `POST /api/order/{id}/status/update`
- `POST /api/payment/{id}/status/update`
- `POST /api/deliver/{id}/status/update`

### Delivery assignment

- `POST /sales-orders/{order}/assign-delivery-user`

### Documentos

- `GET /sales-orders/{id}/pdf`
- `GET /sales-orders/{id}/pdfs/{type}`
- `GET /publicOrder/{id}/pdfs/{type}`

## 5.5 Citas y servicios

### Consulta y agenda

- `GET /appointments`
- `POST /appointments`
- `GET /appointments/available-slots`
- `GET /api/user/appointments`
- `GET /api/user/appointments/{appointment}/available-slots`
- `GET /{tenant}/appointments/public-availability`

### Configuracion de modulo citas

- `POST /appointments/services`
- `POST /appointments/schedules`
- `POST /appointments/packages`

### Workflow de cita

- `POST /appointments/{appointment}/workflow`
- `POST /api/user/appointments/{appointment}/action`

## 5.6 Inventario y operacion de almacenes

- `GET /warehouses`
- `POST /warehouses`
- `PUT /warehouses/{warehouse}`
- `POST /warehouses/movements`
- `PUT /warehouses/movements/{movement}`

## 5.7 Cuentas por pagar

- `GET /accounts-payable`
- `POST /accounts-payable`
- `POST /accounts-payable/{accountPayable}/payments`

## 6. CRUDs reales por entidad

## 6.1 Tenant

- Create: `POST /api/tenants`, `POST /tenants-public`, `POST /tenants`.
- Read: `GET /tenant-store`, `GET /tenants`, `GET /{tenant}`.
- Update: `POST /tenant-update`, `POST /api/tenants/{tenant}`, `PUT/PATCH /tenants/{tenant}`.
- Delete: `DELETE /api/tenants/{tenant}`, `DELETE /tenants/{tenant}`.

## 6.2 Productos y categorias

- Product Create/Update/Delete: `POST /create-product`, `POST /api/products/{id}`, `DELETE /api/products/{id}`.
- Product Read: `GET /api/products`, `GET /products`, `GET /products/{category}`.
- Category Create/Update/Toggle: `POST /api/create-category`, `POST /api/categories/{category}`, `POST /api/categories/{id}/toggle-status`.

## 6.3 Variantes

- Create: `POST /api/variants/store`.
- Read: embebido en endpoints de producto/ventas.
- Update: `PUT /api/variants/{productVariant}`, `PUT /api/variants/{productVariant}/barcode`.
- Operacion adicional: `POST /api/variants/{productVariant}/generate-codes`.

## 6.4 Payment methods

- Create: `POST /api/payment-methods/create`.
- Read: `GET /api/payment-methods`, `GET /api/payment-methods/ecomm`, `GET /{tenant}/payment-methods`.
- Update: `POST /api/payment-methods/{id}/edit`, `POST /api/payment-methods/update-qr/{id}`.
- Soft status: `POST /api/payment-methods/{id}/toggleStatus`, `POST /api/payment-methods/remove-qr/{id}`.

## 6.5 Citas

- Create: `POST /appointments`.
- Read: `GET /appointments`, `GET /api/user/appointments`.
- Update operativa: `POST /appointments/{appointment}/workflow`, `POST /api/user/appointments/{appointment}/action`.
- Delete: no endpoint directo expuesto (gestion por workflow/estado).

## 6.6 Almacenes y movimientos

- Warehouse Create/Read/Update: `POST /warehouses`, `GET /warehouses`, `PUT /warehouses/{warehouse}`.
- Movement Create/Update: `POST /warehouses/movements`, `PUT /warehouses/movements/{movement}`.
- Delete: no endpoint directo para warehouse ni movimientos.

## 6.7 Cuentas por pagar

- Create: `POST /accounts-payable`.
- Read: `GET /accounts-payable`.
- Update financiera: `POST /accounts-payable/{accountPayable}/payments`.
- Delete: no endpoint directo expuesto.

## 7. Flujos reales de negocio

## 7.1 Flujo real: levantamiento operativo + configuracion tecnica inicial

1. Levantar datos del cliente con formulario DOCX oficial (sin alterar encabezados).
2. Importar documento:
   - `POST /tenant-import-setup-docx` con `setup_docx`.
3. Revisar `payload` y `summary` devueltos.
4. Persistir configuracion en tenant:
   - `POST /tenant-update` con datos de tienda + `import_payload`.
5. Validar modulos cargados:
   - usuarios,
   - metodos de pago,
   - catalogo tienda,
   - catalogo servicio,
   - horarios por profesional.
6. Activar salida operativa por tipo de negocio (tienda/servicio/mixto).

Vinculo tecnico: `ShopixSetupDocumentService` parsea tablas DOCX y `ShopixSetupImportService` aplica sincronizacion por modulo.

## 7.2 Flujo real: venta ecommerce y checkout pro publico

1. Cliente consulta tienda publica por slug.
2. Consulta catalogo y metodos de pago.
3. Ejecuta checkout:
   - estandar ecomm: `POST /api/create-sale/ecomm`
   - checkout completo publico: `POST /{tenant}/checkout/pro`
4. Sistema valida:
   - cliente,
   - items,
   - disponibilidad de stock,
   - restricciones de delivery por plan/ciudad,
   - pagos y referencias.
5. Sistema crea orden y detalle; calcula fee delivery cuando aplica.
6. Operacion interna asigna delivery:
   - `POST /sales-orders/{order}/assign-delivery-user`.
7. Seguimiento de estado y documentos PDF.

## 7.3 Flujo real: agenda de citas

1. Configurar servicios (`POST /appointments/services`).
2. Configurar horarios (`POST /appointments/schedules`).
3. Consultar disponibilidad (`GET /appointments/available-slots` o endpoint publico).
4. Crear cita (`POST /appointments`).
5. Gestionar workflow:
   - interno: `POST /appointments/{appointment}/workflow`
   - cliente: `POST /api/user/appointments/{appointment}/action`
6. Acciones permitidas segun rol/contexto:
   - `confirm_attendance`, `cancel`, `reschedule`, `confirm_payment`.

## 7.4 Flujo real: inventario y almacenes

1. Crear/ajustar almacenes.
2. Registrar movimiento (`POST /warehouses/movements`).
3. Actualizar movimiento si aplica (`PUT /warehouses/movements/{movement}`).
4. Sistema valida pertenencia de variante/almacen al tenant y consistencia de movimiento.

## 7.5 Flujo real: cuentas por pagar

1. Registrar cuenta (`POST /accounts-payable`).
2. Registrar abonos (`POST /accounts-payable/{accountPayable}/payments`).
3. Sistema recalcula `amount_paid`, `amount_pending` y estado (`pending`, `partial`, `paid`, `overdue`).

## 8. Levantamiento operativo alineado al manual tecnico

El levantamiento operativo se ejecuta en paralelo al manual tecnico usando el mismo payload estructurado.

## 8.1 Campos obligatorios para arranque minimo

- Nombre comercial, slug, correo, tipo de negocio, rubro.
- Pais, estado, ciudad, telefono.
- Owner inicial (nombre, correo, password, telefono, dni).
- Plan.

## 8.2 Bloques operativos importables

- Datos generales tenant.
- Usuarios iniciales.
- Metodos de pago.
- Catalogo tienda.
- Catalogo de servicios.
- Reglas de horario por profesional.

## 8.3 Reglas operativas criticas

- No alterar encabezados del DOCX importable.
- Formato de horarios en `HH:MM`.
- Dias de horario validos por catalogo de dias interno.
- Delivery sujeto a capacidades de plan y restricciones de ciudad.
- Citas sujetas a disponibilidad real por profesional/servicio.

## 9. Ejemplos de consumo (realistas)

## 9.1 Importar levantamiento DOCX

```bash
curl -X POST http://localhost/tenant-import-setup-docx \
  -H "X-CSRF-TOKEN: <csrf>" \
  -b "laravel_session=<session>" \
  -F "setup_docx=@shopix_formulario_levantamiento_operativo_importable.docx"
```

## 9.2 Crear movimiento de almacen

```bash
curl -X POST http://localhost/warehouses/movements \
  -H "X-CSRF-TOKEN: <csrf>" \
  -b "laravel_session=<session>" \
  -d "product_variant_id=10" \
  -d "movement_type=transfer" \
  -d "source_warehouse_id=1" \
  -d "destination_warehouse_id=2" \
  -d "quantity=5" \
  -d "moved_at=2026-05-28"
```

## 9.3 Registrar accion de workflow de cita (cliente API)

```bash
curl -X POST http://localhost/api/user/appointments/125/action \
  -H "Authorization: Bearer <jwt>" \
  -H "Accept: application/json" \
  -d "action=confirm_payment" \
  -d "payment_method_id=3" \
  -d "paid_amount=20.00"
```

## 10. Riesgos operativos y controles

- Riesgo: encabezados DOCX alterados.
  - Control: usar plantilla oficial sin cambios estructurales.
- Riesgo: ubicacion inconsistente para delivery.
  - Control: validar jerarquia pais/estado/ciudad antes de activar despacho.
- Riesgo: servicios sin profesional o sin horario.
  - Control: bloquear salida de agenda hasta tener reglas activas.
- Riesgo: sobreventa por stock incorrecto inicial.
  - Control: validacion de stock en carga y conciliacion inicial de inventario.

## 11. Definicion operativa final

En Shopix, el levantamiento operativo y el manual tecnico son un solo proceso:

- El levantamiento define el dato de negocio real.
- El manual tecnico define como ese dato se ingiere, valida y opera por endpoints.
- La salida a produccion se aprueba solo cuando CRUDs y flujos criticos estan validados con datos reales del tenant.
