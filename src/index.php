<?php
define('APP', true);
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Config.php';

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

$extraScripts = <<<'SCRIPTS'
<!-- MSAL.js 2.x -->
<script src="https://alcdn.msauth.net/browser/2.38.3/js/msal-browser.min.js"
        integrity="sha384-Dx6pQHU4gKBT+lVMaVFNnCHN+UMUqT/fnEqggwAHMnlFdp3k9IiAMlIW7IIXMZL"
        crossorigin="anonymous"></script>
<script>
  // ── MSAL config – fill in company values before deploying ──────────────────
  const msalConfig = {
    auth: {
      clientId:    "YOUR_CLIENT_ID",   // TODO: App Registration clientId
      authority:   "https://login.microsoftonline.com/YOUR_TENANT_ID", // TODO: tenant
      redirectUri: window.location.origin + window.location.pathname
    }
  };

  var msalInstance = new msal.PublicClientApplication(msalConfig);

  window.signIn = function () {
    msalInstance.loginRedirect({ scopes: ["user.read"] });
  };

  msalInstance.handleRedirectPromise()
    .then(function (response) {
      if (response) {
        return fetch(BASE_URL + "/api/auth.php?action=login", {
          method:  "POST",
          headers: { "Content-Type": "application/json" },
          body:    JSON.stringify(response.account)
        }).then(function (r) {
          if (!r.ok) throw new Error("Server session error: " + r.status);
          localStorage.setItem("cuenta", JSON.stringify(response.account));
          window.location.replace(BASE_URL + "/pages/dashboard.php");
        });
      }
    })
    .catch(function (error) {
      console.error("MSAL error:", error);
      var errDiv = document.getElementById("login-error");
      if (errDiv) {
        errDiv.textContent = "Error al iniciar sesión: " + error.message;
        errDiv.classList.remove("d-none");
      }
    });
</script>
SCRIPTS;

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
        <div class="mx-auto px-3" style="max-width:560px;">

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

          <button id="login-btn" onclick="signIn()"
                  class="btn btn-primary font-weight-bold px-4 py-2 btn-block d-sm-inline-block"
                  style="background-color:#0665F7; border-color:#0665F7;">
            Iniciar sesión
          </button>
          <div id="login-error" class="alert alert-danger mt-3 d-none" role="alert"></div>

        </div>
      </div>
    </div>

  </main>

<?php include 'components/footer.php'; ?>