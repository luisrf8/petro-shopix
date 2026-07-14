# SHOPIX - Documentacion Tecnica Completa (Roles, API y Funcionamiento)

Version: 1.0  
Fecha: 2026-07-13  
Estado: Generado desde rutas reales del proyecto

## 1. Fuente tecnica oficial usada

Esta documentacion se construyo con base en las rutas reales declaradas en:

- routes/web.php
- routes/api.php
- routes/auth.php
- routes/channels.php
- app/Http/Kernel.php

Nota: esta guia documenta el comportamiento operativo observable por rutas y middleware. Es la referencia tecnica de acceso y funciones por rol.

## 2. Arquitectura funcional de Shopix

Shopix opera en 5 superficies:

1. Backoffice web autenticado (sesion): operacion interna por tenant.
2. API /api (mixta): endpoints publicos + endpoints con JWT (auth.jwt).
3. Storefront publico por slug: /{tenant:slug}.
4. Canales de broadcasting: eventos en tiempo real por tenant.
5. Consola artisan: comandos operativos.

## 3. Middleware y seguridad

### 3.1 Middleware global

- TrustProxies
- PreventRequestsDuringMaintenance
- ValidatePostSize
- TrimStrings
- ConvertEmptyStringsToNull
- HandleCors

### 3.2 Grupo web

- Cookies + Session + CSRF
- SubstituteBindings
- AuditTrailMiddleware

### 3.3 Grupo api

- EnsureFrontendRequestsAreStateful
- throttle:api
- SubstituteBindings
- AuditTrailMiddleware

### 3.4 Middleware de control de acceso clave

- auth
- auth.jwt
- role.name
- backoffice.access
- free.plan.access
- basic.plan.access
- inactive.tenant.restrict

## 4. Roles oficiales detectados (sin excepcion)

Roles por nombre:

- owner
- admin
- administrador
- vendor
- vendedor
- seller
- almacen
- almacenista
- warehouse
- delivery
- repartidor

Rol por id:

- 4 (super admin de plataforma)

Actores no backoffice tambien relevantes:

- cliente ecommerce JWT (usuario autenticado por auth.jwt)
- invitado web (guest)
- visitante publico tenant (sin login)

## 5. Equivalencias operativas de roles

Para operacion diaria, el sistema usa grupos equivalentes:

1. Comercial: vendor, vendedor, seller.
2. Almacen: almacen, almacenista, warehouse.
3. Delivery: delivery, repartidor.
4. Administracion de tienda: owner, admin, administrador.
5. Plataforma: role.name:4.

## 6. Matriz completa de funciones por rol

## 6.1 role.name:4 (super admin plataforma)

Funciones:

- Gestion global de usuarios: GET /users.
- Gestion global de tenants:
  - GET /tenants
  - GET /tenant-payments
  - GET /create-tenant
  - Route::resource('tenants') -> index, create, store, show, edit, update, destroy.
- Importacion DOCX de setup tenant: POST /tenant-import-setup-docx.
- Aprobacion/rechazo/corte de pagos de plan:
  - POST /tenants/{tenant}/plan-payments/{payment}/approve
  - POST /tenants/{tenant}/plan-payments/{payment}/cutoff
  - POST /tenants/{tenant}/plan-payments/{payment}/reject
- Planes globales:
  - GET /plans
  - API: POST /api/plans, POST /api/plans/{id}, DELETE /api/plans/{id}
- Impuestos globales:
  - GET /taxes
  - POST /taxes/create
  - POST /taxes/update/{tax}
  - POST /taxes/toggle/{tax}
- Monitor y soporte:
  - GET /logs
  - GET /documentation
  - GET /documentation/download/{document}
- Documentos electronicos globales:
  - GET /electronic-documents
  - POST /electronic-documents/{electronicDocument}/retry

## 6.2 owner

Funciones:

- Acceso a configuracion de tienda:
  - GET /tenant-store
  - POST /tenant-update
  - POST /tenant-store/users/{id}/update
  - POST /tenant-store/users/{id}/toggle-status
  - POST /tenant-store/plan-payment-request
- Gestion completa de catalogo (productos/categorias/variantes/codigos).
- Gestion metodos de pago y tasas.
- Gestion ventas completa (crear venta, estados, pagos, retornos, PDFs).
- Emision y anulacion fiscal/electronica segun endpoints de ventas.
- Citas y workflow de citas.
- Compras, proveedores, gastos, cuentas por pagar, retenciones.
- Almacenes y movimientos.
- Materiales/paquetes.
- Reporteria completa.
- Modulo proyectos (si la ruta del modulo incluye owner).
- Cotizaciones y conversiones (a proyecto, venta, inventario).

