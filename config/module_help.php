<?php

return [
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
                    'title' => 'Paso 1: Revisa tasa actual',
                    'description' => 'Verifica el valor actual del dolar antes de operar.',
                    'selector' => '#currentDollarRate',
                    'action' => 'Confirma la tasa visible para conversiones de moneda.',
                ],
                [
                    'title' => 'Paso 2: Actualiza tasa si aplica',
                    'description' => 'Abre el modal para registrar una nueva tasa.',
                    'selector' => '[data-bs-target="#updateDollarRateModal"]',
                    'action' => 'Si hubo cambio de mercado, actualiza la tasa del dia.',
                ],
                [
                    'title' => 'Paso 3: Crear metodo de pago',
                    'description' => 'Registra cuentas y datos bancarios por moneda.',
                    'selector' => '#createPaymentMethodForm',
                    'action' => 'Completa nombre, moneda, beneficiario y QR.',
                ],
                [
                    'title' => 'Paso 4: Editar o activar',
                    'description' => 'Administra disponibilidad de cada metodo.',
                    'selector' => '.btn-edit-method',
                    'action' => 'Edita datos o usa Inactivar/Activar segun corresponda.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Tasa actual',
                    'description' => 'Valor usado para conversiones VES/USD.',
                    'selector' => '#currentDollarRate',
                ],
                [
                    'title' => 'Actualizar tasa',
                    'description' => 'Boton para abrir el modal de tasa del dolar.',
                    'selector' => '[data-bs-target="#updateDollarRateModal"]',
                ],
                [
                    'title' => 'Monedas',
                    'description' => 'Listado de monedas habilitadas.',
                    'selector' => '.card .card-header h6',
                ],
                [
                    'title' => 'Nuevo metodo',
                    'description' => 'Abre formulario para crear metodo de pago.',
                    'selector' => '[data-bs-target="#createPaymentMethodModal"]',
                ],
                [
                    'title' => 'Gestion de metodos',
                    'description' => 'Edita datos y activa/inactiva metodos existentes.',
                    'selector' => '.btn-edit-method',
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
                    'title' => 'Paso 1: Filtrar categoria',
                    'description' => 'Selecciona una categoria para reducir productos visibles.',
                    'selector' => '#categoriesContainer',
                    'action' => 'Usa las tarjetas de categorias para iniciar el flujo.',
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
                    'title' => 'Paso 4: Metodo de pago',
                    'description' => 'En el paso 2 defines los metodos y montos de pago.',
                    'selector' => '#paymentMethods',
                    'action' => 'Distribuye el total entre uno o varios metodos.',
                ],
                [
                    'title' => 'Paso 5: Confirmar venta',
                    'description' => 'Revisa resumen y finaliza la orden.',
                    'selector' => '#purchaseForm',
                    'action' => 'Completa los pasos y confirma para emitir la venta.',
                ],
            ],
            'tour' => [
                [
                    'title' => 'Categorias de venta',
                    'description' => 'Filtra productos por categoria.',
                    'selector' => '#categoriesContainer',
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
                    'title' => 'Metodos de pago',
                    'description' => 'Define como se cancela la venta.',
                    'selector' => '#paymentMethods',
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
        'tenant.public' => [
            'title' => 'Ayuda: Tienda publica',
            'intro' => 'Vista publica del tenant para explorar productos y comprar.',
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
