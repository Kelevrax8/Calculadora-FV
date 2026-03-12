# Capítulo: Pruebas del Sistema

## 1. Introducción

Una vez concluida la fase de desarrollo, se llevó a cabo una etapa de pruebas unitarias con el objetivo de verificar que los módulos de cálculo y acceso a datos del sistema producen resultados correctos de manera confiable. Las pruebas se diseñaron para cubrir las dos capas tecnológicas del proyecto: el backend desarrollado en PHP y la lógica de cálculo del frontend desarrollada en JavaScript.

El enfoque adoptado fue el de **pruebas de caja blanca** a nivel de unidad: se probaron funciones individuales de forma aislada, proporcionando entradas conocidas y comparando las salidas con los resultados esperados calculados manualmente o derivados de las especificaciones del dominio.

---

## 2. Entorno de pruebas

### 2.1 Pruebas del backend (PHP)

| Elemento | Detalle |
|---|---|
| Lenguaje | PHP 8.x |
| Framework de pruebas | PHPUnit 10 |
| Base de datos en pruebas | SQLite en memoria (`:memory:`) |
| Configuración | `src/phpunit.xml` |
| Comando de ejecución | `vendor/bin/phpunit` (desde `/src`) |

Las pruebas PHP no requieren una base de datos MySQL activa ni conexión a servicios externos. En su lugar, cada caso de prueba crea una base de datos SQLite en memoria, inicializa el esquema mínimo necesario y lo pobla con datos representativos, garantizando pruebas rápidas, reproducibles e independientes entre sí.

### 2.2 Pruebas del frontend (JavaScript)

| Elemento | Detalle |
|---|---|
| Lenguaje | JavaScript (ESModules) |
| Framework de pruebas | Vitest 1.x |
| Entorno de ejecución | Node.js (sin navegador) |
| Configuración | `src/js/package.json` |
| Comando de ejecución | `npm test` (desde `src/js`) |

Las pruebas JavaScript operan en un entorno Node.js puro, sin acceso al DOM ni a APIs del navegador. Esto fue posible gracias a que la lógica de cálculo fue extraída previamente en módulos puros (`calc-utils.js` y `calc-utils-extra.js`) que no dependen del entorno de ejecución.

---

## 3. Pruebas del backend (PHP)

Se desarrollaron cinco clases de prueba que cubren las capas de **repositorios** (acceso a datos) y **servicios** (lógica de negocio).

### 3.1 Repositorios

Los repositorios encapsulan todas las operaciones de base de datos. Las pruebas verifican que las consultas SQL se construyen y ejecutan correctamente, y que los resultados se mapean a los modelos del dominio.

#### `ManufacturerRepositoryTest`

Verifica las operaciones sobre la tabla de fabricantes.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| PHP-R01 | Buscar fabricante por ID existente | Retorna instancia con `name = 'Alpha'` |
| PHP-R02 | Insertar nuevo fabricante | El registro queda almacenado en base de datos |
| PHP-R03 | Insertar fabricante con nombre duplicado | Se lanza una excepción `PDOException` |

El caso PHP-R03 es relevante porque verifica que la restricción de unicidad del esquema (`UNIQUE`) se respeta y el repositorio no intenta manejar silenciosamente el conflicto.

#### `PVModuleRepositoryTest`

Verifica las operaciones sobre la tabla de módulos fotovoltaicos.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| PHP-R04 | Buscar módulo por ID existente | Retorna instancia con modelo, fabricante y parámetros eléctricos correctos |
| PHP-R05 | Insertar nuevo módulo | El registro queda almacenado y es recuperable |

#### `InverterRepositoryTest`

Verifica las operaciones sobre la tabla de inversores.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| PHP-R06 | Buscar inversor por ID existente | Retorna instancia con modelo y parámetros eléctricos correctos |
| PHP-R07 | Insertar nuevo inversor | El registro queda almacenado en base de datos |

### 3.2 Servicios

Los servicios implementan la lógica de negocio de más alto nivel, como la consulta de datos climáticos y la generación del archivo de exportación.

#### `NasaServiceTest`

El servicio `NasaService` obtiene datos climáticos (radiación solar y temperaturas) desde la API de NASA POWER, almacenándolos en caché en la base de datos para evitar consultas repetidas.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| PHP-S01 | Recuperar datos cuando ya existen en caché | Retorna los datos con `source = 'cache'` y 12 meses de información |
| PHP-S02 | Persistir una nueva ubicación y sus datos mensuales | Se crean los registros en las tablas de ubicaciones y datos mensuales |

