# 3.3 Codificación

La fase de codificación constituye la materialización concreta de las decisiones de diseño tomadas en etapas anteriores del proceso de desarrollo. En esta sección se documenta la implementación de cada componente del sistema, explicando tanto las decisiones técnicas adoptadas como la justificación de las mismas. El código fuente fue desarrollado en PHP 8 con el modo de tipos estrictos habilitado (`declare(strict_types=1)`) en todos los archivos de la capa de lógica, lo que obliga al motor de PHP a verificar los tipos de los argumentos en tiempo de ejecución y a rechazar conversiones implícitas que puedan introducir errores sutiles. La base de datos utilizada es MySQL 8.4, y toda la comunicación entre la capa de presentación (el navegador web) y la capa de negocio se realiza mediante peticiones HTTP con respuestas codificadas en formato JSON.

## 3.3.1 Arquitectura del Sistema

El desarrollo de aplicaciones web sin una estructura formal tiende a producir lo que en ingeniería de software se denomina código espagueti o, en términos más precisos, un objeto dios (God Object): un único archivo o módulo que concentra en su interior toda la lógica del sistema, desde la lectura de parámetros HTTP hasta la ejecución de consultas SQL y la generación de la respuesta. Este antipatrón dificulta enormemente el mantenimiento, las pruebas y la extensión del sistema, puesto que cualquier modificación puede tener efectos secundarios impredecibles en partes no relacionadas del código.

Para evitar esta problemática, el presente sistema se desarrolló siguiendo una **arquitectura en capas** complementada con los principios de diseño orientado a objetos SOLID (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion). El principio más relevante en el contexto de esta arquitectura es el Principio de Responsabilidad Única (SRP), que establece que cada clase o módulo debe tener una sola razón para cambiar; es decir, debe encargarse exclusivamente de una parte bien delimitada del comportamiento del sistema. Las dependencias entre componentes fluyen en una única dirección: las capas superiores conocen a las inferiores, pero nunca al revés, lo que garantiza que los detalles de infraestructura (como la base de datos o el protocolo HTTP) no contaminen la lógica de negocio.

La estructura del código fuente refleja directamente estos principios, separando las responsabilidades en carpetas específicas dentro del directorio `app`:

```
src/app/
├── Core/           ← Infraestructura base (conexión DB, configuración, autenticación)
├── Models/         ← Representación tipada de las entidades de dominio
├── Repositories/   ← Acceso y persistencia en base de datos (todo el SQL)
├── Services/       ← Lógica de negocio e integraciones con servicios externos
└── Controllers/    ← Coordinación: valida la entrada, llama servicios, devuelve resultado
```

La carga automática de estas clases se gestiona mediante **Composer** con el estándar **PSR-4** (PHP Standard Recommendation 4), que establece una correspondencia directa y predecible entre el espacio de nombres (namespace) de una clase y su ubicación en el sistema de archivos. Así, una clase declarada como `App\Repositories\PVModuleRepository` se encontrará siempre en el archivo `src/app/Repositories/PVModuleRepository.php`, sin necesidad de escribir instrucciones `require` o `include` manualmente en ninguna parte del código. El archivo `composer.json` del proyecto declara la raíz del namespace `App\` apuntando a `src/app/` y activa la opción `optimize-autoloader`, lo que genera un mapa de clases indexado que evita búsquedas en disco durante la ejecución en producción.

La figura 3.X ilustra el flujo de dependencias entre las capas del sistema:

```
Navegador
    │
    ▼
