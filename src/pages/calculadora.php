<?php
require_once __DIR__ . '/../app/Core/Config.php';
define('APP', true);
$base      = BASE_URL;
$pageTitle = 'Calculadora FV - IPTE';
$extraHead = <<<HEAD
<link rel="stylesheet" href="{$base}/lib/leaflet.css"/>
<style>
  /* Prevent Bootstrap from clipping Leaflet tiles */
  .leaflet-container img { max-width: none !important; max-height: none !important; }
</style>
HEAD;
include '../components/header-dashboard.php';
?>

<!-- Content Header -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Calculadora FV</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pages/dashboard.php">Inicio</a></li>
          <li class="breadcrumb-item active">Calculadora FV</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <?php include '../components/calc/bloque-calc1.php'; ?>
    <?php include '../components/calc/bloque-calc2.php'; ?>
    <?php include '../components/calc/bloque-calc3.php'; ?>
    <?php include '../components/calc/bloque-calc4.php'; ?>

  </div><!-- /.container-fluid -->
</section>

<?php
$extraScripts = <<<HTML
<script src="{$base}/lib/leaflet.js"></script>
<script src="{$base}/js/calc-bloque1.js"></script>
<script src="{$base}/js/calc-bloque2.js"></script>
<script src="{$base}/js/calc-bloque3.js"></script>
<script src="{$base}/js/calc-bloque4.js"></script>
HTML;
include '../components/footer.php';
?>
