<?php
define('APP', true);
$pageTitle = 'Dashboard - IPTE';
include '../components/header-dashboard.php';
?>

<!-- Content Header -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Panel principal</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item active">Inicio</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">

      <!-- Card: Calculadora FV -->
      <div class="col-12 col-sm-6 d-flex mb-4">
        <a href="/pages/calculadora.php" class="text-decoration-none w-100">
          <div class="card card-primary card-outline h-100">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-solar-panel mr-2"></i>Calculadora FV
              </h3>
            </div>
            <div class="card-body d-flex flex-column">
              <p class="card-text text-muted">
                Dimensionamiento de sistemas fotovoltaicos conectados a la red eléctrica.
                Obtén resultados precisos para optimizar el diseño y rendimiento del sistema.
              </p>
              <div class="mt-auto pt-3">
                <span class="btn btn-primary btn-sm">
                  Abrir módulo <i class="fas fa-arrow-right ml-1"></i>
                </span>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Card: Inventario -->
      <div class="col-12 col-sm-6 d-flex mb-4">
        <a href="/pages/inventario.php" class="text-decoration-none w-100">
          <div class="card card-secondary card-outline h-100">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-boxes-stacked mr-2"></i>Inventario
              </h3>
            </div>
            <div class="card-body d-flex flex-column">
              <p class="card-text text-muted">
                Gestión de equipo para sistemas fotovoltaicos. Administra paneles,
                inversores y demás componentes del catálogo.
              </p>
              <div class="mt-auto pt-3">
                <span class="btn btn-secondary btn-sm">
                  Abrir módulo <i class="fas fa-arrow-right ml-1"></i>
                </span>
              </div>
            </div>
          </div>
        </a>
      </div>

    </div>
  </div>
</section>

<?php include '../components/footer.php'; ?>