src/api/*.php          ← Punto de entrada HTTP (enrutamiento, respuesta JSON)
    │
    ▼
Controllers/           ← Validación de entrada, coordinación
    │
    ▼
Services/              ← Lógica de negocio, integraciones externas
Repositories/          ← Consultas SQL, persistencia
    │
    ▼
Core/Database.php      ← Conexión PDO compartida (Singleton)
    │
    ▼
Base de datos MySQL 8.4
```

Una característica fundamental de este diseño es que **los archivos dentro de la carpeta `api` son los únicos puntos del sistema que interactúan directamente con el protocolo HTTP**. Son los únicos que leen las superglobales `$_GET` y `$_POST`, escriben encabezados HTTP mediante `header()` o codifican la respuesta final en JSON. Todo el código que se encuentra por debajo de esta frontera —controladores, servicios, repositorios, modelos— trabaja exclusivamente con estructuras de datos PHP nativas (arreglos, objetos, excepciones) sin ningún conocimiento del entorno web que los invoca. Esta separación tiene un beneficio práctico inmediato: cualquier parte de la lógica de negocio puede ser probada de forma aislada sin necesidad de simular una petición HTTP, simplemente instanciando la clase correspondiente con sus dependencias.

---

## 3.3.2 Capa Core

La capa Core está compuesta por tres archivos: `Config.php`, `Database.php` y `AuthGuard.php`. Estos tres archivos forman la base sobre la que se construye el resto del sistema y resuelven tres responsabilidades fundamentales: centralizar la configuración de la aplicación con soporte para múltiples entornos de despliegue, proporcionar al resto del sistema una única conexión a la base de datos MySQL lista para ejecutar consultas, y proteger todos los endpoints y páginas de acceso no autenticado.

### 3.3.2.1 Config.php — Configuración por Defecto

El archivo `Config.php` actúa como el origen de configuración de último recurso de la aplicación. Define cinco constantes globales mediante la función `define()` de PHP: cuatro destinadas a la conexión con la base de datos (`DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`) y una constante adicional `BASE_URL`. Esta última constante es particularmente importante para la portabilidad del sistema: permite desplegar la aplicación tanto en la raíz de un dominio (por ejemplo, `https://ejemplo.com/`) como en un subdirectorio (por ejemplo, `https://ejemplo.com/calculadora/`) sin necesidad de modificar ningún enlace interno ni ruta de redirección.

El papel de estas constantes dentro del sistema no es ser los valores de configuración definitivos, sino actuar como **valores de reserva** (_fallback_) para entornos donde no se hayan configurado variables de entorno del sistema operativo. Esta distinción es crucial: en un entorno de producción con Docker Compose, las credenciales reales de la base de datos se inyectan como variables de entorno y los valores de `Config.php` nunca llegan a utilizarse. En un entorno de hospedaje compartido donde no sea posible definir variables de entorno, el administrador puede editar directamente este archivo con los valores correspondientes sin tocar el resto del código fuente.

```php
<?php
define('BASE_URL', '');

define('DB_HOST',     'db');
define('DB_NAME',     'app_db');
define('DB_USER',     'app_user');
define('DB_PASSWORD', 'secret');
```

### 3.3.2.2 Database.php — Conexión PDO y Patrón Singleton

La clase `Database` constituye el puente entre el código PHP de la aplicación y el servidor MySQL. Para este sistema se eligió **PDO** (PHP Data Objects), la capa de abstracción de acceso a datos nativa de PHP 5.1 y superiores. PDO ofrece tres ventajas principales sobre las antiguas extensiones `mysql_*` o `mysqli` utilizadas directamente: proporciona una interfaz unificada compatible con múltiples motores de base de datos, implementa consultas preparadas verdaderas (no simuladas) con soporte nativo en el motor, y gestiona los errores mediante excepciones en lugar de valores de retorno que el programador podría omitir revisar inadvertidamente.

La instancia PDO se construye con tres atributos de configuración que determinan el comportamiento de toda la capa de acceso a datos:

```php
$this->pdo = new PDO($dsn, $this->user, $this->password, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

El atributo `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` configura PDO para que lance una excepción de tipo `PDOException` ante cualquier error de base de datos, en lugar de retornar simplemente `false` o generar un error silencioso. Esto garantiza que ningún error de SQL pase desapercibido: si una consulta falla, la excepción propagará por la pila de llamadas hasta ser capturada por el manejador apropiado.

El atributo `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` configura el modo de recuperación por defecto para todas las consultas de la aplicación: las filas se entregarán siempre como arreglos PHP asociativos, indexados por el nombre de columna en lugar de por posición numérica. Esto hace que el código que consume los resultados sea más legible y resistente a cambios en el orden de las columnas.

El atributo `PDO::ATTR_EMULATE_PREPARES => false` desactiva la emulación de consultas preparadas que PDO habilita por defecto. Con la emulación activa, PDO intercepta las consultas antes de enviarlas al servidor MySQL, sustituye los marcadores de posición por los valores escapados y envía una única cadena de texto al motor. Con la emulación desactivada, PDO envía la plantilla SQL y los valores como dos mensajes separados al servidor de base de datos, que los trata como una consulta preparada real: el servidor compila el plan de ejecución de la consulta una sola vez a partir de la plantilla (sin datos), y luego lo ejecuta con los valores proporcionados. Esta separación entre código SQL y datos es la única garantía robusta contra inyecciones SQL; además, permite que el servidor MySQL aplique el tipo de dato correcto a cada parámetro, lo que evita conversiones implícitas que pueden afectar el uso de índices.

La construcción de la conexión está envuelta en un bloque `try/catch` que captura `PDOException`. Si la conexión falla (por ejemplo, porque el servidor de base de datos no está disponible), el manejador emite inmediatamente un código de respuesta HTTP 500 con un mensaje de error en formato JSON y termina la ejecución con `exit`. De esta forma, un error de infraestructura no expone al cliente ni la traza de la pila PHP ni información sensible sobre la configuración del servidor:

```php
try {
    $this->pdo = new PDO($dsn, $this->user, $this->password, [...]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}
```

### 3.3.2.3 El Patrón Singleton para la Conexión Compartida

Una aplicación web típica sirve una petición HTTP por proceso PHP. Sin embargo, durante el procesamiento de esa única petición, varios componentes independientes (repositorios, servicios) necesitan acceso a la base de datos. Si cada uno de ellos abriera su propia conexión PDO, el sistema establecería múltiples conexiones TCP al servidor MySQL por petición, incrementando innecesariamente la latencia y el consumo de recursos tanto en el servidor de aplicación como en el de base de datos.

Para evitar este problema se aplica el **patrón de diseño Singleton**, que garantiza que una clase tiene exactamente una instancia durante toda la vida del proceso y proporciona un punto de acceso global a ella. La implementación en PHP sigue el esquema canónico:

```php
private static ?Database $instance = null;

public static function getInstance(): static
{
    if (static::$instance === null) {
        require_once __DIR__ . '/Config.php';
        static::$instance = new static(
            host:     getenv('DB_HOST')     ?: DB_HOST,
            dbname:   getenv('DB_NAME')     ?: DB_NAME,
            user:     getenv('DB_USER')     ?: DB_USER,
            password: getenv('DB_PASSWORD') ?: DB_PASSWORD,
            port:     (int)(getenv('DB_PORT') ?: 3306),
        );
    }
    return static::$instance;
}
```

La primera llamada a `Database::getInstance()` encuentra `$instance` en `null`, crea la conexión PDO y la almacena en la propiedad estática. Todas las llamadas posteriores —independientemente del repositorio o servicio que las realice— encuentran `$instance` ya inicializada y retornan la misma conexión sin abrir una nueva. El método `getPdo()` expone el objeto `PDO` subyacente para que los repositorios puedan ejecutar consultas directamente.

Para garantizar que el Singleton no puede ser subvertido, el constructor se declara `private`, impidiendo que ningún código externo a la propia clase llame `new Database(...)`. La clase declara además dos métodos que bloquean los mecanismos alternativos de duplicación de objetos en PHP: `__clone()` (invocado por el operador `clone`) y `__wakeup()` (invocado por `unserialize()`). Ambos se declaran con el tipo de retorno `never`, una característica de PHP 8.1 que le indica al motor en tiempo de compilación que el método siempre terminará con una excepción o un error fatal, sin retornar jamás normalmente:

```php
private function __clone() {}
public function __wakeup(): never
{
    throw new \RuntimeException('Cannot unserialize a singleton.');
}
```

### 3.3.2.4 Resolución de Credenciales y la Metodología 12-Factor App

Las credenciales de conexión se resuelven mediante una **estrategia de dos niveles** que hace uso del operador de coalescencia condicional vacío (`?:`) de PHP. En la primera línea de cada parámetro se intenta leer una variable de entorno del sistema operativo mediante `getenv()`; si la función retorna `false` o una cadena vacía (es decir, la variable no está definida), el operador `?:` selecciona automáticamente la constante correspondiente de `Config.php`:

```php
host:     getenv('DB_HOST')     ?: DB_HOST,
dbname:   getenv('DB_NAME')     ?: DB_NAME,
user:     getenv('DB_USER')     ?: DB_USER,
password: getenv('DB_PASSWORD') ?: DB_PASSWORD,
port:     (int)(getenv('DB_PORT') ?: 3306),
```

Este diseño sigue el **Factor III** de la metodología 12-Factor App (una guía de buenas prácticas para aplicaciones web modernas), que establece que la configuración que varía entre entornos de despliegue (desarrollo, pruebas, producción) debe almacenarse en variables de entorno, manteniendo el código fuente completamente libre de credenciales o valores específicos de infraestructura. En el entorno Docker del proyecto, las variables de entorno se inyectan mediante el archivo `docker-compose.yml`, que las lee a su vez desde un archivo `.env` excluido del repositorio de código. Esto garantiza que las credenciales de producción nunca aparezcan en el historial de versiones del proyecto.

### 3.3.2.5 AuthGuard.php — Protección de Acceso

El archivo `AuthGuard.php` implementa la clase estática `AuthGuard`, responsable de gestionar las sesiones de usuario y de proteger tanto las páginas HTML como los endpoints de la API de acceso no autenticado.

La clase expone cuatro métodos estáticos públicos. El método privado `startSession()` centraliza la inicialización de la sesión PHP con la configuración de seguridad adecuada: la cookie de sesión se configura con los atributos `HttpOnly` (impide que JavaScript del cliente lea el identificador de sesión, mitigando ataques XSS) y `SameSite=Lax` (reduce el riesgo de ataques CSRF), y el atributo `Secure` se activa automáticamente cuando el servidor detecta una conexión HTTPS.

Para los endpoints de la API, el método `requireApi()` verifica la existencia de una clave `user` en la superglobal `$_SESSION`. Si la sesión no existe o ha expirado, el método emite inmediatamente un código HTTP 401 con un cuerpo JSON `{"error":"Unauthenticated"}` y termina la ejecución, sin que el controlador subyacente llegue a instanciarse:

```php
public static function requireApi(): void
{
    self::startSession();

    if (empty($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthenticated']);
        exit;
    }
}
```

Para las páginas HTML, el método `requirePage()` realiza la verificación equivalente y redirige al usuario a la página de inicio de sesión mediante un encabezado HTTP `Location:` cuando no existe sesión válida.

---

## 3.3.3 Esquema de Base de Datos y Modelos de Dominio

El diseño del esquema relacional de la base de datos es la piedra angular del sistema, pues todas las demás capas dependen de él. El esquema comprende seis tablas que modelan los dominios principales de la aplicación: fabricantes de equipos (`manufacturers`), módulos fotovoltaicos (`pv_modules`), inversores de corriente (`inverters`), grupos de entradas MPPT del inversor (`inverter_mppt_groups`), ubicaciones climatológicas (`climatology_locations`) y datos climatológicos mensuales por ubicación (`climatology_monthly`). La relación entre estas tablas se muestra en la figura 3.X.

### 3.3.3.1 Decisiones de Tipos de Datos

Una de las decisiones técnicas más importantes del esquema fue el uso del tipo `DECIMAL` en lugar de `FLOAT` o `DOUBLE` para todos los valores numéricos del dominio. En MySQL, `FLOAT` y `DOUBLE` son tipos de punto flotante binario: el valor se almacena como la suma de potencias de 2 más cercana al decimal ingresado, lo que introduce pequeños errores de representación. Por ejemplo, el valor 0.1 no puede representarse exactamente en punto flotante binario de 64 bits; su representación más cercana es 0.1000000000000000055511151231257827021181583404541015625. En cálculos encadenados —como los de tensión en cadena, corriente de cortocircuito corregida por temperatura o producción energética mensual— estos errores microscópicos se acumulan y pueden producir discrepancias en la décima o centésima decimal al comparar resultados intermedios.

`DECIMAL(p, s)` es un tipo de punto flotante **decimal** de precisión fija: MySQL almacena el número exactamente tal como fue ingresado, en notación decimal codificada en BCD (_Binary Coded Decimal_), con `p` dígitos totales y `s` dígitos después del punto decimal. Esto garantiza que el valor `23.45` almacenado en una columna `DECIMAL(6,2)` se recupere siempre como `23.45`, sin aproximaciones.

La tabla 3.X muestra los tipos `DECIMAL` utilizados para cada categoría de dato:

| Categoría | Tipo DECIMAL | Ejemplo |
|-----------|-------------|---------|
| Potencias (Wp, W, kW) | `DECIMAL(8,2)` | `450.00` Wp |
| Tensiones (V) | `DECIMAL(6,2)` | `48.60` V |
| Corrientes (A) | `DECIMAL(6,2)` | `13.25` A |
| Coeficientes de temperatura | `DECIMAL(6,4)` | `-0.0030` %/°C |
| Coordenadas geográficas | `DECIMAL(5,2)` | `23.74` ° |
| Irradiación solar (kWh/m²/día) | `DECIMAL(6,3)` | `5.210` kWh/m²/día |
| Temperatura (°C) | `DECIMAL(5,2)` | `31.20` °C |
| Velocidad del viento (m/s) | `DECIMAL(4,2)` | `2.30` m/s |

Las coordenadas geográficas utilizan `DECIMAL(5,2)`, lo que implica una resolución de 0.01° en latitud y longitud. A la latitud del ecuador, 0.01° de longitud equivale a aproximadamente 1.1 km, y 0.01° de latitud equivale a aproximadamente 1.1 km en cualquier punto del planeta. Esta precisión es más que suficiente para el propósito del sistema: la irradiación solar no varía significativamente a escala de kilómetros dentro de la misma región climática. El redondeo de coordenadas a dos decimales tiene además un propósito de integridad: permite que la restricción `UNIQUE (latitude, longitude)` de la tabla `climatology_locations` funcione de forma predecible, evitando que dos puntos prácticamente idénticos (por ejemplo, `23.7351` y `23.7349`) generen dos registros separados en lugar de compartir los datos climatológicos ya almacenados.

### 3.3.3.2 Integridad Referencial

El esquema define cuatro restricciones de clave foránea con políticas de eliminación cuidadosamente elegidas para cada relación:

- **`pv_modules → manufacturers` y `inverters → manufacturers` con `ON DELETE RESTRICT`**: Un fabricante no puede ser eliminado mientras tenga equipos registrados en el sistema. Esta política es la más conservadora y refleja la regla de negocio de que los datos de inventario no deben modificarse de forma que dejen registros de diseño inconsistentes. Si un usuario intenta eliminar un fabricante con equipos asociados, MySQL rechazará la operación y la aplicación devolverá un error controlado.

- **`inverter_mppt_groups → inverters` con `ON DELETE CASCADE`**: Los grupos MPPT no tienen existencia independiente fuera de su inversor; son parte intrínseca de su especificación técnica. Al eliminar un inversor, todos sus grupos MPPT deben desaparecer automáticamente. La regla `CASCADE` delega esta responsabilidad al motor de base de datos, eliminando la necesidad de lógica de borrado en cascada en el código PHP.

- **`climatology_monthly → climatology_locations` con `ON DELETE CASCADE`**: Análogamente, los doce registros mensuales de una ubicación no tienen sentido sin la ubicación misma. Si una ubicación es eliminada del caché, sus datos mensuales deben eliminarse también de forma automática.

- **`UNIQUE (location_id, month)` y `CHECK (month BETWEEN 1 AND 12)` en `climatology_monthly`**: La restricción UNIQUE garantiza que nunca existirán dos filas para el mismo mes de la misma ubicación, lo cual es un invariante del sistema (hay exactamente doce meses por ubicación). La restricción CHECK valida el rango del número de mes directamente en el motor de base de datos, como una segunda línea de defensa complementaria a la validación en el código PHP.

### 3.3.3.3 Tabla `pv_modules`

La tabla `pv_modules` almacena los parámetros electroquímicos de los módulos fotovoltaicos disponibles en el inventario del sistema. El conjunto de columnas fue seleccionado para cubrir exactamente los parámetros requeridos por los modelos de cálculo implementados en el front-end:

```sql
CREATE TABLE IF NOT EXISTS pv_modules (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer_id INT NOT NULL,
    model           VARCHAR(150) NOT NULL,
    technology      ENUM('Monocrystalline','Polycrystalline','Thin Film','Other') NOT NULL,
    pmax_stc        DECIMAL(6,2) NOT NULL,   -- Potencia máx. en STC (Wp)
    voc_stc         DECIMAL(6,2) NOT NULL,   -- Tensión de circuito abierto en STC (V)
    isc_stc         DECIMAL(6,2) NOT NULL,   -- Corriente de cortocircuito en STC (A)
    vmpp_stc        DECIMAL(6,2) NOT NULL,   -- Tensión en el punto de máx. potencia en STC (V)
    imp_stc         DECIMAL(6,2) NOT NULL,   -- Corriente en el punto de máx. potencia en STC (A)
    temp_coeff_voc  DECIMAL(6,4) NOT NULL,   -- β_Voc (%/°C, valor negativo)
    temp_coeff_pmax DECIMAL(6,4) NOT NULL,   -- γ_Pmax (%/°C, valor negativo)
    noct            DECIMAL(4,1) NOT NULL DEFAULT 45.0,  -- NOCT (°C)
    length_m        DECIMAL(5,3) NOT NULL,   -- Largo del módulo (m)
    width_m         DECIMAL(5,3) NOT NULL,   -- Ancho del módulo (m)
    ...
```

Los parámetros `voc_stc`, `isc_stc`, `vmpp_stc` e `imp_stc` son los valores de la hoja de datos del fabricante medidos bajo Condiciones de Prueba Estándar (STC: 1000 W/m², 25 °C, masa de aire AM 1.5). Los coeficientes de temperatura `temp_coeff_voc` (β) y `temp_coeff_pmax` (γ) son los factores que permiten extrapolar estos parámetros a temperaturas de operación distintas de 25 °C, lo que es fundamental para los cálculos de tensión mínima (día frío) y tensión máxima (día caluroso) en el dimensionamiento de la cadena. El campo `noct` (Temperatura Nominal de Operación de la Celda) es utilizado por el modelo de temperatura de celda del bloque de diseño.

### 3.3.3.4 Tabla `inverters` y `inverter_mppt_groups`

El modelado de los inversores requirió resolver un problema de normalización particular. Un inversor sencillo de entrada única expone todos sus parámetros eléctricos en una sola fila. Sin embargo, los inversores trifásicos de alta potencia para instalaciones industriales y de media escala frecuentemente disponen de dos o más grupos de entradas MPPT con corrientes máximas admisibles distintas entre sí. Si se modelara este tipo de inversor con una sola fila, sería imposible representar fielmente sus capacidades: la tabla `inverters` tendría que contener columnas para un número variable de grupos, o se perdería información técnica relevante para el cálculo de protecciones.

La solución adoptada normaliza la especificación del inversor en **dos tablas relacionadas**: `inverters` contiene los parámetros globales del equipo (potencias, tensiones, rendimiento, ventana de tensión MPPT que es compartida por todas las entradas), mientras que `inverter_mppt_groups` contiene una fila por cada grupo de entradas con idénticas especificaciones de corriente:

```sql
-- Parámetros globales del inversor
CREATE TABLE IF NOT EXISTS inverters (
    ...
    mppt_voltage_min    DECIMAL(6,2) NOT NULL,  -- Shared: igual para todas las entradas
    mppt_voltage_max    DECIMAL(6,2) NOT NULL,
    max_total_strings   TINYINT UNSIGNED NULL DEFAULT NULL,  -- Tope agregado opcional
    ...
);

-- Grupos de entradas MPPT con especificaciones propias de corriente
CREATE TABLE IF NOT EXISTS inverter_mppt_groups (
    inverter_id               INT NOT NULL,
    group_label               VARCHAR(30) NOT NULL,         -- Ej: "A", "B", "Principal"
    mppt_count                TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- Entradas físicas en el grupo
    max_strings_per_mppt      TINYINT UNSIGNED NULL DEFAULT NULL,   -- Tope de hardware o NULL
    max_input_current         DECIMAL(6,2) NOT NULL,
    max_short_circuit_current DECIMAL(6,2) NOT NULL,
    ...
);
```

El campo `mppt_count` indica cuántas entradas físicas comparten las mismas especificaciones de corriente dentro del grupo. Por ejemplo, un inversor con seis entradas MPPT idénticas tendría un único registro en `inverter_mppt_groups` con `mppt_count = 6`, mientras que un inversor con cuatro entradas tipo A y dos entradas tipo B tendría dos registros.

El campo `max_strings_per_mppt` merece una explicación especial. Algunos fabricantes especifican explícitamente en la hoja de datos cuántos strings en paralelo puede aceptar cada entrada MPPT del inversor (por ejemplo, "máximo 2 strings por MPPT"). En ese caso, la columna almacena ese valor. Otros inversores no tienen este límite de hardware explicitado; su capacidad de strings en paralelo está determinada exclusivamente por la corriente máxima de entrada dividida entre la corriente de operación del módulo. Para representar esta distinción, el campo se declara como `TINYINT UNSIGNED NULL DEFAULT NULL`: cuando el valor es `NULL`, significa que no existe un límite de hardware para el número de strings y la capacidad se calcula únicamente a partir de los límites de corriente.

De manera complementaria, la tabla `inverters` incluye la columna opcional `max_total_strings`, que almacena el tope agregado de strings paralelo para todo el inversor cuando el fabricante lo especifica a nivel de equipo (independientemente de la distribución por grupo). Cuando esta columna no es `NULL`, el sistema toma el mínimo entre la capacidad calculada por grupos y este tope agregado.

### 3.3.3.5 Tabla `climatology_locations` y `climatology_monthly`

Estas dos tablas implementan el almacenamiento del caché de datos climatológicos obtenidos de la NASA POWER API. La tabla `climatology_locations` registra cada coordenada geográfica consultada junto con las temperaturas extremas históricas del sitio, que son necesarias para los cálculos de tensión en condiciones de frío y calor extremo:

```sql
CREATE TABLE IF NOT EXISTS climatology_locations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    latitude          DECIMAL(5,2) NOT NULL,
    longitude         DECIMAL(5,2) NOT NULL,
    absolute_min_temp DECIMAL(5,2) NOT NULL,  -- Tmin histórico anual
    absolute_max_temp DECIMAL(5,2) NOT NULL,  -- Tmax histórico anual
    data_source       VARCHAR(50) DEFAULT 'NASA POWER',
    last_updated      DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_coordinates UNIQUE (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

La tabla `climatology_monthly` almacena los doce registros mensuales de datos solares y meteorológicos para cada ubicación. En comparación con esquemas más simples que únicamente guardan la irradiación global horizontal (GHI) y la temperatura, el esquema actual almacena **ocho campos por mes**, cuya inclusión fue determinada por los requerimientos de los modelos físicos de estimación de producción implementados en el front-end:

```sql
CREATE TABLE IF NOT EXISTS climatology_monthly (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    location_id    INT NOT NULL,
    month          TINYINT NOT NULL,           -- 1=enero ... 12=diciembre
    ghi_kwh_m2_day DECIMAL(6,3) NOT NULL,      -- Irradiación global horizontal (kWh/m²/día)
    dni_kwh_m2_day DECIMAL(6,3) NOT NULL DEFAULT 0,  -- Irradiación directa normal (kWh/m²/día)
    dhi_kwh_m2_day DECIMAL(6,3) NOT NULL DEFAULT 0,  -- Irradiación difusa horizontal (kWh/m²/día)
    sun_hours      DECIMAL(4,2) NOT NULL DEFAULT 0,  -- Horas de sol astronómicas
    t2m_avg        DECIMAL(5,2) NOT NULL,      -- Temperatura media mensual a 2 m (°C)
    t2m_max        DECIMAL(5,2) NOT NULL,      -- Temperatura máxima mensual a 2 m (°C)
    t2m_min        DECIMAL(5,2) NOT NULL,      -- Temperatura mínima mensual a 2 m (°C)
    ws10m          DECIMAL(4,2) NOT NULL DEFAULT 0,  -- Velocidad del viento a 10 m (m/s)
    ...
);
```

Los campos `dni_kwh_m2_day` (irradiación directa normal) y `dhi_kwh_m2_day` (irradiación difusa horizontal) son requeridos por el **modelo de transposición de Hay-Davies**, que descompone la irradiación global horizontal en sus componentes directa, difusa y reflejada por el suelo para calcular la irradiación efectiva sobre el plano inclinado del arreglo fotovoltaico. El campo `ws10m` (velocidad del viento a 10 metros de altura) es requerido por el **modelo térmico de Faiman** (IEC 61853-2), que estima la temperatura de operación de la celda fotovoltaica considerando el efecto de enfriamiento convectivo del viento. El campo `sun_hours` (horas de sol astronómicas) es utilizado por la integración de la irradiancia a lo largo del día que requiere el modelo Faiman ponderado por irradiancia. La inclusión de estos campos en la base de datos, y no sólo en el caché temporal del navegador, permite que consultas posteriores sobre la misma ubicación retornen los datos completos directamente sin necesidad de llamar nuevamente a la API de la NASA.

### 3.3.3.6 Modelos de Dominio

Sobre el esquema de base de datos se construye una capa de **modelos de dominio**: clases PHP que actúan como contenedores de datos tipados para cada entidad de negocio. El sistema define cuatro modelos: `Manufacturer`, `PVModule`, `MpptGroup` e `Inverter`. La decisión de introducir esta capa, en lugar de trabajar directamente con los arreglos asociativos retornados por PDO, responde a varias necesidades: tipado estático verificable por el motor de PHP y por herramientas de análisis estático, prevención de acceso a propiedades con nombres incorrectos (un error tipográfico en `$mod['pmax_stc']` pasa desapercibido con arreglos; con un objeto tipeado, la propiedad `$mod->pmaxStc` es detectada por el IDE), e inmutabilidad de los datos recuperados de la base de datos.

Todos los modelos emplean la sintaxis de propiedades del constructor con el modificador `readonly` introducida en PHP 8.1. Una propiedad `readonly` sólo puede ser asignada una única vez, durante la construcción del objeto, y cualquier intento posterior de modificarla lanza un error fatal. Esto garantiza que un objeto de modelo recuperado de la base de datos no puede ser modificado accidentalmente por ninguna parte del sistema:

```php
class PVModule
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $manufacturerId,
        public readonly string $manufacturer,
        public readonly string $model,
        public readonly string $technology,
        public readonly float  $pmaxStc,
        public readonly float  $vocStc,
        public readonly float  $iscStc,
        public readonly float  $vmppStc,
        public readonly float  $impStc,
        public readonly float  $tempCoeffVoc,
        public readonly float  $tempCoeffPmax,
        public readonly float  $noct,
        public readonly float  $lengthM,
        public readonly float  $widthM,
        public readonly string $createdAt,
    ) {}
}
```

El modelo `MpptGroup` representa un grupo de entradas MPPT del inversor. La propiedad `maxStringsPerMppt` se declara como `?int` (entero nullable), reflejando exactamente la semántica del campo `NULL` en la base de datos:

```php
class MpptGroup
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $inverterId,
        public readonly string $label,
        public readonly int    $mpptCount,
        public readonly ?int   $maxStringsPerMppt,      // NULL = sin límite de hardware
        public readonly float  $maxInputCurrent,
        public readonly float  $maxShortCircuitCurrent,
    ) {}
}
```

El modelo `Inverter` es el más complejo porque incorpora un arreglo de objetos `MpptGroup` como una propiedad composita, además del campo opcional `maxTotalStrings`. Expone dos métodos derivados que encapsulan lógica de negocio que depende exclusivamente de los datos del propio inversor:

```php
/** Suma de mppt_count en todos los grupos → total de entradas físicas MPPT */
public function totalMpptCount(): int
{
    return (int) array_sum(
        array_map(fn(MpptGroup $g) => $g->mpptCount, $this->mpptGroups)
    );
}