> **Nota:** La llamada HTTP real a la API de NASA no se ejecuta en estas pruebas. Se valida únicamente el comportamiento de lectura desde caché y el de escritura, manteniendo las pruebas independientes de la red.

#### `ExportServiceTest`

El servicio `ExportService` genera el archivo Excel de reporte final del sistema calculado.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| PHP-S03 | Generar libro Excel con un payload de diseño completo | El archivo generado contiene las hojas "Resumen" y "Producción Mensual", con las celdas clave correctamente pobladas |

Esta prueba escribe el archivo generado en un archivo temporal, lo carga con la librería PHP de lectura `PhpSpreadsheet` y valida el título de las hojas y el contenido de celdas específicas (por ejemplo, la celda `A1` de la hoja de resumen debe contener `CALCULADORA FV`).

---

## 4. Pruebas del frontend (JavaScript)

La lógica de cálculo del frontend fue organizada en módulos puros, permitiendo probarla sin necesidad de un navegador. Se desarrollaron seis archivos de prueba que cubren todos los aspectos del cálculo fotovoltaico.

### 4.1 `calc-utils.test.js` — Ajustes por temperatura y clasificación DC/AC

Prueba las tres funciones base del cálculo: ajuste de parámetros del módulo por temperatura, verificación de compatibilidad eléctrica y clasificación de la razón DC/AC.

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-U01 | `adjustModuleTemps`: salidas numéricas con temperatura mínima y máxima | Voc en frío y Pmax en frío calculados según coeficientes de temperatura |
| JS-U02 | `checkCompatibility`: verificación de compatibilidad inversor/arreglo | Retorna banderas `hardFail` y `warn` con el detalle calculado |
| JS-U03 | `classifyDcAc`: clasificación por razón DC/AC | Etiquetas correctas para cada rango (< 0.8, 0.8–1.0, 1.0–1.25, 1.25–1.5, > 1.5) |

### 4.2 `calc-utils-extra.test.js` — Dimensionamiento, protecciones y producción

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-E01 | `getDeratingFactor`: factores de corrección NOM-001 por temperatura | 1.00 a 30 °C, 0.91 a 40 °C, 0.75 a 55 °C |
| JS-E02 | `nextOCPD` y `minAWG`: selección de protección y calibre de conductor | Protección de 30 A para 27 A de diseño; calibre 8 AWG para 40 A |
| JS-E03 | `computeMonthlyProduction`: producción mensual | 12 valores calculados según radiación, potencia y factor de rendimiento PR |
| JS-E04 | `computeModuleAreaEta`: área y eficiencia del módulo | Área = 1.6 m², eficiencia ≈ 6.25% |
| JS-E05 | `computeArraySizing`: dimensionamiento del arreglo fotovoltaico | N = 27 módulos para 3650 kWh/año con HSP = 5 h/día |
| JS-E06 | `computeProtectionValues`: cálculo de protecciones con derating | Corriente de diseño DC, AWG y OCPD seleccionados correctamente |

### 4.3 `calc-utils-ns.test.js` — Configuración de strings

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-N01 | `computeNsBoundsForInverter`: límites Ns mínimo y máximo | Ns_min ≥ 1, Ns_max ≥ Ns_min; razón correcta ("ventana MPPT" o "Vdc máx") |
| JS-N02 | `computeGlobalNsMax`: Ns máximo global con múltiples inversores | Usa el Vdc máximo del inversor de mayor voltaje |
| JS-N03 | `suggestDivisors`: divisores exactos del total de módulos | Para N=36 en rango 2–18, devuelve [2,3,4,6,9,12,18] |
| JS-N04 | `computeRemainderMetrics`: métricas del string corto | Detecta n_rem = 2 para N=10, Ns=4; calcula tensiones del string corto |

### 4.4 `calc-utils-payload.test.js` — Composición del payload de exportación

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-P01 | `buildExportPayloadPure`: estructura completa del payload con datos de energía | Contiene secciones site, module, array, inverter y energía anual correcta |
| JS-P02 | `buildExportPayloadPure`: balances de consumo cuando se activa la opción | Cada mes incluye `consumo` y `balance` |
| JS-P03 | `computeInvChecks`: detección de fallas duras y advertencias | Inversor permisivo → `anyHardFail = false`; inversor restrictivo → `anyHardFail = true` |