## 6.3 admin y administrador

Funciones:

- Equivalentes operativos de owner en la mayoria de modulos.
- En rutas con restriccion owner exclusiva (ej: solicitud de pago de plan) no sustituyen owner.
- Pueden operar catalogo, ventas, citas, compras, almacenes, reportes y proyectos segun middleware especifico de cada endpoint.

## 6.4 vendor, vendedor, seller (comercial)

Funciones:

- Catalogo operativo:
  - ver/crear/actualizar productos
  - generar codigos de productos/variantes
  - ver categorias
- Ventas:
  - crear venta
  - ver ordenes
  - actualizar estados de orden/pago (via endpoints permitidos)
  - ver detalle de orden
  - descargar documentos permitidos
- Clientes:
  - listar, crear, actualizar, activar/inactivar
- Citas:
  - crear/gestionar citas y workflow
- Comisiones (seller specific views):
  - GET /seller-commissions/progress
  - GET /seller-commissions/progress/pdf
- Cotizaciones:
  - CRUD operativo y conversiones

## 6.5 almacen, almacenista, warehouse (operacion de inventario)

Funciones:

- Ver catalogo para operacion logistico-comercial.
- Almacenes:
  - GET /warehouses
  - POST /warehouses/movements
  - PUT /warehouses/movements/{movement}
  - (store/update de warehouse reservado a owner/admin/administrador)
- Compras y proveedores:
  - GET /purchase
  - GET/POST/PUT proveedores
  - GET /purchase-orders
  - GET /order/{id}
- Cuentas por pagar:
  - GET /accounts-payable
  - POST /accounts-payable
  - POST /accounts-payable/{accountPayable}/payments
- Retenciones de compras (descarga/sync/status segun endpoints).
- Proyectos y nomina (modulo projects-module, nomina, proyectos).
- Cotizaciones y conversiones cuando endpoint incluye roles de almacen.

## 6.6 delivery y repartidor

Funciones:

- Operacion de entregas:
  - GET /sales-orders/pending-delivery
  - GET /paid-pending-deliveries
  - GET /sales/{id}
  - POST /sales-orders/{order}/assign-delivery-user
- No tienen acceso general a catalogo, compras o configuraciones administrativas salvo endpoints explicitamente compartidos.

## 6.7 cliente ecommerce autenticado (auth.jwt)

Funciones API:

- Perfil:
  - GET /api/user
  - POST /api/user/update-profile
  - POST /api/user/change-password
- Compras:
  - POST /api/create-sale/ecomm
  - GET /api/user/orders
- Citas:
  - GET /api/user/appointments
  - GET /api/user/appointments/{appointment}/available-slots
  - POST /api/user/appointments/{appointment}/action
- Notificaciones:
  - GET /api/notifications
  - POST /api/notifications/{id}/read
  - POST /api/push-subscriptions
  - DELETE /api/push-subscriptions

## 6.8 invitado web / visitante publico

Funciones:

- Landing y pagina publica principal.
- Login y registro web.
- Login social cliente (Google).
- Storefront por slug:
  - GET /{tenant:slug}
  - GET /{tenant:slug}/categorias
  - GET /{tenant:slug}/payment-methods
  - GET /{tenant:slug}/appointments/public-availability
  - GET /{tenant:slug}/{product}
  - POST /{tenant:slug}/checkout/pro
  - POST /{tenant:slug}/scan-code
- Alta publica de tenant:
  - POST /tenants-public

## 7. Catalogo completo API (/api)

## 7.1 API publica

Auth y acceso:

- POST /api/logout
- POST /api/loginEcomm
- POST /api/registerEcomm

Webhook bancario:

- POST /api/v1/bfc/p2r/registro

Usuarios:

- POST /api/create-user
- POST /api/user/{id}
- POST /api/users/{id}/toggle-status

Catalogo ecommerce:

- GET /api/products
- GET /api/get-products
- GET /api/categories
- GET /api/products/{id}
- GET /api/products/all
- GET /api/getProduct/{id}
- GET /api/payment-methods/ecomm

Categorias:

- POST /api/create-category
- POST /api/categories/{category}
- POST /api/categories/{id}/toggle-status

Productos:

- POST /api/products/{id}
- DELETE /api/products/{id}
- POST /api/products/{product}/generate-codes
- POST /api/create-product
- POST /api/products/import-catalog
- GET /api/products/import-template
- POST /api/addImage/{productId}
- DELETE /api/product/remove-image/{imageId}
- GET /api/products/report

Variantes:

- POST /api/variants/store
- PUT /api/variants/{productVariant}
- DELETE /api/variants/{productVariant}
- POST /api/variants/{productVariant}/toggle-status
- POST /api/variants/{productVariant}/reassign
- PUT /api/variants/{productVariant}/barcode
- POST /api/variants/{productVariant}/generate-codes

Metodos de pago y moneda:

- POST /api/payment-methods/create
- POST /api/payment-methods/{id}/edit
- POST /api/payment-methods/{id}/toggleStatus
- POST /api/payment-methods/update-qr/{id}
- POST /api/payment-methods/remove-qr/{id}
- POST /api/currencies/create
- POST /api/currencies/{id}/update
- POST /api/currencies/{id}/currencyToggleStatus
- POST /api/dollar-rate/update
- POST /api/euro-rate/update
- POST /api/tenant-base-currency/update
- GET /api/dollarRate

Ventas y pagos:

- GET /api/payment-methods
- POST /api/sales/get-variants
- POST /api/create-sale
- POST /api/payment/order/{orderId}/create
- POST /api/payment/{id}/status/update
- POST /api/payment/{id}/update
- DELETE /api/payment/{id}
- POST /api/deliver/{id}/status/update
- POST /api/order/{id}/status/update
- GET /api/orders/{id}
- POST /api/sales-orders-report

Compras:

- POST /api/create-order
- POST /api/get-variants

Planes y tenants (API):

- POST /api/plans
- POST /api/plans/{id}
- DELETE /api/plans/{id}
- POST /api/tenants
- POST /api/tenants/{tenant}
- DELETE /api/tenants/{tenant}

## 7.2 API autenticada JWT (auth.jwt)

- GET /api/user
- POST /api/user/update-profile
- POST /api/user/change-password
- GET /api/user/orders
- GET /api/user/appointments
- GET /api/user/appointments/{appointment}/available-slots
- POST /api/user/appointments/{appointment}/action
- POST /api/create-sale/ecomm
- GET /api/notifications
- POST /api/notifications/{id}/read
- POST /api/push-subscriptions
- DELETE /api/push-subscriptions

## 8. Catalogo completo web (backoffice + publico)

## 8.1 Invitados (guest)

- GET /admin/login
- POST /admin/login
- POST /client/login
- GET /client/login/{provider}
- GET /client/login/{provider}/callback
- GET /login
- POST /login
- GET /register
- POST /register

## 8.2 Publico general

- GET /
- GET /landings
- GET /legal/terms-and-conditions.pdf
- GET /index
- GET /manifest.webmanifest
- GET /pwa-icon/{size}.png
- GET /storage/gdrive/{fileId}
- GET /publicOrder/{id}
- GET /publicOrder/{id}/pdfs/{type}
- GET /create-tenant-user
- GET /get-countries
- GET /get-states/{country}
- GET /get-cities/{state}
- POST /tenant-ai-image
- POST /tenant-ai-copy
- POST /tenant-ai-setup
- GET /csrf-token

## 8.3 Backoffice autenticado (auth + restricciones de acceso)

Infraestructura y soporte:

- GET /settings/google-drive/oauth
- GET /settings/google-drive/connect
- GET /settings/google-drive/callback
- GET /dashboard
- POST /logout
- GET /notifications
- GET /notifications/feed
- POST /notifications/{id}/read
- POST /push-subscriptions
- DELETE /push-subscriptions
- GET /help-preferences
- POST /help-preferences/global
- POST /help-preferences/route
- GET /profile

Catalogo:

- GET /categories
- GET /products
- POST /create-product
- POST /products/{id}/update
- POST /products/import-catalog
- POST /products/{product}/generate-codes
- GET /products/{category}
- GET /products/product/{id}
- GET /createProduct
- POST /products/{id}/taxes
- POST /variants/{productVariant}/generate-codes
- GET /variants/{productVariant}/qr-image

Ventas y clientes:

- GET /sales
- GET /customers
- POST /customers
- PUT /customers/{customer}
- POST /customers/{customer}/toggle-status
- GET /accounts-receivable
- GET /paid-pending-deliveries
- GET /sales-orders
- POST /sales-orders/pending-dispatch-guides/email
- GET /sales-orders/pending-delivery
- GET /sales/{id}
- POST /sales-orders/{order}/assign-delivery-user
- POST /sales-orders/{order}/electronic/emit
- POST /sales-orders/{order}/electronic/status
- POST /sales-orders/{order}/electronic/download
- POST /sales-orders/{order}/dispatch-guide/emit
- MATCH(GET|POST) /sales-orders/{order}/dispatch-guide/download
- POST /sales-orders/{order}/electronic/send-email
- POST /sales-orders/{order}/electronic/annul
- POST /sales-orders/{order}/electronic/metadata
- POST /sales-orders/{order}/document-mode
- POST /sales-orders/{order}/adjustment-notes
- GET /sales-adjustment-notes/{note}/download
- POST /sales-orders/{order}/retentions
- GET /sales-retentions/{retention}/certificate
- GET /sales-retentions/{retention}/download
- POST /sales-retentions/{retention}/sync-hka
- POST /sales-retentions/{retention}/status-hka
- GET /my-electronic-documents
- GET /electronic-documents
- POST /electronic-documents/{electronicDocument}/retry
- POST /sales/{id}/return
- POST /create-sale
- POST /sales/scan-code
- GET /sales-orders/{id}/pdf
- GET /sales-orders/{id}/pdfs/{type}

Comisiones:

- GET /seller-commissions
- GET /seller-commissions/progress
- GET /seller-commissions/progress/pdf
- PUT /seller-commissions/rate/{seller}
- POST /seller-commissions/{commission}/mark-paid

Citas:

- GET /appointments
- POST /appointments
- POST /appointments/{appointment}/workflow
- POST /appointments/services
- POST /appointments/schedules
- POST /appointments/packages
- GET /appointments/available-slots

Proyectos, nomina y cotizaciones:

- GET /projects-module
- GET /nomina
- POST /nomina/team-members
- POST /nomina/team-members/{teamMember}/status
- POST /nomina/payrolls
- GET /nomina/payrolls/{payroll}/comprobante
- GET /proyectos
- POST /proyectos
- GET /proyectos/{project}
- POST /proyectos/{project}/assets
- GET /proyectos/assets/{asset}/file
- POST /proyectos/{project}/phase
- POST /proyectos/{project}/complete
- POST /proyectos/{project}/visibility
- POST /proyectos/{project}/tasks
- POST /proyectos/tasks/{task}/status
- POST /proyectos/{project}/assignments
- GET /cotizaciones
- POST /cotizaciones
- PUT /cotizaciones/{quotation}
- GET /cotizaciones/{quotation}/pdf
- POST /cotizaciones/{quotation}/invalidate
- POST /cotizaciones/{quotation}/annul
- POST /cotizaciones/{quotation}/replace
- POST /cotizaciones/{quotation}/to-project
- POST /cotizaciones/{quotation}/to-sale
- POST /cotizaciones/{quotation}/to-inventory-entry

Reportes:

- GET /reports
- GET /reports/products/top-selling/pdf
- GET /reports/products/top-selling/excel
- GET /reports/inventory/entries/pdf
- GET /reports/inventory/entries/excel
- GET /reports/sales/management/pdf
- GET /reports/sales/management/excel
- GET /reports/inventory/total/pdf
- GET /reports/inventory/total/excel
- GET /reports/system/modules/pdf
- GET /reports/system/modules/excel
- GET /reports/customers/pdf
- GET /reports/customers/excel
- GET /reports/appointments/workflow/pdf
- GET /reports/appointments/workflow/excel
- GET /reports/accounts-receivable/pdf
- GET /reports/accounts-receivable/excel
- GET /reports/income/by-user/pdf
- GET /reports/income/by-user/excel
- GET /reports/sales/book/pdf
- GET /reports/sales/book/excel
- GET /reports/store-expenses/pdf
- GET /reports/store-expenses/excel

Compras, gastos y finanzas:

- GET /purchase
- GET /providers
- POST /providers
- PUT /providers/{provider}
- POST /providers/{provider}/toggle-status
- GET /store-expenses
- POST /store-expenses
- PUT /store-expenses/{expense}
- GET /accounts-payable
- POST /accounts-payable
- POST /accounts-payable/{accountPayable}/payments
- GET /withholdings/islr/concepts
- POST /withholdings/islr/concepts
- PUT /withholdings/islr/concepts/{concept}
- GET /withholdings/iva/export/txt
- GET /withholdings/islr/export/xml
- GET /withholdings/iva/{retention}/certificate
- GET /withholdings/iva/{retention}/download-hka-pdf
- POST /withholdings/iva/{retention}/sync-hka
- POST /withholdings/iva/{retention}/status-hka
- GET /withholdings/iva/{retention}/download-hka
- GET /withholdings/islr/{withholding}/certificate
- GET /withholdings/islr/{withholding}/download-hka-pdf
- POST /withholdings/islr/{withholding}/sync-hka
- POST /withholdings/islr/{withholding}/status-hka
- GET /withholdings/islr/{withholding}/download-hka
- GET /purchase-orders
- GET /order/{id}

Almacenes y materiales:

- GET /warehouses
- POST /warehouses
- PUT /warehouses/{warehouse}
- POST /warehouses/movements
- PUT /warehouses/movements/{movement}
- GET /materials
- POST /materials
- PUT /materials/{id}
- POST /materials/{id}/toggle-status
- POST /materials/{id}/generate-codes

Plataforma:

- GET /users
- GET /tenants
- GET /tenant-payments
- GET /create-tenant
- GET /tenant-store
- POST /tenant-update
- POST /tenant-store/users/{id}/update
- POST /tenant-store/users/{id}/toggle-status
- POST /tenant-import-setup-docx
- POST /tenant-store/plan-payment-request
- POST /tenants/{tenant}/plan-payments/{payment}/approve
- POST /tenants/{tenant}/plan-payments/{payment}/cutoff
- POST /tenants/{tenant}/plan-payments/{payment}/reject
- Resource /tenants (index/create/store/show/edit/update/destroy)
- GET /plans
- GET /taxes
- POST /taxes/create
- POST /taxes/update/{tax}
- POST /taxes/toggle/{tax}
- GET /logs
- GET /documentation
- GET /documentation/download/{document}

## 8.4 Storefront publico por tenant slug

- GET /{tenant:slug}
- GET /{tenant:slug}/categorias
- GET /{tenant:slug}/payment-methods
- GET /{tenant:slug}/appointments/public-availability
- POST /{tenant:slug}/checkout/pro
- POST /{tenant:slug}/scan-code
- GET /{tenant:slug}/{product}
- POST /tenants-public

## 9. Broadcast y realtime

Canales definidos:

- App.Models.User.{id}: solo el propio usuario.
- tenant.delivery-ops.{tenantId}: solo usuarios del tenant con store role owner/admin/seller/warehouse/delivery.

Uso:

- feed de notificaciones
- operacion delivery y tableros en tiempo real

## 10. Consola

Comandos por rutas de consola:

- inspire

Nota: el proyecto ademas posee comandos artisan propios en app/Console/Commands que se documentan en manual operativo interno.

## 11. Flujo operativo integral (resumen)

1. Onboarding tenant (alta + configuracion + usuarios + plan).  
2. Configuracion comercial/fiscal (metodos de pago, moneda, impuestos).  
3. Carga de catalogo (productos, variantes, imagenes, codigos).  
4. Operacion de venta (pedido, pago, despacho, documentos).  
5. Operacion de servicios (agenda, disponibilidad, workflow de citas).  
6. Operacion de abastecimiento (compras, proveedores, cuentas por pagar).  
7. Operacion de inventario (almacenes, movimientos, materiales).  
8. Operacion de proyectos (nomina, proyectos, cotizaciones y conversiones).  
9. Control de gestion (reportes PDF/Excel, notificaciones, logs).  

## 12. Consideraciones tecnicas criticas

- Imagenes: politica operativa del proyecto orientada a Google Drive.
- JWT: endpoints de cliente ecommerce dependen de auth.jwt.
- Backoffice: toda ruta interna pasa por auth + controles de plan/tenant/rol.
- Fiscal/e-invoicing: endpoints de emision y retenciones deben validarse por tenant y configuracion activa.
- Roles sinonimos: seller~vendor~vendedor y warehouse~almacen~almacenista.

## 13. Trazabilidad

Documento alineado a rutas activas al 2026-07-13. Si cambia routes/web.php o routes/api.php, este documento debe versionarse nuevamente.