/**
 * Máximo total de strings en paralelo que puede aceptar este inversor,
 * considerando topes por grupo y el tope agregado del inversor si existe.
 */
public function totalMaxStrings(): int
{
    $fromGroups = (int) array_sum(
        array_map(
            fn(MpptGroup $g) => $g->mpptCount * ($g->maxStringsPerMppt ?? PHP_INT_MAX),
            $this->mpptGroups
        )
    );
    if ($this->maxTotalStrings !== null) {
        return min($fromGroups, $this->maxTotalStrings);
    }
    return $fromGroups;
}
```

En `totalMaxStrings()`, la expresión `$g->maxStringsPerMppt ?? PHP_INT_MAX` maneja el caso de campos `NULL` de forma elegante: cuando no hay límite de hardware por entrada, se utiliza el entero máximo representable por PHP como sustituto de infinito, de modo que la suma resultante quede determinada exclusivamente por el tope agregado `maxTotalStrings` cuando éste existe.

Cada modelo implementa también los métodos estáticos y de instancia para la conversión entre representaciones:

- `fromArray(array $row): static` — construye el objeto a partir del arreglo asociativo retornado por PDO, aplicando castings explícitos (`(int)`, `(float)`, `(string)`) a cada campo para garantizar los tipos correctos aunque MySQL retorne temporalmente los valores como cadenas de texto.
- `toArray(): array` — serializa el objeto a un arreglo con claves en `snake_case` listo para ser codificado como JSON y enviado al cliente.

---

## 3.3.4 Capa de Repositorios

El **patrón repositorio** es un patrón de diseño estructural que establece una abstracción entre la lógica de negocio y la capa de persistencia de datos. Conceptualmente, un repositorio simula una colección en memoria de objetos de dominio; el código que lo utiliza no necesita saber si los datos provienen de una base de datos relacional, un servicio web externo o un archivo en disco. La implementación concreta de esa colección —el SQL específico, los parámetros de paginación, las restricciones de orden— queda completamente encapsulada dentro del repositorio.

En el presente sistema, este patrón tiene una consecuencia arquitectónica fundamental: **todo el código SQL vive exclusivamente en los repositorios**. Ningún controlador, servicio ni modelo ejecuta consultas SQL directamente. Esto garantiza que si en el futuro fuera necesario modificar una consulta (por ejemplo, para optimizar el rendimiento o agregar una columna nueva), el cambio se realiza en un único lugar y no está disperso por múltiples archivos.

El sistema implementa tres repositorios: `ManufacturerRepository`, `PVModuleRepository` e `InverterRepository`. Cada uno recibe la conexión PDO como parámetro del constructor —un ejemplo de **inyección de dependencias** (DI, _Dependency Injection_)—, lo que permite sustituir fácilmente la conexión real por una de pruebas sin modificar la clase.

### 3.3.4.1 `PVModuleRepository` — Estructura y Métodos

El repositorio de módulos fotovoltaicos expone seis métodos públicos que cubren el ciclo de vida completo del catálogo de módulos:

| Método | Firma | Propósito |
|--------|-------|-----------|
| `count` | `count(string $q = ''): int` | Cuenta el total de módulos que coinciden con el término de búsqueda. Utilizado por el componente de paginación del inventario. |
| `findAll` | `findAll(int $limit, int $offset, string $q = ''): PVModule[]` | Lista paginada con búsqueda sobre el modelo y el nombre del fabricante. |
| `findAllForCalculator` | `findAllForCalculator(): PVModule[]` | Lista completa sin paginación, ordenada para la calculadora. No acepta parámetros de búsqueda porque el front-end filtra localmente. |
| `findById` | `findById(int $id): ?PVModule` | Recupera un módulo específico por su clave primaria. Retorna `null` si no existe. |
| `save` | `save(PVModule $module): void` | Inserta un módulo nuevo si `id === 0`, o actualiza el existente si `id > 0`. |
| `delete` | `delete(int $id): void` | Elimina un módulo por su clave primaria. |

La consulta `findAllForCalculator()` realiza un `JOIN` con la tabla `manufacturers` para obtener el nombre del fabricante en una sola operación, evitando así consultas adicionales por cada módulo:

```php
public function findAllForCalculator(): array
{
    $stmt = $this->pdo->prepare(
        "SELECT m.id, mf.name AS manufacturer, m.manufacturer_id, m.model,
                m.technology, m.pmax_stc, m.voc_stc, m.isc_stc, m.vmpp_stc, m.imp_stc,
                m.temp_coeff_voc, m.temp_coeff_pmax, m.noct, m.length_m, m.width_m,
                DATE_FORMAT(m.created_at, '%d/%m/%Y') AS created_at
         FROM pv_modules m
         JOIN manufacturers mf ON m.manufacturer_id = mf.id
         ORDER BY mf.name, m.pmax_stc"
    );
    $stmt->execute();

    return array_map(
        fn(array $row) => PVModule::fromArray($row),
        $stmt->fetchAll()
    );
}
```

El resultado de `fetchAll()` —un arreglo de arreglos asociativos PHP— se transforma inmediatamente en un arreglo de objetos `PVModule` mediante `array_map` y el método estático de hidratación `PVModule::fromArray()`. De esta forma, ningún consumidor del repositorio manipula arreglos crudos: siempre recibe objetos del dominio con sus propiedades tipadas.

### 3.3.4.2 Prevención de Inyección SQL mediante Consultas Preparadas

Todas las consultas del sistema utilizan **sentencias preparadas** con parámetros nombrados. Esto es la primera línea de defensa técnica contra ataques de inyección SQL (OWASP Top 10, A03:2021 — Injection). En una consulta preparada, el texto SQL y los datos proporcionados por el usuario se envían al servidor de base de datos como dos elementos separados; el servidor nunca interpreta los datos como parte de la sintaxis SQL, por lo que no es posible que un valor malicioso como `'; DROP TABLE pv_modules; --` sea ejecutado.