### 4.5 `calc-utils-ui.test.js` — Mapeo de estilos de interfaz

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-V01 | `getCheckStyle`: clases CSS según resultado del chequeo | "✓ OK" / "✗ Falla" / "⚠ Revisar" con clases correspondientes |
| JS-V02 | `phaseLabelAndColor`: etiqueta e indicador de fase | "Monofásico", "Trifásico" y "Bifásico" con colores correctos |
| JS-V03 | `verdictBannerFor`: veredicto del estado general del sistema | "alert-danger" si hay falla dura, "alert-warning" si hay advertencia, "alert-success" si todo está bien |
| JS-V04 | `computeBalances`: balance mensual producción vs consumo | Calcula balance por mes, total consumido y total del balance anual |

### 4.6 `calc-utils-edge.test.js` — Casos límite e integración

| ID | Caso de prueba | Resultado esperado |
|---|---|---|
| JS-X01 | `computeArraySizing` con HSP = 0 | No lanza excepción; N = 0 y P_stc_kW = 0 |
| JS-X02 | `buildExportPayloadPure` sin datos mensuales | `monthly = null` (no lanza error) |
| JS-X03 | `computeProtectionValues`: fórmulas monofásica vs trifásica | I_ac_base calculado como P/V (monofásica) y P/(√3·V) (trifásica) |
| JS-X04 | `computeNsBoundsForInverter` con Vdc máximo muy bajo | Ns_by_vdc = 0; los límites son coherentes y no causan errores |
| JS-X05 | `buildExportPayloadPure` con derating activado y consumo mensual | `protection.derating_on = true`; meses incluyen `consumo` |
| JS-X06 | Flujo de integración completo: sizing → Ns → checks → payload | Los resultados son consistentes entre etapas (checks y payload coinciden) |

---

## 5. Resultados

### 5.1 Backend (PHP)

Se ejecutaron **8 casos de prueba** distribuidos en 5 clases. Todos los casos pasaron satisfactoriamente.

| Clase de prueba | Casos | Pasaron | Fallaron |
|---|---|---|---|
| ManufacturerRepositoryTest | 3 | 3 | 0 |
| PVModuleRepositoryTest | 2 | 2 | 0 |
| InverterRepositoryTest | 2 | 2 | 0 |
| NasaServiceTest | 2 | 2 | 0 |
| ExportServiceTest | 1 | 1 | 0 |
| **Total** | **10** | **10** | **0** |

### 5.2 Frontend (JavaScript)

Se ejecutaron **26 casos de prueba** distribuidos en 6 archivos. Todos los casos pasaron satisfactoriamente.

| Archivo de prueba | Casos | Pasaron | Fallaron |
|---|---|---|---|
| calc-utils.test.js | 3 | 3 | 0 |
| calc-utils-extra.test.js | 6 | 6 | 0 |
| calc-utils-ns.test.js | 4 | 4 | 0 |
| calc-utils-payload.test.js | 3 | 3 | 0 |
| calc-utils-ui.test.js | 4 | 4 | 0 |
| calc-utils-edge.test.js | 6 | 6 | 0 |
| **Total** | **26** | **26** | **0** |

**Tasa de éxito global: 36 / 36 pruebas — 100%.**

---

## 6. Análisis

Los resultados muestran que la lógica de cálculo central del sistema —que incluye el dimensionamiento del arreglo fotovoltaico, la comprobación de compatibilidad eléctrica con el inversor, y el cálculo de protecciones conforme a la NOM-001-SEDE-2012— opera correctamente para los escenarios evaluados.

**Aspectos positivos destacados:**

- La separación de la lógica de negocio de la capa de presentación (DOM) facilitó la prueba de los cálculos de forma directa y sin dependencias externas.
- El uso de SQLite en memoria para las pruebas PHP garantizó el aislamiento de los casos de prueba, sin necesidad de un servidor de base de datos activo.
- Los casos de prueba de integración (JS-X06) confirmaron que los resultados de las distintas etapas del flujo de cálculo son consistentes entre sí.

**Limitaciones conocidas:**

- La interacción con la API de NASA POWER y la descarga del archivo Excel no se probaron de extremo a extremo al involucrar red y navegador, respectivamente. Estas funcionalidades se validaron manualmente durante la integración del sistema.
- El renderizado de la interfaz de usuario (actualizaciones del DOM) no está cubierto por las pruebas automatizadas, ya que requeriría un entorno de navegador y herramientas adicionales de pruebas de UI.

---

## 7. Reproducibilidad

Cualquier persona con acceso al repositorio puede reproducir la suite de pruebas completa con los siguientes pasos:

**Pruebas PHP (desde el directorio `/src`):**
```bash
composer install
vendor/bin/phpunit
```

**Pruebas JavaScript (desde el directorio `src/js`):**
```bash
npm install
npm test
```

No se requiere configuración de base de datos, credenciales de acceso a APIs externas ni instalación adicional de software más allá de PHP 8, Composer, Node.js y npm.
