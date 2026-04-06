<?php
define('APP', true);
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Config.php';

// If OAuth callback params arrive on '/', proxy them to the backend callback
// endpoint while preserving the original code/state/error values.
if (isset($_GET['code']) || isset($_GET['error'])) {
  $callbackParams = ['action' => 'callback'];
  foreach (['code', 'state', 'error', 'error_description', 'error_uri'] as $k) {
    if (isset($_GET[$k])) {
      $callbackParams[$k] = (string) $_GET[$k];
    }
  }
  header('Location: ' . BASE_URL . '/api/auth.php?' . http_build_query($callbackParams));
  exit;
}

// ── DEV MODE ──────────────────────────────────────────────────────────────────
// Set to true while you don't have the company Azure AD keys.
// Flip to false (and fill msalConfig below) before production deployment.

const DEV_MODE = true;

if (DEV_MODE) {
    // Session is created server-side — no JS round-trip, no cookie timing issues.
    \App\Core\AuthGuard::createUserSession([
        'homeAccountId' => 'mock-dev-id',
        'username'      => 'dev@ipte.mx',
        'name'          => 'Usuario de Prueba',
    ]);
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

// If already authenticated, skip the login page.
if (\App\Core\AuthGuard::currentUser() !== null) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$pageTitle = 'Calculadora FV - IPTE';

$authError = (string) ($_GET['auth_error'] ?? '');
$authErrorMessage = '';
switch ($authError) {
    case 'config':
        $authErrorMessage = 'La configuración de autenticación no está completa en el servidor.';
        break;
    case 'provider':
        $authErrorMessage = 'Microsoft devolvió un error durante el inicio de sesión.';
        break;
    case 'state':
        $authErrorMessage = 'No se pudo validar la solicitud de autenticación. Intenta nuevamente.';
        break;
    case 'token':
        $authErrorMessage = 'No fue posible completar la validación del token de acceso.';
        break;
}

include 'components/header.php';
?>

  <!-- Hero / Main section -->
  <main class="flex-fill position-relative d-flex align-items-center">

    <!-- Background image -->
    <div class="position-absolute w-100 h-100"
         style="background-image:url('<?= BASE_URL ?>/Images/Paneles.webp');
                background-size:cover; background-position:center; top:0; left:0;">
    </div>

    <!-- Gradient overlay: dark on the left, transparent on the right -->
    <div class="position-absolute w-100 h-100"
         style="background:linear-gradient(to right,rgba(23,25,51,.9) 0%,rgba(23,25,51,.6) 50%,transparent 100%);
                top:0; left:0;">
    </div>

    <!-- Content -->
    <div class="position-relative w-100 py-5" style="z-index:10;">
      <div class="container-fluid px-4 px-sm-5">
        <div class="mx-auto px-3" style="max-width:800px;">

          <span class="d-inline-block text-white text-uppercase font-weight-bold mb-3"
                style="font-size:.75rem; letter-spacing:.15em;">
            Soluciones Tecnológicas
          </span>

          <h1 class="display-4 font-weight-bold text-white mb-4 hero-title text-break" style="line-height:1.18;">
            DAN<br>Calculadora solar interconectada a CFE
          </h1>

          <p class="mb-4 text-white-50 text-break" style="font-size:1.0625rem; line-height:1.7;">
            Módulo web diseñado para el dimensionamiento de Sistemas Fotovoltaicos conectados
            a la red eléctrica, proporcionando resultados precisos y confiables para optimizar
            el diseño y rendimiento de los sistemas solares.
          </p>

          <?php if ($authErrorMessage !== ''): ?>
            <div class="alert alert-danger mt-3" role="alert">
              <?= htmlspecialchars($authErrorMessage) ?>
            </div>
          <?php endif; ?>

          <a href="<?= BASE_URL ?>/api/auth.php?action=login"
             class="btn btn-primary font-weight-bold px-4 py-2 btn-block"
             style="max-width:250px; background-color:#0665F7; border-color:#0665F7;">
            Iniciar sesión
          </a>

        </div>
      </div>
    </div>

  </main>

<?php include 'components/footer.php'; ?>