Una situación técnica particular surge en las consultas de búsqueda con `LIKE`, donde el mismo término de búsqueda debe aparecer en dos condiciones (una para el modelo, otra para el fabricante). Con `EMULATE_PREPARES => false`, PDO no permite que el mismo nombre de parámetro sea utilizado más de una vez en la misma sentencia preparada. La solución es nombrar los parámetros de forma distinta aunque contengan el mismo valor en tiempo de ejecución:

```php
$stmt = $this->pdo->prepare(
    'SELECT COUNT(*)
     FROM pv_modules m
     JOIN manufacturers mf ON m.manufacturer_id = mf.id
     WHERE m.model LIKE :q1 OR mf.name LIKE :q2'
);
$stmt->execute([':q1' => '%' . $q . '%', ':q2' => '%' . $q . '%']);
```

Para las cláusulas `LIMIT` y `OFFSET` de la paginación se utiliza `bindValue()` en lugar de incluirlos en el arreglo `execute()`, acompañado del tipo explícito `PDO::PARAM_INT`. La razón es que MySQL requiere que los operandos de `LIMIT` y `OFFSET` sean valores enteros literales; con la emulación de consultas preparadas desactivada, pasar estos parámetros como cadenas de texto (el tipo por defecto de PDO) provoca un error de sintaxis en el servidor:

```php
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
```

### 3.3.4.3 Validación de Dominio en el Método `save()`

El método `save()` de `PVModuleRepository` incorpora un nivel de validación de dominio que va más allá de simplemente ejecutar una sentencia SQL. Los coeficientes de temperatura de los módulos fotovoltaicos (`temp_coeff_voc` y `temp_coeff_pmax`) son cantidades físicas que por definición son **siempre negativas**: expresan cuánto disminuye la tensión de circuito abierto o la potencia máxima del módulo por cada grado Celsius de aumento de temperatura. Un valor positivo o cero indicaría que la potencia del módulo aumenta con la temperatura, lo cual es físicamente imposible para la tecnología de silicio. Si este dato incorrecto se almacenara en la base de datos, los cálculos de tensión máxima (en condición de temperatura fría) producirían valores erróneos que podrían resultar en un dimensionamiento de la cadena inseguro.

Por esta razón, el repositorio verifica los signos antes de ejecutar la sentencia SQL y lanza una excepción descriptiva si los valores son incorrectos:

```php
public function save(PVModule $module): void
{
    if ($module->tempCoeffVoc >= 0) {
        throw new \InvalidArgumentException(
            'El coeficiente de temperatura de Voc (β Voc) debe ser negativo.'
        );
    }
    if ($module->tempCoeffPmax >= 0) {
        throw new \InvalidArgumentException(
            'El coeficiente de temperatura de Pmax (β Pmax) debe ser negativo.'
        );
    }
    // ... INSERT o UPDATE según $module->id
}
```

