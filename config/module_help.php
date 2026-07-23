<?php

return [
    'enabled' => env('MODULE_HELP_ENABLED', true),

    'audience' => [
        'allow_guests' => env('MODULE_HELP_ALLOW_GUESTS', true),
        'role_allow_list' => env('MODULE_HELP_ROLE_ALLOW_LIST', ''),
        'role_block_list' => env('MODULE_HELP_ROLE_BLOCK_LIST', ''),
        'tenant_allow_list' => env('MODULE_HELP_TENANT_ALLOW_LIST', ''),
        'tenant_block_list' => env('MODULE_HELP_TENANT_BLOCK_LIST', ''),
    ],

    'fallback' => [
        'title' => 'Ayuda del modulo',
        'intro' => 'En esta pantalla puedes revisar informacion y ejecutar acciones segun tus permisos.',
        'wizard' => [
            [
                'title' => 'Paso 1: Revisa la pantalla',
                'description' => 'Identifica los bloques principales para entender el flujo del modulo.',
                'selector' => 'main',
                'action' => 'Ubica tablas, formularios y botones disponibles.',
            ],
            [
                'title' => 'Paso 2: Completa el formulario',
                'description' => 'Llena los campos obligatorios antes de guardar.',
                'selector' => 'form',
                'action' => 'Verifica que no existan datos vacios.',
            ],
            [
                'title' => 'Paso 3: Ejecuta la accion',
                'description' => 'Confirma la operacion usando el boton principal.',
                'selector' => 'button[type="submit"]',
                'action' => 'Haz clic en Guardar/Crear/Actualizar segun el modulo.',
            ],
        ],
        'tour' => [
            [
                'title' => 'Vista general',
                'description' => 'Esta es el area de trabajo del modulo actual.',
                'selector' => 'main',
            ],
            [
                'title' => 'Formulario principal',
                'description' => 'Desde aqui registras o actualizas informacion.',
                'selector' => 'form',
            ],
            [
                'title' => 'Accion principal',
                'description' => 'Este boton ejecuta la accion final del modulo.',
                'selector' => 'button[type="submit"]',
            ],
        ],
        'sections' => [
            [
                'heading' => 'Que puedes hacer aqui',
                'items' => [
                    'Consultar informacion general del modulo.',
                    'Usar los botones de crear, editar o ver detalle cuando esten disponibles.',
                ],
            ],
            [
                'heading' => 'Tip rapido',
                'items' => [
                    'Si no ves una accion, valida primero tu rol o permisos del usuario.',
                ],
            ],
        ],
    ],
    'routes' => [
        'dashboard' => [
            'title' => 'Ayuda: Dashboard',
            'intro' => 'Este panel resume el estado general del sistema con metricas y accesos directos.',
            'sections' => [
                [
                    'heading' => 'Que mirar primero',
                    'items' => [
                        'Revisa los indicadores principales para detectar ventas, compras o alertas.',
                        'Usa las tarjetas para entrar directo al modulo que necesites.',
                    ],
                ],
            ],
        ],
        'notifications.index' => [
            'title' => 'Ayuda: Notificaciones',
            'intro' => 'Aqui ves avisos recientes del sistema y su estado de lectura.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Abre una notificacion para revisar el detalle.',
                        'Marca como leida para limpiar el listado pendiente.',
                    ],
                ],
            ],
        ],
        'categories.index' => [
            'title' => 'Ayuda: Categorias',
            'intro' => 'Administra las categorias para organizar el catalogo de productos.',
            'sections' => [
                [
                    'heading' => 'Botones importantes',
                    'items' => [
                        'Crear categoria: agrega una nueva categoria para productos.',
                        'Editar/estado: actualiza nombre o activa/desactiva categorias.',
                    ],
                ],
            ],
        ],
        'products.index' => [
            'title' => 'Ayuda: Productos',
            'intro' => 'Desde aqui gestionas el inventario principal de productos.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Filtra categorias',
                    'description' => 'Usa las categorias para acotar el listado de productos.',
                    'selector' => '#categoriesContainer',
                    'action' => 'Selecciona una categoria o deja "Todos" para vista completa.',
                ],
                [
                    'title' => 'Paso 2: Busca producto',
                    'description' => 'Localiza productos por nombre.',
                    'selector' => '#searchProduct',
                    'action' => 'Escribe una palabra clave para filtrar resultados.',
                ],
                [
                    'title' => 'Paso 3: Crea nuevo producto',
                    'description' => 'Accede al flujo de alta de productos.',
                    'selector' => 'a[href="/createProduct"]',
                    'action' => 'Haz clic en + Agregar Producto para registrar uno nuevo.',
                ],
                [
                    'title' => 'Paso 4: Importa catalogo',
                    'description' => 'Carga productos masivamente desde archivo o Google Sheets.',
                    'selector' => '#importCatalogForm',
                    'action' => 'Abre "Importar Catalogo", selecciona archivo/URL y confirma.',
                ],
                [
                    'title' => 'Paso 5: Edita detalle',
                    'description' => 'Ingresa al detalle de un producto para variantes y codigos.',
                    'selector' => '.product-item a[href*="/products/product/"]',
                    'action' => 'Abre cualquier producto para editar sus datos avanzados.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Categorias',
                    'description' => 'Aqui eliges la categoria para filtrar el catalogo.',
                    'selector' => '#categoriesContainer',
                ],
                [
                    'title' => 'Buscador de productos',
                    'description' => 'Escribe aqui para encontrar productos rapidamente.',
                    'selector' => '#searchProduct',
                ],
                [
                    'title' => 'Agregar producto',
                    'description' => 'Enlace para crear un nuevo producto.',
                    'selector' => 'a[href="/createProduct"]',
                ],
                [
                    'title' => 'Importacion masiva',
                    'description' => 'Abre este modal para importar catalogo.',
                    'selector' => 'a[data-bs-target="#importCatalogModal"]',
                ],
                [
                    'title' => 'Listado de productos',
                    'description' => 'Cada tarjeta te lleva al detalle editable del producto.',
                    'selector' => '.product-item',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Flujo recomendado',
                    'items' => [
                        '1) Crear producto, 2) configurar variantes, 3) revisar impuestos y precios.',
                        'Usa el acceso al detalle para administrar imagenes, stock y codigos.',
                    ],
                ],
            ],
        ],
        'products.byCategory' => [
            'title' => 'Ayuda: Productos por categoria',
            'intro' => 'Muestra solo los productos de una categoria especifica.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Filtra y revisa productos de la categoria seleccionada.',
                        'Ingresa al detalle de cada producto para cambios puntuales.',
                    ],
                ],
            ],
        ],
        'productItem' => [
            'title' => 'Ayuda: Detalle de producto',
            'intro' => 'Aqui administras variantes, codigos, imagenes y datos especificos del producto.',
            'sections' => [
                [
                    'heading' => 'Que hace cada accion',
                    'items' => [
                        'Agregar imagen: publica fotos para mejorar la vista comercial.',
                        'Generar codigo: crea codigos para escaneo o impresion.',
                    ],
                ],
            ],
        ],
        'createProductItem' => [
            'title' => 'Ayuda: Crear producto',
            'intro' => 'Formulario para registrar un nuevo producto en el sistema.',
            'sections' => [
                [
                    'heading' => 'Antes de guardar',
                    'items' => [
                        'Completa nombre, categoria y datos obligatorios.',
                        'Valida precio y unidad para evitar errores en ventas.',
                    ],
                ],
            ],
        ],
        'users' => [
            'title' => 'Ayuda: Usuarios',
            'intro' => 'Administra cuentas internas y sus permisos de acceso.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Buscar usuario',
                    'description' => 'Filtra usuarios existentes para editar o validar estado.',
                    'selector' => '#searchUser',
                    'action' => 'Escribe nombre o correo para encontrar el usuario.',
                ],
                [
                    'title' => 'Paso 2: Crear usuario',
                    'description' => 'Abre el modal de creacion de usuario.',
                    'selector' => '[data-bs-target="#createCategoryModal"]',
                    'action' => 'Haz clic en + Crear Usuario y completa el formulario.',
                ],
                [
                    'title' => 'Paso 3: Editar usuario',
                    'description' => 'Actualiza datos y rol de un usuario existente.',
                    'selector' => '.btn-edit-user',
                    'action' => 'Pulsa Editar para abrir el modal y guardar cambios.',
                ],
                [
                    'title' => 'Paso 4: Activar/Inactivar',
                    'description' => 'Controla el estado operativo de cada cuenta.',
                    'selector' => '.toggle-status-btn',
                    'action' => 'Usa Activar/Inactivar segun necesidad.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Buscador',
                    'description' => 'Filtra usuarios por nombre o correo.',
                    'selector' => '#searchUser',
                ],
                [
                    'title' => 'Boton crear',
                    'description' => 'Abre el formulario para nuevo usuario.',
                    'selector' => '[data-bs-target="#createCategoryModal"]',
                ],
                [
                    'title' => 'Tabla de usuarios',
                    'description' => 'Aqui revisas rol, estado y acciones disponibles.',
                    'selector' => '#userTableBody',
                ],
                [
                    'title' => 'Editar',
                    'description' => 'Abre modal para actualizar datos del usuario.',
                    'selector' => '.btn-edit-user',
                ],
                [
                    'title' => 'Estado',
                    'description' => 'Activa o inactiva cuentas rapidamente.',
                    'selector' => '.toggle-status-btn',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones frecuentes',
                    'items' => [
                        'Crear usuario: registra personal nuevo.',
                        'Editar rol: cambia permisos segun funciones del colaborador.',
                    ],
                ],
            ],
        ],
        'paymentMethods.index' => [
            'title' => 'Ayuda: Metodos de pago',
            'intro' => 'Configura cuentas y formas de cobro para ventas.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Revisa tasas actuales',
                    'description' => 'Verifica los valores actuales de USD y EUR antes de operar.',
                    'selector' => '#currentDollarRate, #currentEuroRate',
                    'action' => 'Confirma las tasas visibles para conversiones de moneda.',
                ],
                [
                    'title' => 'Paso 2: Historial y actualizacion',
                    'description' => 'Consulta el historico y actualiza tasas cuando aplique.',
                    'selector' => '[data-bs-target="#rateHistoryModal"], [data-bs-target="#updateDollarRateModal"], [data-bs-target="#updateEuroRateModal"]',
                    'action' => 'Abre historial o actualiza la tasa del dia segun mercado.',
                ],
                [
                    'title' => 'Paso 3: Configura monedas',
                    'description' => 'Revisa las monedas habilitadas y define la moneda madre.',
                    'selector' => '#paymentCurrenciesCard, #paymentCurrenciesList, #updateBaseCurrencyForm',
                    'action' => 'Selecciona la moneda madre y guarda el cambio si es necesario.',
                ],
                [
                    'title' => 'Paso 4: Crear metodo de pago',
                    'description' => 'Registra cuentas y datos bancarios por moneda.',
                    'selector' => '#createPaymentMethodTrigger, #createPaymentMethodModal',
                    'action' => 'Abre Nuevo Metodo de Pago y completa nombre, moneda, beneficiario y QR.',
                ],
                [
                    'title' => 'Paso 5: Editar o activar',
                    'description' => 'Administra disponibilidad de cada metodo registrado.',
                    'selector' => '#paymentMethodsList .btn-edit-method, #paymentMethodsList .toggle-status-btn',
                    'action' => 'Edita datos o usa Inactivar/Activar segun corresponda.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Tasas actuales',
                    'description' => 'Valores usados para conversiones VES/USD y VES/EUR.',
                    'selector' => '#currentDollarRate, #currentEuroRate',
                ],
                [
                    'title' => 'Historial y actualizacion',
                    'description' => 'Desde aqui consultas historico y actualizas tasas.',
                    'selector' => '[data-bs-target="#rateHistoryModal"], [data-bs-target="#updateDollarRateModal"], [data-bs-target="#updateEuroRateModal"]',
                ],
                [
                    'title' => 'Monedas',
                    'description' => 'Listado de monedas habilitadas y selector de moneda madre.',
                    'selector' => '#paymentCurrenciesCard, #paymentCurrenciesList, #updateBaseCurrencyForm',
                ],
                [
                    'title' => 'Nuevo metodo',
                    'description' => 'Abre formulario para crear metodo de pago.',
                    'selector' => '#createPaymentMethodTrigger',
                ],
                [
                    'title' => 'Gestion de metodos',
                    'description' => 'Edita datos y activa/inactiva metodos existentes.',
                    'selector' => '#paymentMethodsList .btn-edit-method, #paymentMethodsList .toggle-status-btn',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Uso',
                    'items' => [
                        'Crea metodos de pago para caja y tienda publica.',
                        'Actualiza datos bancarios o desactiva metodos no vigentes.',
                    ],
                ],
            ],
        ],
        'sales' => [
            'title' => 'Ayuda: Ventas',
            'intro' => 'Modulo para registrar ventas y construir el pedido del cliente.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Flujo por etapas',
                    'description' => 'La venta avanza en 3 pantallas: Seleccion, Pago y Confirmacion.',
                    'selector' => '#saleFlowStepper, [data-sale-step="1"], [data-sale-step="2"], [data-sale-step="3"]',
                    'action' => 'Empieza en Seleccion y avanza con Siguiente hasta Confirmacion.',
                ],
                [
                    'title' => 'Paso 2: Buscar/escanear',
                    'description' => 'Puedes buscar por texto o agregar por codigo de barras/QR.',
                    'selector' => '#scanCodeInput',
                    'action' => 'Escanea o pega un codigo y presiona Agregar.',
                ],
                [
                    'title' => 'Paso 3: Seleccionar variantes',
                    'description' => 'Marca variantes para agregarlas al carrito de venta.',
                    'selector' => '#itemSelector',
                    'action' => 'Selecciona variantes con stock para construir el pedido.',
                ],
                [
                    'title' => 'Paso 4: Ir a pagina de pago',
                    'description' => 'Cuando tengas productos seleccionados, avanza a la segunda pantalla.',
                    'selector' => '#toStep2, [data-sale-step="2"]',
                    'action' => 'Haz clic en Siguiente para abrir el Paso 2: Metodos de pago.',
                ],
                [
                    'title' => 'Paso 5: Metodo de pago y confirmacion',
                    'description' => 'En la pagina 2 registras pagos y luego pasas a la pagina 3 para confirmar.',
                    'selector' => '#step2:not(.d-none) #paymentMethods, #toStep3, [data-sale-step="3"], #step3:not(.d-none) #summaryContainer, #step3:not(.d-none) #confirmPurchase',
                    'action' => 'Completa pagos, pulsa Siguiente y finalmente Confirmar en el Paso 3.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Flujo de Venta',
                    'description' => 'Este stepper muestra las 3 paginas del proceso: Seleccion, Pago y Confirmacion.',
                    'selector' => '#saleFlowStepper, [data-sale-step="1"], [data-sale-step="2"], [data-sale-step="3"]',
                ],
                [
                    'title' => 'Buscador de productos',
                    'description' => 'Encuentra productos por nombre.',
                    'selector' => '#searchInput',
                ],
                [
                    'title' => 'Escaner/codigo',
                    'description' => 'Agrega productos por QR o codigo de barras.',
                    'selector' => '#scanCodeInput',
                ],
                [
                    'title' => 'Selector de items',
                    'description' => 'Lista de productos y variantes disponibles.',
                    'selector' => '#itemSelector',
                ],
                [
                    'title' => 'Paso 2: Metodos de pago',
                    'description' => 'Tras seleccionar items, avanza a la segunda pagina para registrar pagos.',
                    'selector' => '#toStep2, [data-sale-step="2"], #step2:not(.d-none) #paymentMethods',
                ],
                [
                    'title' => 'Paso 3: Confirmacion',
                    'description' => 'Desde la segunda pagina avanza a Confirmacion para revisar resumen y finalizar.',
                    'selector' => '#toStep3, [data-sale-step="3"], #step3:not(.d-none) #summaryContainer, #step3:not(.d-none) #confirmPurchase',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Flujo',
                    'items' => [
                        'Busca productos o escanea codigos para agregarlos al pedido.',
                        'Confirma metodo de pago y finaliza para emitir la orden.',
                    ],
                ],
            ],
        ],
        'sales.orders' => [
            'title' => 'Ayuda: Ordenes de venta',
            'intro' => 'Lista historica de ventas registradas en el sistema.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Revisa tabla de ordenes',
                    'description' => 'Consulta estado, fecha y cantidad de productos por orden.',
                    'selector' => 'table.table',
                    'action' => 'Ubica la orden por numero o fecha para seguimiento.',
                ],
                [
                    'title' => 'Paso 2: Ver detalle de orden',
                    'description' => 'Entra al detalle para items, pagos y devoluciones.',
                    'selector' => 'a[href*="/sales/"]',
                    'action' => 'Haz clic en Ver Detalles de la orden objetivo.',
                ],
                [
                    'title' => 'Paso 3: Generar reporte',
                    'description' => 'Exporta reportes por periodicidad.',
                    'selector' => '#reportForm',
                    'action' => 'Selecciona semanal/mensual/trimestral/anual y genera el reporte.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Cabecera de ventas',
                    'description' => 'Desde aqui puedes abrir reporte o crear nueva venta.',
                    'selector' => '.bg-gradient-dark',
                ],
                [
                    'title' => 'Tabla de ordenes',
                    'description' => 'Listado con estado y datos principales de cada venta.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Accion detalle',
                    'description' => 'Abre informacion completa de la orden seleccionada.',
                    'selector' => 'a[href*="/sales/"]',
                ],
                [
                    'title' => 'Reporte de ventas',
                    'description' => 'Configura rango de tiempo y descarga reporte.',
                    'selector' => '#reportModal',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Filtra por fecha o estado para ubicar una orden.',
                        'Abre el detalle para ver productos, pagos y devoluciones.',
                    ],
                ],
            ],
        ],
        'sales.showByOrder' => [
            'title' => 'Ayuda: Detalle de orden de venta',
            'intro' => 'Vista detallada de una orden de venta especifica.',
            'sections' => [
                [
                    'heading' => 'Desde aqui puedes',
                    'items' => [
                        'Revisar items vendidos y metodos de pago usados.',
                        'Procesar devoluciones cuando aplique.',
                    ],
                ],
            ],
        ],
        'purchase' => [
            'title' => 'Ayuda: Compras',
            'intro' => 'Registra ingresos de inventario y costos de abastecimiento.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Seleccion de variantes',
                    'description' => 'Marca variantes e indica cantidad/costo.',
                    'selector' => '#itemSelector',
                    'action' => 'Activa los checkboxes y completa cantidad y costo USD.',
                ],
                [
                    'title' => 'Paso 2: Ir a proveedor y fecha',
                    'description' => 'Avanza al segundo bloque con datos operativos.',
                    'selector' => '#toStep2',
                    'action' => 'Presiona Siguiente cuando haya items validos.',
                ],
                [
                    'title' => 'Paso 3: Configura proveedor/almacen',
                    'description' => 'Define almacen destino, proveedor y fecha de compra.',
                    'selector' => '#warehouseId',
                    'action' => 'Completa almacen, proveedor y fecha antes de continuar.',
                ],
                [
                    'title' => 'Paso 4: Confirmacion',
                    'description' => 'Revisa el resumen final de la entrada.',
                    'selector' => '#finalSummaryText',
                    'action' => 'Valida cantidades y montos antes de registrar.',
                ],
                [
                    'title' => 'Paso 5: Registrar entrada',
                    'description' => 'Ejecuta el registro de inventario.',
                    'selector' => '#createOrder',
                    'action' => 'Haz clic en Registrar entrada para guardar la compra.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Indicador de pasos',
                    'description' => 'Muestra en que fase del flujo de compra estas.',
                    'selector' => '#purchase-steps-indicator',
                ],
                [
                    'title' => 'Buscador',
                    'description' => 'Filtra productos para armar la compra.',
                    'selector' => '#searchInput',
                ],
                [
                    'title' => 'Selector de variantes',
                    'description' => 'Aqui eliges variantes y defines cantidades/costos.',
                    'selector' => '#itemSelector',
                ],
                [
                    'title' => 'Almacen destino',
                    'description' => 'Selecciona donde entrara el inventario.',
                    'selector' => '#warehouseId',
                ],
                [
                    'title' => 'Registro final',
                    'description' => 'Boton final para registrar la entrada.',
                    'selector' => '#createOrder',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Flujo',
                    'items' => [
                        'Selecciona proveedor y productos que ingresan.',
                        'Confirma cantidades y costo para crear la entrada.',
                    ],
                ],
            ],
        ],
        'purchase.orders' => [
            'title' => 'Ayuda: Ordenes de compra',
            'intro' => 'Consulta el historial de entradas y compras realizadas.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Revisa ordenes',
                    'description' => 'Analiza proveedor, almacen y total de cada compra.',
                    'selector' => 'table.table',
                    'action' => 'Ubica orden por fecha o numero para validacion.',
                ],
                [
                    'title' => 'Paso 2: Ver detalle',
                    'description' => 'Ingresa para revisar variantes y costos cargados.',
                    'selector' => 'a[href*="/order/"]',
                    'action' => 'Haz clic en Ver Detalles para auditar la entrada.',
                ],
                [
                    'title' => 'Paso 3: Registrar nueva compra',
                    'description' => 'Salta al flujo de nueva entrada de inventario.',
                    'selector' => 'a[href="/purchase"]',
                    'action' => 'Usa + Generar Compra para registrar otra orden.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Cabecera de compras',
                    'description' => 'Atajos para reporte y nueva compra.',
                    'selector' => '.bg-gradient-dark',
                ],
                [
                    'title' => 'Tabla de ordenes',
                    'description' => 'Consulta vista previa, proveedor, almacen y totales.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Detalle de orden',
                    'description' => 'Abre la orden para revisar informacion completa.',
                    'selector' => 'a[href*="/order/"]',
                ],
                [
                    'title' => 'Nueva compra',
                    'description' => 'Abre el flujo de registro de entradas.',
                    'selector' => 'a[href="/purchase"]',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Filtra por rango de fechas y estado de orden.',
                        'Abre detalle para revisar productos y totales.',
                    ],
                ],
            ],
        ],
        'showByOrder' => [
            'title' => 'Ayuda: Detalle de compra',
            'intro' => 'Muestra la informacion completa de una orden de compra.',
            'sections' => [
                [
                    'heading' => 'Que revisar',
                    'items' => [
                        'Productos ingresados, cantidades y costos.',
                        'Estado de la orden y trazabilidad de la operacion.',
                    ],
                ],
            ],
        ],
        'warehouses.index' => [
            'title' => 'Ayuda: Almacenes',
            'intro' => 'Gestiona los almacenes donde se distribuye el stock.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Crear almacen',
                    'description' => 'Ingresa nombre y registra un nuevo almacen.',
                    'selector' => 'form[action*="warehouses"]',
                    'action' => 'Escribe el nombre y pulsa Crear almacen.',
                ],
                [
                    'title' => 'Paso 2: Revisar listado',
                    'description' => 'Verifica tipo y estado de cada almacen creado.',
                    'selector' => 'table.table.align-items-center',
                    'action' => 'Confirma que el almacen aparezca como Activo.',
                ],
                [
                    'title' => 'Paso 3: Validar stock cruzado',
                    'description' => 'Consulta existencias por variante en todos los almacenes.',
                    'selector' => 'table.table.table-bordered',
                    'action' => 'Usa esta tabla para control de distribucion de inventario.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Formulario de alta',
                    'description' => 'Formulario para registrar un nuevo almacen.',
                    'selector' => 'form[action*="warehouses"]',
                ],
                [
                    'title' => 'Nombre del almacen',
                    'description' => 'Campo donde defines el nombre del almacen.',
                    'selector' => 'input[name="name"]',
                ],
                [
                    'title' => 'Boton crear',
                    'description' => 'Guarda el nuevo almacen en el sistema.',
                    'selector' => 'button[type="submit"]',
                ],
                [
                    'title' => 'Tabla de almacenes',
                    'description' => 'Lista almacenes con tipo y estado.',
                    'selector' => 'table.table.align-items-center',
                ],
                [
                    'title' => 'Tabla de existencias',
                    'description' => 'Visualiza stock de variantes entre almacenes.',
                    'selector' => 'table.table.table-bordered',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Crear almacen para separar inventario por sede o zona.',
                        'Editar datos del almacen para mantener ubicaciones actualizadas.',
                    ],
                ],
            ],
        ],
        'materials.index' => [
            'title' => 'Ayuda: Materiales y paquetes',
            'intro' => 'Administra listas de materiales y paquetes de uso interno.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Crea encabezado del paquete',
                    'description' => 'Define nombre, descripcion y descuento del paquete.',
                    'selector' => '#materialPackageForm',
                    'action' => 'Completa datos base y, si aplica, precio fijo.',
                ],
                [
                    'title' => 'Paso 2: Agrega materiales',
                    'description' => 'Incluye variantes y cantidad para cada item del combo.',
                    'selector' => '#materialsRows',
                    'action' => 'Usa + Agregar material para sumar filas necesarias.',
                ],
                [
                    'title' => 'Paso 3: Guarda paquete',
                    'description' => 'Registra el paquete en el sistema.',
                    'selector' => '#materialPackageForm button[type="submit"]',
                    'action' => 'Presiona Guardar paquete para finalizar.',
                ],
                [
                    'title' => 'Paso 4: Gestiona estado',
                    'description' => 'Activa o desactiva paquetes existentes.',
                    'selector' => 'form[action*="toggle-status"]',
                    'action' => 'Usa Activar/Desactivar segun disponibilidad comercial.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Formulario de paquete',
                    'description' => 'Area principal para crear combos o listas de materiales.',
                    'selector' => '#materialPackageForm',
                ],
                [
                    'title' => 'Agregar fila material',
                    'description' => 'Boton para añadir mas variantes al paquete.',
                    'selector' => '#addMaterialRow',
                ],
                [
                    'title' => 'Listado de filas',
                    'description' => 'Aqui configuras variante y cantidad por item.',
                    'selector' => '#materialsRows',
                ],
                [
                    'title' => 'Tabla de paquetes',
                    'description' => 'Muestra todos los paquetes creados y sus materiales.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Estado de paquete',
                    'description' => 'Accion para activar o desactivar cada paquete.',
                    'selector' => 'form[action*="toggle-status"]',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Crear paquete: arma grupos de materiales reutilizables.',
                        'Generar codigos: prepara identificacion rapida por escaneo.',
                    ],
                ],
            ],
        ],
        'tenant.index' => [
            'title' => 'Ayuda: Tenants',
            'intro' => 'Panel de administracion de tenants registrados en la plataforma.',
            'sections' => [
                [
                    'heading' => 'Desde esta tabla puedes',
                    'items' => [
                        'Ver estado y datos clave de cada tenant.',
                        'Entrar al detalle o editar configuraciones del tenant.',
                    ],
                ],
            ],
        ],
        'createTenant' => [
            'title' => 'Ayuda: Crear tenant',
            'intro' => 'Formulario para crear una nueva tienda con su configuracion inicial.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Datos base',
                    'description' => 'Empieza con nombre, slug y correo de contacto para definir la identidad del tenant.',
                    'selector' => '#name',
                    'action' => 'Completa nombre comercial y revisa que el slug sea unico.',
                ],
                [
                    'title' => 'Paso 2: Branding',
                    'description' => 'Configura logo y colores para personalizar la tienda.',
                    'selector' => '#logo',
                    'action' => 'Sube logo y ajusta color primario, secundario y acento.',
                ],
                [
                    'title' => 'Paso 3: Plan',
                    'description' => 'Selecciona el plan que define el alcance funcional del tenant.',
                    'selector' => '#plan_id',
                    'action' => 'Escoge el plan mas adecuado para el cliente.',
                ],
                [
                    'title' => 'Paso 4: Usuarios iniciales',
                    'description' => 'Carga credenciales de owner, admin y vendor en los acordeones.',
                    'selector' => '#accordionRoles',
                    'action' => 'Usa los botones de ojo y copiar para validar contrasenas.',
                ],
                [
                    'title' => 'Paso 5: Crear tenant',
                    'description' => 'Cuando todo este validado, ejecuta la creacion.',
                    'selector' => 'button[type="submit"]',
                    'action' => 'Haz clic en Crear Tenant para guardar y vincular usuarios.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Nombre del tenant',
                    'description' => 'Define el nombre visible de la nueva tienda.',
                    'selector' => '#name',
                ],
                [
                    'title' => 'Slug / URL',
                    'description' => 'Esta URL se usa en la landing del tenant.',
                    'selector' => '#slug',
                ],
                [
                    'title' => 'Correo de contacto',
                    'description' => 'Correo principal para notificaciones y comunicacion.',
                    'selector' => '#email',
                ],
                [
                    'title' => 'Logo y colores',
                    'description' => 'Sube la imagen y define la paleta visual de la tienda.',
                    'selector' => '#logo',
                ],
                [
                    'title' => 'Seleccion de plan',
                    'description' => 'Aqui defines el plan comercial del tenant.',
                    'selector' => '#plan_id',
                ],
                [
                    'title' => 'Usuarios iniciales',
                    'description' => 'Expande cada acordeon para cargar cuentas iniciales.',
                    'selector' => '#accordionRoles',
                ],
                [
                    'title' => 'Boton final',
                    'description' => 'Ejecuta este boton para crear el tenant y guardar todo.',
                    'selector' => 'button[type="submit"]',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Campos clave',
                    'items' => [
                        'Nombre, slug y correo definen la identidad base del tenant.',
                        'Logo y colores personalizan la apariencia de la tienda.',
                    ],
                ],
                [
                    'heading' => 'Usuarios iniciales',
                    'items' => [
                        'Puedes crear owner, admin y vendor desde los acordeones.',
                        'Boton de ojo: muestra/oculta la contrasena; boton de copiar: copia al portapapeles.',
                    ],
                ],
                [
                    'heading' => 'Accion final',
                    'items' => [
                        'Crear Tenant: guarda la tienda y vincula usuarios de forma automatica.',
                    ],
                ],
            ],
        ],
        'tenant.store' => [
            'title' => 'Ayuda: Configuracion de tienda',
            'intro' => 'Aqui actualizas datos publicos y ajustes de la tienda tenant.',
            'sections' => [
                [
                    'heading' => 'Que puedes actualizar',
                    'items' => [
                        'Informacion de empresa, branding y datos de contacto.',
                        'Parametros comerciales que afectan la experiencia publica.',
                    ],
                ],
            ],
        ],
        'plans.index' => [
            'title' => 'Ayuda: Planes',
            'intro' => 'Administra los planes comerciales disponibles para tenants.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Crear o editar planes con precio y beneficios.',
                        'Activar o ajustar condiciones segun estrategia comercial.',
                    ],
                ],
            ],
        ],
        'taxes' => [
            'title' => 'Ayuda: Impuestos',
            'intro' => 'Configuracion de tasas e impuestos usados en ventas y compras.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Registrar una nueva tasa de impuesto.',
                        'Actualizar valores o cambiar estado segun normativa.',
                    ],
                ],
            ],
        ],
        'logs.index' => [
            'title' => 'Ayuda: Logs',
            'intro' => 'Registro de eventos y actividades relevantes del sistema.',
            'sections' => [
                [
                    'heading' => 'Uso recomendado',
                    'items' => [
                        'Filtra por fecha o tipo de evento para auditar cambios.',
                        'Usa esta vista para soporte tecnico y seguimiento.',
                    ],
                ],
            ],
        ],
        'createTenantUser' => [
            'title' => 'Ayuda: Registro de tenant publico',
            'intro' => 'Pantalla para registrar un tenant desde el flujo publico.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Completa los datos basicos para crear tu tienda.',
                        'Revisa la previsualizacion antes de enviar el registro.',
                    ],
                ],
            ],
        ],
        'profile' => [
            'title' => 'Ayuda: Perfil',
            'intro' => 'Aqui actualizas tus datos personales y preferencias de cuenta.',
            'tour' => [
                [
                    'title' => 'Formulario de perfil',
                    'description' => 'Actualiza nombre, correo y datos de contacto del usuario autenticado.',
                    'selector' => 'form',
                ],
                [
                    'title' => 'Guardar cambios',
                    'description' => 'Aplica los cambios del perfil para la sesion actual.',
                    'selector' => 'button[type="submit"]',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Sugerencia',
                    'items' => [
                        'Actualiza datos de contacto para mejorar notificaciones y trazabilidad.',
                    ],
                ],
            ],
        ],
        'customers.index' => [
            'title' => 'Ayuda: Clientes',
            'intro' => 'Gestiona la base de clientes para ventas, historial y seguimiento comercial.',
            'wizard' => [
                [
                    'title' => 'Paso 1: Buscar cliente',
                    'description' => 'Filtra por nombre, correo o telefono para ubicar registros rapidamente.',
                    'selector' => 'input[type="search"], #searchCustomer, #searchInput',
                    'action' => 'Escribe un criterio corto para reducir resultados.',
                ],
                [
                    'title' => 'Paso 2: Crear cliente',
                    'description' => 'Registra un nuevo cliente con datos de contacto y documento.',
                    'selector' => '[data-bs-target*="Customer"], [data-bs-target*="customer"], button[data-bs-target]',
                    'action' => 'Abre el modal de alta y completa los campos obligatorios.',
                ],
                [
                    'title' => 'Paso 3: Gestionar estado',
                    'description' => 'Activa o inactiva clientes segun politicas comerciales.',
                    'selector' => 'table.table',
                    'action' => 'Revisa acciones por fila para editar o cambiar estatus.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Buscador de clientes',
                    'description' => 'Ubica clientes por coincidencia de texto.',
                    'selector' => 'input[type="search"], #searchCustomer, #searchInput',
                ],
                [
                    'title' => 'Tabla principal',
                    'description' => 'Consulta datos de clientes y acciones disponibles.',
                    'selector' => 'table.table',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Uso',
                    'items' => [
                        'Mantener clientes depurados mejora la calidad de ventas y reportes.',
                    ],
                ],
            ],
        ],
        'accounts.receivable.index' => [
            'title' => 'Ayuda: Cuentas por cobrar',
            'intro' => 'Monitorea saldos pendientes, abonos y estado de cobranza por cliente.',
            'tour' => [
                [
                    'title' => 'Resumen de cartera',
                    'description' => 'Visualiza montos vencidos y pendientes por gestionar.',
                    'selector' => '.card, .bg-gradient-dark',
                ],
                [
                    'title' => 'Detalle de cuentas',
                    'description' => 'Tabla con cliente, documento, saldo y acciones de cobro.',
                    'selector' => 'table.table',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Buenas practicas',
                    'items' => [
                        'Prioriza cobranzas por antiguedad y riesgo de mora.',
                    ],
                ],
            ],
        ],
        'sales.paidPendingDeliveries.index' => [
            'title' => 'Ayuda: Deliveries pagados pendientes',
            'intro' => 'Lista ordenes cobradas que aun requieren preparacion o entrega final.',
            'tour' => [
                [
                    'title' => 'Tabla de pendientes',
                    'description' => 'Aqui controlas pedidos que faltan por despachar o entregar.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Acciones por orden',
                    'description' => 'Ingresa al detalle para asignacion y seguimiento de entrega.',
                    'selector' => 'a[href*="/sales/"]',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Operacion',
                    'items' => [
                        'Usa esta cola para reducir tiempos entre pago y entrega.',
                    ],
                ],
            ],
        ],
        'sales.orders.pendingDelivery' => [
            'title' => 'Ayuda: Ordenes pendientes por entregar',
            'intro' => 'Vista operativa para coordinar pedidos listos para delivery.',
            'tour' => [
                [
                    'title' => 'Lista operativa',
                    'description' => 'Ordenes disponibles para asignar y despachar.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Asignacion de reparto',
                    'description' => 'Usa acciones por orden para definir responsable de entrega.',
                    'selector' => 'form[action*="assign-delivery-user"], button[type="submit"]',
                ],
            ],
        ],
        'providers.index' => [
            'title' => 'Ayuda: Proveedores',
            'intro' => 'Administra proveedores para abastecimiento, pagos y trazabilidad de compras.',
            'tour' => [
                [
                    'title' => 'Alta de proveedor',
                    'description' => 'Registra datos fiscales, contacto y condiciones de compra.',
                    'selector' => 'form',
                ],
                [
                    'title' => 'Listado de proveedores',
                    'description' => 'Consulta estado, datos y opciones de edicion.',
                    'selector' => 'table.table',
                ],
            ],
        ],
        'store-expenses.index' => [
            'title' => 'Ayuda: Gastos de tienda',
            'intro' => 'Registra y controla egresos operativos para analisis financiero.',
            'tour' => [
                [
                    'title' => 'Registro de gasto',
                    'description' => 'Carga concepto, categoria, monto y fecha del egreso.',
                    'selector' => '#storeExpenseCreateTrigger, [data-bs-target="#createExpenseModal"], #createExpenseModal',
                ],
                [
                    'title' => 'Historico de gastos',
                    'description' => 'Revisa movimientos para control de caja y reportes.',
                    'selector' => '#storeExpensesTable, table.table, .table-responsive',
                ],
            ],
        ],
        'accounts.payable.index' => [
            'title' => 'Ayuda: Cuentas por pagar',
            'intro' => 'Gestiona deudas con proveedores y registra pagos parciales o totales.',
            'tour' => [
                [
                    'title' => 'Panel de deuda',
                    'description' => 'Identifica facturas pendientes y vencimientos de pago.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Registrar pago',
                    'description' => 'Usa acciones para cargar abonos y actualizar saldos.',
                    'selector' => 'form[action*="accounts-payable"], [data-bs-target*="payment"]',
                ],
            ],
        ],
        'withholdings.islr.concepts.index' => [
            'title' => 'Ayuda: Conceptos ISLR',
            'intro' => 'Configura conceptos de retencion ISLR para operaciones de compra.',
            'tour' => [
                [
                    'title' => 'Formulario de concepto',
                    'description' => 'Define codigo, descripcion y porcentaje de retencion.',
                    'selector' => 'form',
                ],
                [
                    'title' => 'Tabla de conceptos',
                    'description' => 'Consulta y ajusta conceptos existentes.',
                    'selector' => 'table.table',
                ],
            ],
        ],
        'appointments.index' => [
            'title' => 'Ayuda: Citas',
            'intro' => 'Gestiona agenda, estados de servicio y flujo de atencion al cliente.',
            'tour' => [
                [
                    'title' => 'Resumen de citas',
                    'description' => 'Consulta profesionales activos, servicios configurados y citas de la semana.',
                    'selector' => '.appointments-top-cards-row, #appointmentsWeekCountValue, #appointmentsWeekRangeNote',
                ],
                [
                    'title' => 'Filtros y navegacion',
                    'description' => 'Filtra por fecha/profesional y cambia entre vista de dia, semana o mes.',
                    'selector' => '#appointmentsFiltersToggleButton, #appointmentsFiltersCollapse, [data-calendar-view], #appointmentsWeekRangeTitle',
                ],
                [
                    'title' => 'Calendario de agenda',
                    'description' => 'Aqui ves los bloques de citas por hora y dia para gestionar la agenda.',
                    'selector' => '#appointmentsCalendarCard, #appointmentsCalendarScroll, .appointments-calendar-grid, .appointments-calendar-day-column',
                ],
            ],
        ],
        'appointments.customerControl.index' => [
            'title' => 'Ayuda: Control de clientes en citas',
            'intro' => 'Da seguimiento operativo y evidencias por cita atendida.',
            'tour' => [
                [
                    'title' => 'Listado de control',
                    'description' => 'Consulta estado de atencion y avance por cliente.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Carga de evidencia',
                    'description' => 'Adjunta pruebas del servicio realizado.',
                    'selector' => 'form[action*="/evidence"], [data-bs-target*="evidence"]',
                ],
            ],
        ],
        'appointments.services.index' => [
            'title' => 'Ayuda: Servicios de citas',
            'intro' => 'Administra catalogo de servicios, duracion y disponibilidad.',
            'tour' => [
                [
                    'title' => 'Nuevo servicio',
                    'description' => 'Registra nombre, costo y parametros operativos.',
                    'selector' => '#service-tab-create, #service-pane-create, #appointmentServiceCreateForm',
                ],
                [
                    'title' => 'Servicios creados',
                    'description' => 'Revisa filtros y edita/activa/inactiva servicios existentes.',
                    'selector' => '#service-tab-created, #service-pane-created, #servicesCreatedList, #servicesFilterSearch',
                ],
                [
                    'title' => 'Paquetes de sesiones',
                    'description' => 'Configura paquetes y frecuencia de asistencia para clientes.',
                    'selector' => '#service-tab-packages, #service-pane-packages, form[action*="/appointments/packages"]',
                ],
            ],
        ],
        'seller-commissions.index' => [
            'title' => 'Ayuda: Comisiones de vendedores',
            'intro' => 'Controla calculo, estado y pago de comisiones por vendedor.',
            'tour' => [
                [
                    'title' => 'Resumen de comisiones',
                    'description' => 'Visualiza montos acumulados y pendientes por cancelar.',
                    'selector' => 'table.table, .card',
                ],
                [
                    'title' => 'Acciones de pago',
                    'description' => 'Marca comisiones como pagadas y actualiza tasas por vendedor.',
                    'selector' => 'form[action*="mark-paid"], form[action*="/rate/"]',
                ],
            ],
        ],
        'seller-commissions.progress' => [
            'title' => 'Ayuda: Mi progreso de comisiones',
            'intro' => 'Vista personal del vendedor con avance de objetivos y comisiones.',
            'tour' => [
                [
                    'title' => 'Indicadores personales',
                    'description' => 'Revisa monto acumulado y metas del periodo.',
                    'selector' => '.card, .progress',
                ],
                [
                    'title' => 'Detalle de operaciones',
                    'description' => 'Consulta ventas que impactan tu comision.',
                    'selector' => 'table.table',
                ],
            ],
        ],
        'electronic.documents.index' => [
            'title' => 'Ayuda: Documentos electronicos',
            'intro' => 'Monitorea emision, estado y reintentos de documentos fiscales electronicos.',
            'tour' => [
                [
                    'title' => 'Bandeja de documentos',
                    'description' => 'Filtra por estado para detectar errores pendientes de accion.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Reintento de emision',
                    'description' => 'Ejecuta reintentos sobre documentos fallidos.',
                    'selector' => 'form[action*="/retry"], button[type="submit"]',
                ],
            ],
        ],
        'sales.electronic.documents.tenant' => [
            'title' => 'Ayuda: Mis documentos electronicos',
            'intro' => 'Consulta tu historial de documentos emitidos y su estado de procesamiento.',
            'tour' => [
                [
                    'title' => 'Bandeja de documentos',
                    'description' => 'Filtra por estatus para identificar rechazos, pendientes y emitidos.',
                    'selector' => 'table.table, .card',
                ],
                [
                    'title' => 'Acciones disponibles',
                    'description' => 'Abre detalle o ejecuta acciones permitidas segun estado.',
                    'selector' => 'button, a[href*="electronic"], form[action*="electronic"]',
                ],
            ],
        ],
        'projects.module.index' => [
            'title' => 'Ayuda: Modulo de proyectos',
            'intro' => 'Punto de entrada para nomina, proyectos y cotizaciones del modulo.',
            'tour' => [
                [
                    'title' => 'Tarjetas de acceso',
                    'description' => 'Desde aqui navegas a cada submodulo operativo.',
                    'selector' => '.card, a[href*="/nomina"], a[href*="/proyectos"], a[href*="/cotizaciones"]',
                ],
            ],
        ],
        'projects.module.payroll.index' => [
            'title' => 'Ayuda: Nomina',
            'intro' => 'Gestiona personal, pagos y comprobantes del equipo.',
            'tour' => [
                [
                    'title' => 'Equipo de trabajo',
                    'description' => 'Administra integrantes y su estado operativo.',
                    'selector' => '#payrollTeamCard, #payroll-team-content, #payrollTeamTable, [data-bs-target="#teamMemberModal"]',
                ],
                [
                    'title' => 'Registro de nomina',
                    'description' => 'Carga nuevos pagos y genera comprobantes.',
                    'selector' => '#payrollPaymentsCard, [data-section-toggle="payroll-payments-content"], #payroll-payments-content, #payrollOpenPaymentModalBtn, #payrollPaymentModal',
                ],
            ],
        ],
        'projects.module.projects.index' => [
            'title' => 'Ayuda: Proyectos',
            'intro' => 'Administra proyectos activos, fases, tareas y asignaciones.',
            'tour' => [
                [
                    'title' => 'Crear proyecto',
                    'description' => 'Registra objetivo, alcance y visibilidad del proyecto.',
                    'selector' => 'form[action*="/proyectos"], [data-bs-target*="project"]',
                ],
                [
                    'title' => 'Listado de proyectos',
                    'description' => 'Abre cada proyecto para gestionar fase, tareas y activos.',
                    'selector' => 'table.table, .card',
                ],
            ],
        ],
        'projects.module.projects.show' => [
            'title' => 'Ayuda: Detalle de proyecto',
            'intro' => 'Gestiona fase, tareas, activos y equipo asignado del proyecto seleccionado.',
            'tour' => [
                [
                    'title' => 'Resumen del proyecto',
                    'description' => 'Consulta estado general, avance y visibilidad.',
                    'selector' => '.project-show-hero, .project-meta-pills, .project-roadmap',
                ],
                [
                    'title' => 'Tareas y asignaciones',
                    'description' => 'Crea tareas y actualiza estado de ejecucion.',
                    'selector' => 'form[action*="/tasks"], table.table',
                ],
                [
                    'title' => 'Activos del proyecto',
                    'description' => 'Adjunta evidencias y documentos de avance.',
                    'selector' => 'form[action*="/assets"], a[href*="/assets/"]',
                ],
            ],
        ],
        'projects.module.quotations.index' => [
            'title' => 'Ayuda: Cotizaciones',
            'intro' => 'Administra cotizaciones y conversiones a proyecto, venta o inventario.',
            'tour' => [
                [
                    'title' => 'Registro de cotizacion',
                    'description' => 'Crea nuevas propuestas para clientes y seguimiento comercial.',
                    'selector' => 'form[action*="/cotizaciones"], [data-bs-target*="quotation"]',
                ],
                [
                    'title' => 'Acciones de conversion',
                    'description' => 'Transforma la cotizacion en proyecto, venta o entrada de inventario.',
                    'selector' => 'form[action*="to-project"], form[action*="to-sale"], form[action*="to-inventory-entry"]',
                ],
            ],
        ],
        'reports.index' => [
            'title' => 'Ayuda: Reportes',
            'intro' => 'Centro de reporteria para exportar analitica comercial, inventario y finanzas.',
            'tour' => [
                [
                    'title' => 'Catalogo de reportes',
                    'description' => 'Selecciona el reporte segun area de analisis requerida.',
                    'selector' => '.card, a[href*="/reports/"]',
                ],
                [
                    'title' => 'Exportaciones',
                    'description' => 'Descarga reportes en PDF o Excel.',
                    'selector' => 'a[href$="/pdf"], a[href$="/excel"]',
                ],
            ],
        ],
        'reports.csv.viewer' => [
            'title' => 'Ayuda: Visor CSV',
            'intro' => 'Previsualiza reportes CSV antes de descarga o analisis externo.',
            'tour' => [
                [
                    'title' => 'Vista de datos',
                    'description' => 'Tabla con contenido del archivo CSV seleccionado.',
                    'selector' => 'table.table, .table-responsive',
                ],
            ],
        ],
        'tenant.payments.index' => [
            'title' => 'Ayuda: Pagos de tenants',
            'intro' => 'Supervisa solicitudes de pago, aprobaciones y fechas de corte de planes.',
            'tour' => [
                [
                    'title' => 'Cola de pagos',
                    'description' => 'Revisa pagos pendientes de aprobacion o rechazo.',
                    'selector' => 'table.table',
                ],
                [
                    'title' => 'Acciones de revision',
                    'description' => 'Aprueba, rechaza o actualiza fecha de corte.',
                    'selector' => 'form[action*="/approve"], form[action*="/reject"], form[action*="/cutoff"]',
                ],
            ],
        ],
        'documentation.index' => [
            'title' => 'Ayuda: Documentacion tecnica',
            'intro' => 'Consulta manuales tecnicos y descargas operativas del sistema.',
            'tour' => [
                [
                    'title' => 'Listado de documentos',
                    'description' => 'Selecciona el documento tecnico segun rol o proceso.',
                    'selector' => 'table.table, .list-group, a[href*="/documentation/download/"]',
                ],
            ],
        ],
        'landing' => [
            'title' => 'Ayuda: Landing principal',
            'intro' => 'Presentacion inicial de Shopix con acceso a registro y directorio de tiendas.',
            'tour' => [
                [
                    'title' => 'Propuesta de valor',
                    'description' => 'Seccion introductoria de beneficios principales de la plataforma.',
                    'selector' => 'header, .hero, .section-title',
                ],
                [
                    'title' => 'Accion principal',
                    'description' => 'Accede al flujo para crear tienda o explorar directorio.',
                    'selector' => 'a[href*="create-tenant-user"], a[href*="/landings"], .btn',
                ],
            ],
        ],
        'landing.directory' => [
            'title' => 'Ayuda: Directorio de tiendas',
            'intro' => 'Explora tiendas y servicios publicados en Shopix desde una sola vista.',
            'tour' => [
                [
                    'title' => 'Buscador global',
                    'description' => 'Filtra tiendas por nombre, ciudad o actividad economica.',
                    'selector' => 'input[type="search"], #directorySearchInput, .hero-search-input',
                ],
                [
                    'title' => 'Tarjetas de tiendas',
                    'description' => 'Entra a cada landing para ver productos, servicios y contacto.',
                    'selector' => '.card, .tenant-card, a[href*="/"]',
                ],
            ],
        ],
        'tenant.public' => [
            'title' => 'Ayuda: Tienda publica',
            'intro' => 'Vista publica del tenant para explorar productos y comprar.',
            'tour' => [
                [
                    'title' => 'Inicio y navegacion',
                    'description' => 'Desde esta barra puedes entrar al catalogo, contacto e inicio de sesion cliente.',
                    'selector' => '.landing-header, #landingNavbar, [data-shopix-open-auth], .tenant-main-nav-btn',
                ],
                [
                    'title' => 'Hero y accesos rapidos',
                    'description' => 'Este bloque resume la tienda y te deja ir a catalogo, WhatsApp o ubicacion.',
                    'selector' => '.hero, .hero-copy-shell, .hero-actions, a[href*="/categorias"]',
                ],
                [
                    'title' => 'Catalogo principal',
                    'description' => 'Aqui filtras productos y tambien puedes consultar servicios para cita cuando esten habilitados.',
                    'selector' => '#productos, #product-search, #products-container, .category-link, .js-open-tenant-service',
                ],
                [
                    'title' => 'Iniciar sesion y completar pago o cita',
                    'description' => 'Pulsa Entrar para identificarte. Luego agrega al carrito para continuar al checkout y pagar, o usa Reservar cita/WhatsApp para gestionar agenda de servicios.',
                    'selector' => '[data-shopix-open-auth], [data-bs-target="#tenantCartOffcanvas"], .js-open-tenant-service, [href*="whatsapp"]',
                    'action' => '1) Inicia sesion. 2) Compra por carrito y checkout o agenda una cita segun el servicio.',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Que hacer',
                    'items' => [
                        'Navega por categorias para descubrir productos.',
                        'Agrega productos al carrito y sigue al checkout.',
                    ],
                ],
            ],
        ],
        'tenant.public.categories' => [
            'title' => 'Ayuda: Categorias publicas',
            'intro' => 'Muestra productos agrupados por categoria para el cliente final.',
            'tour' => [
                [
                    'title' => 'Navegacion del catalogo',
                    'description' => 'Estos accesos te permiten volver, abrir categorias y entrar con tu cuenta cliente.',
                    'selector' => '.landing-header, #landingNavbar, .tenant-main-nav-btn, [data-shopix-open-auth]',
                ],
                [
                    'title' => 'Filtros y busqueda',
                    'description' => 'Usa los filtros por categoria y el buscador para localizar productos rapido.',
                    'selector' => '.filters-panel, #product-search-desktop, #product-search-results, .category-link',
                ],
                [
                    'title' => 'Resultados y detalle',
                    'description' => 'Desde cada tarjeta puedes abrir el detalle del producto y elegir su variante.',
                    'selector' => '#products-container, .product-item, .product-card-link',
                ],
                [
                    'title' => 'Login y flujo de pago o cita',
                    'description' => 'Inicia sesion desde Entrar. Para compras agrega al carrito y finaliza pago en checkout; para servicios usa Reservar cita cuando este disponible.',
                    'selector' => '[data-shopix-open-auth], [data-bs-target="#tenantCartOffcanvas"], [data-shopix-catalog-appointment], .js-open-tenant-service',
                    'action' => 'Primero autentica tu cuenta, luego completa compra o agenda segun el tipo de producto/servicio.',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Explora catalogo por categoria.',
                        'Abre un producto para ver variantes y precio.',
                    ],
                ],
            ],
        ],
        'tenant.public.product' => [
            'title' => 'Ayuda: Producto publico',
            'intro' => 'Detalle de producto para seleccionar variantes y cantidad.',
            'tour' => [
                [
                    'title' => 'Navegacion y acceso cliente',
                    'description' => 'Desde aqui puedes volver al catalogo y entrar con tu cuenta cliente.',
                    'selector' => '.landing-header, #product-back-link, [data-shopix-open-auth], .tenant-main-nav-btn',
                ],
                [
                    'title' => 'Galeria del producto',
                    'description' => 'Revisa imagenes del producto y abre vista completa si necesitas mas detalle.',
                    'selector' => '.product-gallery-shell, #product-gallery-track, #product-gallery-fullscreen',
                ],
                [
                    'title' => 'Seleccion de variante',
                    'description' => 'Elige talla/presentacion disponible antes de continuar con compra o cotizacion.',
                    'selector' => '#variants-container, .variant-button, #selected-variant-indicator',
                ],
                [
                    'title' => 'Iniciar sesion y continuar a pago o cita',
                    'description' => 'Inicia sesion con Entrar. Si ves Agregar al carrito, continua al checkout para el pago; si aplica cotizacion/servicio, usa WhatsApp para coordinar o gestionar cita.',
                    'selector' => '[data-shopix-open-auth], #add-to-cart-button, #whatsapp-button, [data-bs-target="#tenantCartOffcanvas"]',
                    'action' => 'Selecciona variante, autentica tu cuenta y finaliza por checkout o coordinacion por WhatsApp segun el flujo activo.',
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Selecciona variante y cantidad segun disponibilidad.',
                        'Presiona agregar al carrito para continuar compra.',
                    ],
                ],
            ],
        ],
        'tenant.public.paymentMethods' => [
            'title' => 'Ayuda: Metodos de pago publicos',
            'intro' => 'Muestra al cliente las opciones disponibles para pagar.',
            'sections' => [
                [
                    'heading' => 'Acciones',
                    'items' => [
                        'Selecciona el metodo de pago que prefieras.',
                        'Sigue instrucciones mostradas para completar el pago.',
                    ],
                ],
            ],
        ],
    ],
];
