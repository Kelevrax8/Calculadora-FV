<?php
define('APP', true);
$pageTitle = 'Calculadora FV - IPTE';
include 'components/header.php';
?>

  <!-- Hero / Main section -->
  <main class="flex-fill position-relative d-flex align-items-center"
        style="min-height:calc(100vh - 56px);">

    <!-- Background image -->
    <div class="position-absolute w-100 h-100"
         style="background-image:url('/Images/Paneles.webp');
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
        <div style="max-width:560px;">

          <span class="d-inline-block text-white text-uppercase font-weight-bold mb-3"
                style="font-size:.75rem; letter-spacing:.15em;">
            Soluciones Tecnológicas
          </span>

          <h1 class="display-4 font-weight-bold text-white mb-4" style="line-height:1.18;">
            DAN<br>Calculadora solar interconectada a CFE
          </h1>

          <p class="mb-4" style="color:rgba(255,255,255,.7); font-size:1.0625rem; line-height:1.7;">
            Módulo web diseñado para el dimensionamiento de Sistemas Fotovoltaicos conectados
            a la red eléctrica, proporcionando resultados precisos y confiables para optimizar
            el diseño y rendimiento de los sistemas solares.
          </p>

          <a href="/pages/dashboard.php"
             class="btn btn-primary font-weight-bold px-4 py-2"
             style="background-color:#0665F7; border-color:#0665F7;">
            Iniciar sesión
          </a>

        </div>
      </div>
    </div>

  </main>

<?php include 'components/footer.php'; ?>