El patrón de upsert condicional (`UPDATE` si `id > 0`, `INSERT` si `id === 0`) evita la necesidad de tener métodos separados `insert()` y `update()` y simplifica la interfaz del repositorio para el controlador.

### 3.3.4.4 `InverterRepository` — Carga Eficiente y Atomicidad Transaccional

El repositorio de inversores incorpora dos patrones adicionales exigidos por la naturaleza de la relación uno-a-muchos entre `inverters` e `inverter_mppt_groups`.

**Prevención del Problema N+1 con Carga en Una Consulta**

El problema N+1 es un antipatrón clásico en el acceso a bases de datos relacionales que ocurre cuando se recuperan N registros de una tabla y luego se ejecuta una consulta adicional para cada uno de ellos para obtener datos relacionados, resultando en N+1 consultas en total. En este sistema, si al cargar una lista de 50 inversores se lanzara una consulta separada para los grupos MPPT de cada uno, se ejecutarían 51 consultas SQL para producir un único resultado.

Para evitar esto, el método privado `loadGroupsForInverters(array $ids)` carga los grupos de un conjunto arbitrario de inversores en **una única consulta SQL** que utiliza la cláusula `IN` con marcadores de posición dinámicos:

```php
private function loadGroupsForInverters(array $ids): array
{
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $this->pdo->prepare(
        "SELECT id, inverter_id, group_label, mppt_count,
                max_strings_per_mppt, max_input_current, max_short_circuit_current
         FROM inverter_mppt_groups
         WHERE inverter_id IN ($placeholders)
         ORDER BY inverter_id, id"
    );
    $stmt->execute($ids);

    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['inverter_id']][] = MpptGroup::fromArray($row);
    }
    return $result;
}
```

El método construye un arreglo asociativo donde las claves son los IDs de los inversores y los valores son arreglos de objetos `MpptGroup`. Cada método de lectura del repositorio utiliza este mapa para pasar los grupos al constructor de `Inverter::fromArray($row, $groups)`, resultando en exactamente dos consultas SQL para cargar cualquier cantidad de inversores: una para la tabla `inverters` y una para `inverter_mppt_groups`.

**Escritura Atómica con Transacciones de Base de Datos**

La persistencia de un inversor no es una operación simple: implica escribir en dos tablas distintas. Si se insertan los datos del inversor en la tabla `inverters` pero la inserción de los grupos en `inverter_mppt_groups` falla a mitad de camino (por ejemplo, por un error de red o un valor que viola una restricción de integridad), la base de datos quedaría en un estado inconsistente: un inversor sin grupos MPPT, que causaría errores de cálculo en la calculadora.

Las **transacciones de base de datos** son el mecanismo estándar para garantizar que un conjunto de operaciones se ejecute de forma atómica: o todas se completan correctamente, o ninguna. El método `save()` de `InverterRepository` envuelve toda la escritura en una transacción, y el método auxiliar privado `replaceGroups()` implementa una estrategia _delete-then-insert_ que maneja de forma uniforme tanto la creación de nuevos grupos como la actualización de los existentes, sin necesidad de comparar el estado anterior con el nuevo:

```php
public function save(Inverter $inverter): void
{
    $this->pdo->beginTransaction();

    try {
        if ($inverter->id > 0) {
            // UPDATE de la fila del inversor
            $this->updateInverterRow($inverter);
            $inverterId = $inverter->id;
        } else {
            // INSERT de la fila del inversor
            $this->insertInverterRow($inverter);
            $inverterId = (int)$this->pdo->lastInsertId();
        }

        $this->replaceGroups($inverterId, $inverter->mpptGroups);
        $this->pdo->commit();

    } catch (\Throwable $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}
```

Si cualquier operación dentro del bloque `try` lanza una excepción, el bloque `catch` llama a `rollBack()`, deshaciendo todas las escrituras realizadas hasta ese punto, y relanza la excepción para que el controlador pueda informar al cliente del error. Al eliminar un inversor, la restricción `ON DELETE CASCADE` de la tabla `inverter_mppt_groups` elimina automáticamente todos sus grupos sin requerir código adicional.

---

## 3.3.5 Capa de Servicios

La capa de servicios aloja la **lógica de negocio** de la aplicación: las operaciones que no son simplemente recuperación o persistencia de datos, sino que involucran procesos más complejos como llamadas a sistemas externos, cálculos multietapa, o transformaciones que requieren conocimiento del dominio. El sistema implementa dos servicios: `NasaService`, responsable de obtener y almacenar datos climatológicos, y `ExportService`, responsable de generar el archivo de exportación `.xlsx`.

### 3.3.5.1 `NasaService` — Integración con la API NASA POWER y Caché

La API NASA POWER (Prediction Of Worldwide Energy Resource) es un servicio web público de la NASA que proporciona datos climatológicos históricos derivados de modelos satelitales y reanálisis atmosférico para cualquier coordenada geográfica del planeta. Los datos disponibles incluyen irradiación solar, temperatura del aire, velocidad del viento y decenas de otros parámetros. Para el presente sistema se eligió esta fuente por ser de acceso público y gratuito, con cobertura global y resolución temporal mensual suficiente para dimensionamiento fotovoltaico de primer nivel.

La clase `NasaService` es el único componente del sistema que realiza llamadas HTTP a un servicio externo. Esta responsabilidad está deliberadamente aislada en un único lugar: si la NASA cambia el formato de su API o su URL en el futuro, el único archivo que requiere modificación es este servicio.

**Parámetros Solicitados a la API**

La versión actual del servicio solicita **siete parámetros** climatológicos en cada consulta a la API:

```
ALLSKY_SFC_SW_DWN   → Irradiación global horizontal (GHI) promedio mensual, kWh/m²/día
ALLSKY_SFC_SW_DNI   → Irradiación directa normal (DNI) promedio mensual, kWh/m²/día
ALLSKY_SFC_SW_DIFF  → Irradiación difusa horizontal (DHI) promedio mensual, kWh/m²/día
T2M                 → Temperatura media del aire a 2 m, °C
T2M_MAX             → Temperatura máxima mensual del aire a 2 m, °C
T2M_MIN             → Temperatura mínima mensual del aire a 2 m, °C
WS10M               → Velocidad del viento a 10 m de altura, m/s
```

La URL de consulta se construye mediante `sprintf()` en el método privado `fetchFromNasa()`:

```php
$url = sprintf(
    'https://power.larc.nasa.gov/api/temporal/climatology/point'
    . '?parameters=ALLSKY_SFC_SW_DWN,ALLSKY_SFC_SW_DNI,ALLSKY_SFC_SW_DIFF,T2M,T2M_MAX,T2M_MIN,WS10M'
    . '&community=RE&latitude=%s&longitude=%s&format=JSON',
    $lat,
    $lng
);
```

La comunidad `RE` (Renewable Energy) selecciona el subconjunto de parámetros más adecuado para aplicaciones de energía solar. La llamada HTTP se realiza mediante `file_get_contents()` con un contexto de stream que establece un tiempo de espera máximo de 20 segundos, después del cual la función retorna `false`. Si la llamada falla, se lanza una `RuntimeException` con un mensaje en español que el controlador transformará en una respuesta HTTP 502 (Bad Gateway). La validación de la respuesta comprueba que los siete parámetros esperados estén presentes en la estructura JSON antes de procesar los datos, protegiéndose ante cambios inesperados en el formato de la API:

```php
if (
    !$params
    || !isset(
        $params['ALLSKY_SFC_SW_DWN'], $params['ALLSKY_SFC_SW_DNI'],
        $params['ALLSKY_SFC_SW_DIFF'], $params['T2M'],
        $params['T2M_MAX'], $params['T2M_MIN'], $params['WS10M']
    )
) {
    throw new RuntimeException('Respuesta inesperada de NASA POWER.');
}
```

**Cálculo de Horas de Sol Astronómicas**

Adicionalmente a los datos obtenidos de la NASA, el servicio calcula las **horas de sol astronómicas** para cada mes mediante el método privado `computeSunHours()`. Este valor no está disponible directamente de la API, pero es necesario para la integración de la irradiancia solar a lo largo del día en el modelo Faiman. El cálculo se basa en la ecuación de Cooper para la declinación solar y el ángulo horario de puesta del sol:

```
δ = 23.45 · sin(360/365 · (284 + n))   [grados, ecuación de Cooper]
cos(ωs) = -tan(φ) · tan(δ)
N = 2·ωs / 15                           [horas de sol astronómicas]
```

donde `n` es el día representativo del mes (día central del mes según Klein, 1977) y `φ` es la latitud del sitio. El método maneja correctamente los casos extremos de sol de medianoche (latitudes polares en verano, donde `cos(ωs) < −1`) y noche polar (latitudes polares en invierno, donde `cos(ωs) > 1`).

**El Patrón Caché-Aside (_Cache-Aside Pattern_)**

Consultar la API de la NASA por cada solicitud del usuario tendría un costo significativo en latencia: la respuesta de la API demora típicamente entre 2 y 5 segundos. Para mitigar este problema se implementa el patrón **caché-aside**: antes de contactar la API, el servicio verifica si los datos para las coordenadas solicitadas ya existen en la base de datos local. Si existen, se retornan directamente con una marca `"source": "cache"`; si no, se contacta la API, se persisten los datos en la base de datos y se retornan con la marca `"source": "api"`. Las solicitudes subsecuentes para la misma ubicación quedan atendidas en microsegundos desde la base de datos local.

El redondeo de coordenadas a dos decimales, aplicado al inicio del método público `getClimateData()`, garantiza la consistencia de la búsqueda en caché con la restricción `UNIQUE (latitude, longitude)` de la tabla, de forma que dos puntos con coordenadas prácticamente idénticas (por ejemplo, derivadas de distintos movimientos del marcador en el mapa) obtendrán los mismos datos climatológicos almacenados:

```php
public function getClimateData(float $lat, float $lng): array
{
    $lat = round($lat, 2);
    $lng = round($lng, 2);

    $cached = $this->findCachedLocation($lat, $lng);

    if ($cached !== null) {
        return [
            'source'  => 'cache',
            'lat'     => $lat,
            'lng'     => $lng,
            'tmin'    => (float) $cached['absolute_min_temp'],
            'tmax'    => (float) $cached['absolute_max_temp'],
            'monthly' => $this->fetchMonthly($cached['id']),
        ];
    }

    $monthly  = $this->fetchFromNasa($lat, $lng);
    $absTmin  = (float) min(array_column($monthly, 't2m_min'));
    $absTmax  = (float) max(array_column($monthly, 't2m_max'));

    $this->persist($lat, $lng, $absTmin, $absTmax, $monthly);

    return [
        'source'  => 'api',
        'lat'     => $lat,
        'lng'     => $lng,
        'tmin'    => $absTmin,
        'tmax'    => $absTmax,
        'monthly' => $monthly,
    ];
}
```

Las temperaturas extremas `tmin` y `tmax` no se solicitan directamente a la API; se derivan como el mínimo y el máximo, respectivamente, de los valores `t2m_min` y `t2m_max` de los doce meses. Este enfoque es consistente con la definición de temperatura de diseño utilizada en la NOM-001-SEDE-2012 para el dimensionamiento de la instalación fotovoltaica.

**Estructura de los Datos Mensuales**

Cada uno de los doce elementos del arreglo `monthly` retornado por el servicio contiene los siguientes campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `month` | int | Número de mes (1–12) |
| `ghi` | float | Irradiación global horizontal (kWh/m²/día) |
| `dni` | float | Irradiación directa normal (kWh/m²/día) |
| `dhi` | float | Irradiación difusa horizontal (kWh/m²/día) |
| `sun_hours` | float | Horas de sol astronómicas calculadas |
| `t2m_avg` | float | Temperatura media mensual (°C) |
| `t2m_max` | float | Temperatura máxima mensual (°C) |
| `t2m_min` | float | Temperatura mínima mensual (°C) |
| `ws10m` | float | Velocidad del viento a 10 m (m/s) |

Los campos `dni` y `dhi` son los componentes que requiere el modelo de transposición de Hay-Davies para convertir la irradiación en el plano horizontal en irradiación en el plano inclinado del arreglo fotovoltaico (POA, _Plane Of Array_). El campo `ws10m` es el que requiere el modelo térmico de Faiman para estimar el efecto de enfriamiento convectivo del viento sobre la temperatura de la celda. El campo `sun_hours` es necesario para integrar el perfil de irradiancia sinusoidal a lo largo del día en ese modelo.

**Persistencia Transaccional**

El método privado `persist()` almacena la nueva ubicación y sus doce registros mensuales en la base de datos dentro de una transacción, garantizando atomicidad: si la inserción de algún registro mensual falla, la inserción de la ubicación también se revierte:

```php
$stmt = $this->pdo->prepare(
    'INSERT INTO climatology_monthly
        (location_id, month, ghi_kwh_m2_day, dni_kwh_m2_day, dhi_kwh_m2_day,
         sun_hours, t2m_avg, t2m_max, t2m_min, ws10m)
     VALUES (:loc, :month, :ghi, :dni, :dhi, :sun, :avg, :max, :min, :ws10m)'
);
foreach ($monthly as $row) {
    $stmt->execute([...]);
}
```

La sentencia preparada se compila una sola vez y se ejecuta doce veces con distintos parámetros, aprovechando la optimización de plan de ejecución reutilizable que ofrecen las consultas preparadas reales.

### 3.3.5.2 `ExportService` — Generación del Archivo Excel

`ExportService` es el componente responsable de transformar el resultado completo del proceso de diseño fotovoltaico en un archivo `.xlsx` descargable. El servicio utiliza la biblioteca PHP **PhpSpreadsheet** versión 1.x, una alternativa de código abierto a PHPExcel que implementa la especificación Office Open XML (ISO/IEC 29500). PhpSpreadsheet permite crear libros de trabajo, dar formato a celdas (fuentes, colores, bordes, alineación), combinar celdas y exportar el resultado como binario XLSX sin depender de ninguna extensión de PHP más allá del núcleo.

El método público `build(array $payload): string` es el único punto de entrada del servicio. Recibe el arreglo PHP con todos los valores del diseño ya calculados por el cliente (la calculadora JavaScript), construye el libro de trabajo y retorna el binario del archivo `.xlsx` como una cadena de texto PHP. La captura del binario se realiza mediante el patrón de buffer de salida:

```php
public function build(array $payload): string
{
    $this->row   = 1;
    $spreadsheet = new Spreadsheet();
    // ... configuración del libro y construcción de la hoja ...
    $writer = new Xlsx($spreadsheet);
    ob_start();
    $writer->save('php://output');
    return (string) ob_get_clean();
}
```

`ob_start()` abre un buffer de salida que captura todo lo que `$writer->save()` escribe al flujo de salida estándar `php://output`. `ob_get_clean()` recupera el contenido del buffer y lo cierra. La cadena resultante es el binario puro del archivo XLSX, que el endpoint `export_excel.php` enviará al navegador con los encabezados HTTP apropiados para forzar la descarga.

**Estructura del Libro de Trabajo**

A diferencia de versiones anteriores del servicio que generaban dos hojas separadas, el libro actual contiene **una única hoja denominada "Resumen"**. La decisión de consolidar en una sola hoja responde a criterios de usabilidad: un documento con una sola hoja es más navegable y permite imprimir o compartir el resultado como un único documento coherente. La tabla de producción mensual, que anteriormente ocupaba su propia hoja, se inserta **de forma alineada** a la derecha de la sección de Estimación Energética dentro de la misma hoja, mediante el método privado `addMonthlyTableInline()`. Esta tabla aparece condicionalmente: sólo si el payload contiene los doce registros mensuales de datos de la NASA.

La hoja se organiza en siete secciones secuenciales, cada una encabezada por una fila de color que sirve como título visual de sección:

| # | Sección | Clave del payload |
|---|---------|-------------------|
| 1 | Ubicación y Diseño | `site` |
| 2 | Módulo FV | `module` |
| 3 | Inversor | `inverter` |
| 4 | Configuración de la Planta | `array` |
| 5 | Verificaciones de Compatibilidad (NOM-001-SEDE-2012) | `checks` |
| 6 | Estimación Energética | `energy` |
| 7 | Protecciones Eléctricas | `protection` |

**Paleta de Colores como Constantes de Clase**

Todos los colores utilizados en el libro se definen como constantes privadas de clase en formato ARGB (Alpha-Red-Green-Blue de 8 bits cada componente) al inicio de la clase, siguiendo el principio de no tener "números mágicos" dispersos en el código. Por ejemplo:

```php
private const C_TITLE_BG   = 'FF1D4ED8';  // Azul institucional para encabezados de sección
private const C_TITLE_FG   = 'FFFFFFFF';  // Texto blanco
private const C_PASS_BG    = 'FFBBF7D0';  // Verde claro para verificaciones aprobadas
private const C_PASS_FG    = 'FF15803D';  // Verde oscuro
private const C_FAIL_BG    = 'FFFECACA';  // Rojo claro para verificaciones reprobadas
private const C_FAIL_FG    = 'FFB91C1C';  // Rojo oscuro
```

**Sección de Configuración del Arreglo y Ocupación MPPT**

La sección "Configuración de la Planta" incluye, además de los parámetros eléctricos del arreglo, un análisis de la distribución de strings entre los inversores y su ocupación de entradas MPPT. Este análisis calcula cuántos inversores recibirán el número mayor de strings (⌈Np/N_inv⌉) y cuántos recibirán el menor (⌊Np/N_inv⌋), e indica si algún inversor operará con entradas MPPT sin utilizar, lo cual es relevante para la eficiencia de la instalación.

**Sección de Estimación Energética y Pérdidas Desglosadas**

La sección de producción energética refleja en el documento los factores de pérdida individuales calculados por el módulo de estimación del front-end. Las categorías de pérdidas documentadas son las siguientes:

| Clave del payload | Etiqueta en el Excel |
|-------------------|----------------------|
| `soiling` | Suciedad |
| `mismatch` | Desajuste |
| `dc_wiring` | Cableado DC |
| `inverter` | Conversión inversor |
| `ac_wiring` | Cableado AC |
| `lid` | Degradación inicial (LID) |

La sección también incluye los parámetros de orientación del arreglo (`tilt`, `azimuth`, `albedo`) y el NOCT del módulo, que son los parámetros de entrada del modelo de producción.

**Sección de Protecciones Eléctricas**

La sección de protecciones expone todos los escenarios de distribución de strings en el circuito DC. Cuando el parámetro `derating_on` del payload es verdadero, se inserta una fila adicional que documenta el factor de corrección por temperatura aplicado, con referencia a la tabla 310.15(B)(2)(a) de la NOM-001-SEDE-2012.

---

## 3.3.6 Capa de Controladores

Los controladores son la capa que conecta los puntos de entrada HTTP con los servicios y repositorios de la aplicación. Su responsabilidad es doble: validar y sanitizar la entrada del cliente antes de pasarla a la capa de lógica de negocio, y coordinar la respuesta —ya sea un resultado exitoso o un mensaje de error con el código HTTP apropiado. Lo que los controladores **no** hacen es igualmente importante: no ejecutan SQL, no llaman servicios externos, no formatean el HTML ni construyen la respuesta JSON directamente. Son coordinadores, no ejecutores.

El sistema implementa dos controladores: `CalculadoraApiController`, responsable de servir los datos necesarios para el módulo de la calculadora fotovoltaica, e `InventarioApiController`, responsable del módulo de gestión del catálogo de equipos.

### 3.3.6.1 `CalculadoraApiController` — Inyección de Dependencias

`CalculadoraApiController` recibe sus dependencias a través del constructor, un patrón denominado **inyección de dependencias por constructor** (_Constructor Injection_). Esta técnica tiene varias ventajas: hace explícitas las dependencias de la clase (cualquier desarrollador que lea el constructor sabe exactamente qué servicios y repositorios necesita), facilita las pruebas unitarias (se pueden pasar implementaciones falsas o "mocks" de las dependencias sin modificar el código del controlador), y evita el acoplamiento estático a clases concretas:

```php
class CalculadoraApiController
{
    public function __construct(
        private readonly NasaService         $nasa,
        private readonly PVModuleRepository  $modules,
        private readonly InverterRepository  $inverters,
    ) {}
}
```

El controlador expone tres métodos públicos que corresponden exactamente a las tres acciones del endpoint:

**`getClimateData(array $body): array`** — valida que los parámetros `lat` y `lng` sean coordenadas geográficas válidas (latitud en el rango −90 a 90, longitud en el rango −180 a 180) y que ambas estén presentes en el cuerpo de la petición. Si la validación falla, retorna inmediatamente un arreglo de error con la clave especial `__status => 400` sin llamar al servicio NASA. Si la validación pasa, delega la operación completa a `NasaService::getClimateData()` y captura cualquier `RuntimeException` que el servicio pueda lanzar (por ejemplo, si la API de la NASA no está disponible), convirtiéndola en una respuesta HTTP 502 (Bad Gateway):

```php
public function getClimateData(array $body): array
{
    $lat = isset($body['lat']) ? (float) $body['lat'] : null;
    $lng = isset($body['lng']) ? (float) $body['lng'] : null;

    if (
        $lat === null || $lng === null
        || $lat < -90  || $lat > 90
        || $lng < -180 || $lng > 180
    ) {
        return ['error' => 'Coordenadas inválidas.', '__status' => 400];
    }

    try {
        return $this->nasa->getClimateData($lat, $lng);
    } catch (RuntimeException $e) {
        return ['error' => $e->getMessage(), '__status' => 502];
    }
}
```

**`getPVModules(): array`** y **`getInverters(): array`** — delegan directamente a sus repositorios respectivos, transformando el arreglo de objetos de dominio en un arreglo de arreglos asociativos mediante `array_map` y el método `toArray()` de cada modelo, listo para ser serializado como JSON.

### 3.3.6.2 La Convención `__status` para Códigos HTTP

Una de las decisiones de diseño más visibles de los controladores es que **no emiten respuestas HTTP directamente**. Los controladores no llaman a `http_response_code()` ni a `header()`. En su lugar, cuando necesitan señalizar un código de estado HTTP diferente de 200, incluyen en el arreglo retornado la clave especial `__status` con el código numérico deseado.

Esta convención tiene una ventaja arquitectónica: mantiene a los controladores completamente ajenos al protocolo HTTP. Un controlador podría ser invocado también desde un script de línea de comandos o desde una prueba unitaria, y el resultado sería el mismo arreglo PHP interpretable en cualquier contexto. El único punto que traduce esa clave a una llamada HTTP real es el punto de entrada `calculadora.php`:

```php
if (isset($result['__status'])) {
    http_response_code($result['__status']);
    unset($result['__status']);
}
echo json_encode($result);
```

La clave `__status` se elimina del arreglo antes de serializar, de forma que no aparece en el cuerpo JSON de la respuesta y el cliente sólo la percibe como el código de estado HTTP.

---

## 3.3.7 Punto de Entrada HTTP — `api/calculadora.php`

El archivo `src/api/calculadora.php` es el **único punto de entrada** del módulo calculadora que conoce el protocolo HTTP. Su estructura es deliberadamente lineal y se puede dividir en seis bloques de responsabilidad bien delimitados, cada uno con su propósito claramente identificable:

**Bloque 1 — Verificación de Autenticación**

La primera instrucción ejecutable del archivo, antes de cualquier otra operación, es la verificación de la sesión del usuario:

```php
AuthGuard::requireApi();
```

Esta llamada verifica la existencia de una sesión PHP válida con el usuario autenticado. Si no existe, `AuthGuard::requireApi()` emite inmediatamente un código HTTP 401 con el cuerpo `{"error":"Unauthenticated"}` y termina la ejecución. Al colocar esta verificación en la primera línea, se garantiza que ningún código posterior —ni el bootstrap, ni el cableado de dependencias, ni ninguna lógica de negocio— se ejecuta para un usuario no autenticado.

**Bloque 2 — Bootstrap**

El bloque de inicialización activa el buffer de salida con `ob_start()`, silencia los errores de PHP para evitar que mensajes inesperados contaminen la respuesta JSON, y establece el tipo de contenido de la respuesta:

```php
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json');
```

El buffer de salida (`ob_start`) es una medida de seguridad adicional: si alguna parte del código de terceros (como PhpSpreadsheet o el autoloader de Composer) imprimiera algo inesperado —un aviso de PHP, un mensaje de depuración— antes de la respuesta JSON, ese contenido quedaría capturado en el buffer y sería descartado por el `ob_clean()` final, en lugar de corromper el JSON.

**Bloque 3 — Cableado de Dependencias**

En este bloque se obtiene la instancia PDO compartida del Singleton `Database` y se construye el controlador con todas sus dependencias:

```php
$pdo        = Database::getInstance()->getPdo();
$controller = new CalculadoraApiController(
    new NasaService($pdo),
    new PVModuleRepository($pdo),
    new InverterRepository($pdo),
);
```

Nótese que la instancia PDO se obtiene una única vez y se pasa a los tres objetos que la necesitan. Debido al patrón Singleton de `Database`, aunque `getInstance()` se llamara múltiples veces, siempre retornaría la misma conexión. La construcción explícita del grafo de dependencias en el punto de entrada —en lugar de usar un contenedor de inyección de dependencias (IoC Container)— es una decisión consciente de mantener la simplicidad del sistema: para este nivel de complejidad, el cableado manual es perfectamente legible y no justifica la introducción de una biblioteca adicional.

**Bloque 4 — Parseo de la Solicitud**

```php
$action = $_GET['action'] ?? '';
$body   = (array) (json_decode(file_get_contents('php://input'), true) ?? $_POST);
```

La acción solicitada se lee de los parámetros de la URL (`$_GET`). El cuerpo de la solicitud se intenta parsear como JSON desde el flujo de entrada estándar de PHP (`php://input`), que contiene el cuerpo crudo de la petición HTTP sin procesar. Si la decodificación JSON falla o el cuerpo está vacío, se utiliza `$_POST` como alternativa para compatibilidad con clientes que envíen datos como `application/x-www-form-urlencoded`. El resultado siempre se fuerza a un arreglo con el cast `(array)` para garantizar que `$body` sea siempre un arreglo válido.

**Bloque 5 — Enrutamiento con `match`**

```php
$result = match ($action) {
    'get_climate_data' => $controller->getClimateData($body),
    'get_pv_modules'   => $controller->getPVModules(),
    'get_inverters'    => $controller->getInverters(),
    default            => ['error' => 'Unknown action', '__status' => 400],
};
```

La expresión `match` de PHP 8 es la herramienta idónea para el enrutamiento simple: a diferencia de `switch`, realiza comparaciones estrictas (sin coerción de tipo), garantiza que sólo un brazo se ejecute y, siendo una expresión (no una sentencia), retorna directamente el resultado al arreglo `$result`. La acción `default` maneja cualquier solicitud con un nombre de acción desconocido, retornando un error 400 en lugar de silenciar el error o ejecutar código inesperado.

**Bloque 6 — Respuesta HTTP**

```php
if (isset($result['__status'])) {
    http_response_code($result['__status']);
    unset($result['__status']);
}
ob_clean();
echo json_encode($result);
```

Se aplica el código de estado HTTP si el controlador lo señalizó, se descarta cualquier salida generada accidentalmente durante el procesamiento con `ob_clean()`, y se serializa el resultado como JSON. La función `json_encode()` sin opciones adicionales produce un JSON compacto, adecuado para respuestas de API donde el tamaño de la transferencia importa.

---

## 3.3.8 Comunicación entre el Front-end y el Back-end

La calculadora fotovoltaica sigue una arquitectura cliente-pesado (_thick client_): la mayor parte de la lógica de cálculo —dimensionamiento de cadena, verificaciones eléctricas, estimación de producción, cálculo de protecciones— reside íntegramente en el navegador del usuario, implementada en JavaScript. El servidor PHP interviene únicamente en tres momentos específicos del flujo: cuando se necesita obtener datos climatológicos de la ubicación, cuando se necesita cargar el catálogo de equipos disponibles, y cuando el usuario solicita exportar el resultado como archivo Excel.

### 3.3.8.1 Mapa de Endpoints

La aplicación expone tres endpoints PHP para la calculadora:

```
Navegador (JavaScript)
│
├─ POST /api/calculadora.php?action=get_climate_data ──► NasaService (caché + API NASA)
│        Body: { "lat": 23.74, "lng": -99.15 }
│
├─ GET  /api/calculadora.php?action=get_pv_modules   ──► PVModuleRepository
│
├─ GET  /api/calculadora.php?action=get_inverters    ──► InverterRepository
│
├─ POST /api/export_excel.php                        ──► ExportService → binario .xlsx
│        Body: { completo payload del diseño }
│
└─ GET/POST /api/inventario.php?action=<acción>      ──► InventarioApiController
```

Todos los endpoints de la calculadora están protegidos por `AuthGuard::requireApi()`, que devuelve HTTP 401 si el usuario no tiene sesión activa.

### 3.3.8.2 Acción `get_climate_data` — Datos Climatológicos del Sitio

**Método HTTP:** POST  
**Invocado por:** `calc-bloque1.js` al confirmar la ubicación en el mapa

El cliente envía las coordenadas geográficas del sitio obtenidas del marcador Leaflet.js en el cuerpo de la petición codificado como JSON:

```json
{ "lat": 23.74, "lng": -99.15 }
```

El controlador valida que `lat` esté en el rango [−90, 90] y `lng` en [−180, 180]. Si la validación falla, retorna HTTP 400 con una descripción del error. Si la validación pasa, delega a `NasaService::getClimateData()`, que aplica el patrón caché-aside descrito en la sección 3.3.5.1.

La respuesta exitosa contiene los datos completos del sitio, incluyendo los ocho campos por registro mensual requeridos por los modelos físicos del front-end:

```json
{
  "source":  "cache",
  "lat":     23.74,
  "lng":     -99.15,
  "tmin":    4.8,
  "tmax":    31.2,
  "monthly": [
    {
      "month":    1,
      "ghi":      4.21,
      "dni":      3.10,
      "dhi":      0.95,
      "sun_hours": 10.8,
      "t2m_avg":  13.5,
      "t2m_max":  22.1,
      "t2m_min":  4.8,
      "ws10m":    2.3
    },
    ...
  ]
}
```

El campo `source` indica al front-end si los datos provienen del caché local (respuesta inmediata) o de la API de la NASA (petición externa con latencia). El front-end almacena `json.monthly` en la variable de clausura `monthlyData` dentro del IIFE de `calc-bloque1.js`, donde persiste incluso cuando el usuario reinicia el bloque de diseño y `window.calcState` es vaciado; `monthlyData` se copia también a `window.calcState.monthly` para que el bloque de diseño pueda acceder a los datos sin dependencia directa entre los dos archivos JavaScript.

Los valores `tmin` y `tmax` se usan en los cálculos de tensión de la cadena en condiciones extremas: `tmin` para calcular la tensión máxima de circuito abierto del arreglo (condición de diseño de mayor tensión, que no debe superar el máximo del inversor ni los límites de los conductores) y `tmax` para calcular la tensión mínima del punto de máxima potencia (condición que determina si el inversor puede arrancar).

### 3.3.8.3 Acciones `get_pv_modules` y `get_inverters` — Catálogo de Equipos

**Método HTTP:** GET  
**Invocado por:** `calc-diseno.js` mediante `window.loadDisenoBlock()`

Una característica importante de la arquitectura del front-end es que estas dos solicitudes se realizan en **paralelo** mediante la API `Promise.all` de JavaScript, en lugar de secuencialmente. Esto reduce el tiempo de carga percibido por el usuario, ya que ambas solicitudes viajan al servidor simultáneamente y el bloque de diseño espera a que ambas completen antes de renderizar los selectores:

```javascript
window.loadDisenoBlock = function () {
    if (dataLoaded) return;
    dataLoaded = true;

    Promise.all([
        fetch('/api/calculadora.php?action=get_pv_modules'),
        fetch('/api/calculadora.php?action=get_inverters'),
    ])
    .then(([r1, r2]) => Promise.all([r1.json(), r2.json()]))
    .then(([modules, inverters]) => {
        // Renderizar selectores con los datos recibidos
    });
};
```

La bandera `dataLoaded` garantiza que esta doble solicitud al servidor se realice como máximo una vez por sesión de página. Si el usuario navega de regreso al paso 1 y luego vuelve al paso 2, `loadDisenoBlock()` detecta que los datos ya están cargados y retorna inmediatamente sin realizar nuevas peticiones.

La respuesta de `get_pv_modules` es un arreglo JSON de objetos, donde cada objeto contiene todos los campos del modelo `PVModule` serializados en `snake_case`. De forma análoga, `get_inverters` retorna un arreglo de objetos `Inverter` que incluyen el arreglo anidado `mppt_groups` con los grupos MPPT de cada inversor.

### 3.3.8.4 Endpoint `export_excel.php` — Generación del Archivo de Exportación

**Método HTTP:** POST  
**Invocado por:** `calc-diseno.js` (función `handleExport()` dentro del IIFE)

El endpoint de exportación es un caso especial: **no consulta la base de datos**. Recibe el resultado completo del proceso de diseño —un objeto JSON con todos los parámetros ya calculados por el JavaScript del cliente— y lo delega directamente a `ExportService::build()`:

```php
// export_excel.php
AuthGuard::requireApi();
// ...
$payload = (array) json_decode(file_get_contents('php://input'), true);
$service = new ExportService();
$xlsx    = $service->build($payload);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="resumen-fv.xlsx"');
header('Content-Length: ' . strlen($xlsx));
echo $xlsx;
```

El payload enviado por el cliente contiene ocho secciones:

| Clave | Contenido |
|-------|-----------|
| `site` | Coordenadas, HSP, temperaturas extremas |
| `module` | Objeto con todos los parámetros del módulo seleccionado |
| `array` | Configuración del arreglo: N, Ns, Np, N_inv, tensiones, área |
| `inverter` | Objeto con todos los parámetros del inversor seleccionado |
| `checks` | Arreglo de verificaciones de compatibilidad con resultado pass/fail/warn |
| `energy` | Producción anual, factor de pérdidas, parámetros del modelo, desglose mensual de pérdidas, orientación (`tilt`, `azimuth`, `albedo`), coeficientes Faiman |
| `protection` | Escenarios DC (por número de strings por MPPT), circuito AC, parámetros de derating |
| `monthly` | Arreglo de 12 entradas con producción mensual (puede ser `null`) |

La función `buildExportPayload()` en `calc-diseno.js` es la responsable de recopilar todos estos datos desde `window.calcState` y los elementos del DOM para construir el objeto antes de enviarlo.

### 3.3.8.5 Endpoint `inventario.php` — CRUD del Catálogo

El endpoint del inventario expone diez acciones para la gestión completa del catálogo de equipos: operaciones de lectura paginada, creación, actualización y eliminación para las tres entidades del catálogo (fabricantes, módulos FV e inversores). Opera de forma completamente independiente del módulo de la calculadora y utiliza su propio controlador `InventarioApiController`.

### 3.3.8.6 Estado Compartido: `window.calcState`

La calculadora fotovoltaica no utiliza sesiones de servidor, cookies ni bases de datos para mantener el estado del proceso de diseño durante la sesión del usuario. En su lugar, el estado se mantiene en el objeto global `window.calcState`, que es accesible desde ambos archivos JavaScript del módulo. Esta decisión tiene implicaciones de arquitectura bien definidas: el estado existe únicamente en la memoria del navegador, desaparece completamente al recargar la página, y no genera carga adicional en el servidor durante el proceso de cálculo.

La tabla 3.X documenta las claves del objeto `window.calcState`, sus autores y sus consumidores:

| Clave | Escrita por | Leída por | Descripción |
|-------|-------------|-----------|-------------|
| `monthly` | Bloque 1 (`calc-bloque1.js`) al recibir la respuesta de la NASA | Bloque de diseño (estimación energética, payload de exportación) | Arreglo de 12 objetos con datos climatológicos mensuales completos |
| `module` | Bloque de diseño al seleccionar el módulo | Bloque de diseño (todos los cálculos), exportación | Objeto completo del módulo FV seleccionado |
| `N` | Bloque de diseño (dimensionamiento del arreglo) | Bloque de diseño, exportación | Número total de módulos del sistema |
| `P_stc_kW` | Bloque de diseño (dimensionamiento) | Verificaciones de compatibilidad, producción energética, exportación | Potencia total del arreglo en STC (kWp) |
| `inverter` | Bloque de diseño al seleccionar el inversor | Bloque de diseño (todos los cálculos), exportación | Objeto completo del inversor seleccionado con sus grupos MPPT |
| `Ns` | Bloque de diseño (configurador de string) | Exportación, protecciones | Módulos en serie por string |
| `Np` | Bloque de diseño (configurador de string) | Exportación, distribución de strings | Total de strings en paralelo del sistema |
| `N_inv` | Bloque de diseño (configurador de string) | Exportación, distribución de strings | Número de inversores |
| `Np_per_inv` | Bloque de diseño (configurador de string) | Exportación, cálculo de protecciones por inversor | Strings en paralelo por inversor |

El ciclo de vida del estado está gestionado por dos funciones de reset complementarias:

- **`window.resetDisenoBlock()`**: declarada en `calc-diseno.js`, elimina todas las claves del bloque de diseño de `calcState` (`module`, `N`, `P_stc_kW`, `inverter`, `Ns`, `Np`, `N_inv`, `Np_per_inv`) y restablece el DOM del bloque de diseño a su estado inicial. No elimina `monthly`, pues los datos climatológicos son independientes del diseño y se reutilizan si el usuario vuelve al paso 2.

- **`window.resetBlock1()`**: declarada en `calc-bloque1.js`, restablece el DOM del bloque 1 (limpia el mapa, los campos de coordenadas y los indicadores de HSP y temperatura). El objeto `window.calcState` se vacía completamente con `window.calcState = {}` cuando el usuario activa el botón "Comenzar de nuevo", para eliminar también `monthly` y forzar una nueva consulta de datos.

---

## Resumen de Correcciones Respecto a la Versión Anterior

La tabla siguiente sintetiza las diferencias identificadas entre la documentación anterior de la tesis y el código fuente actual:

| Aspecto | Versión documentada anteriormente | Implementación actual en el código |
|---------|-----------------------------------|-------------------------------------|
| Capa Core | 2 archivos (Config, Database) | 3 archivos: Config, Database, **AuthGuard** |
| Llamada a NASA POWER | 4 parámetros: GHI, T2M, T2M_MAX, T2M_MIN | **7 parámetros**: agrega DNI, DHI, WS10M |
| Campos del objeto mensual | `ghi`, `t2m_avg`, `t2m_max`, `t2m_min` | Agrega `dni`, `dhi`, `sun_hours`, `ws10m` |
| Tabla `climatology_monthly` | Sin columnas DNI, DHI, sun_hours, ws10m | Incluye `dni_kwh_m2_day`, `dhi_kwh_m2_day`, `sun_hours`, `ws10m` |
| `max_strings_per_mppt` en schema | `TINYINT UNSIGNED NOT NULL DEFAULT 1` | `TINYINT UNSIGNED NULL DEFAULT NULL` (nullable) |
| `maxTotalStrings` en modelo `Inverter` | No documentado | Campo `?int` nullable, considerado en `totalMaxStrings()` |
| `totalMaxStrings()` | Suma `mpptCount × maxStringsPerMppt` | Usa `?? PHP_INT_MAX` y acota por `maxTotalStrings` cuando existe |
| Hojas en el archivo Excel exportado | 2 hojas: "Resumen" + "Producción Mensual" | **1 hoja** "Resumen"; tabla mensual incrustada de forma alineada |
| Clave de pérdida en ExportService | `clipping` | `inverter` (Conversión inversor) |
| Carga de catálogos en el front-end | `get_pv_modules` y `get_inverters` en pasos/archivos distintos | Ambas cargadas en **paralelo** con `Promise.all` desde `calc-diseno.js` |
| Archivos JavaScript del módulo | 4 archivos (bloque 1, 2, 3, 4) | **2 archivos**: `calc-bloque1.js` y `calc-diseno.js` |
| Funciones de reset del front-end | `resetBlock2()`, `resetBlock3()`, `resetBlock4()` separadas | `resetDisenoBlock()` única para todo el bloque de diseño |
| `calcState` — claves documentadas | `module`, `N`, `inverter`, `Ns`, `Np`, `N_inv` | Agrega `P_stc_kW` y **`Np_per_inv`** |
| Payload de exportación | Sin parámetros de orientación ni pérdidas desglosadas | Incluye `tilt`, `azimuth`, `albedo`, coeficientes Faiman (U0, U1) y pérdidas individuales |

