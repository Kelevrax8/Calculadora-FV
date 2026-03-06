<?php
define('APP', true);
$pageTitle = 'Calculadora FV - IPTE';
$extraHead = <<<'HEAD'
<link rel="stylesheet" href="/lib/leaflet.css"/>
<style>
  /* Tailwind resets max-width on img elements which breaks Leaflet tiles */
  .leaflet-container img { max-width: none !important; max-height: none !important; }
</style>
HEAD;
include '../components/header-dashboard.php';
?>

<main class="flex-1 overflow-y-auto bg-gray-100">
  <div class="max-w-7xl mx-auto px-6 py-8">

    <!-- Page title -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-Ipteblue">Calculadora FV</h1>
      <p class="text-sm text-gray-500 mt-1">Dimensionamiento de sistemas fotovoltaicos interconectados</p>
    </div>

    <!-- Blocks -->
    <?php include '../components/calc/bloque-calc1.php'; ?>
    <?php include '../components/calc/bloque-calc2.php'; ?>
    <?php include '../components/calc/bloque-calc3.php'; ?>
    <?php include '../components/calc/bloque-calc4.php'; ?>

  </div><!-- /max-w-7xl -->
</main>

<?php
$extraScripts = <<<'HTML'
<script src="/lib/leaflet.js"></script>
<script src="/js/calc-bloque1.js"></script>
<script src="/js/calc-bloque2.js"></script>
<script src="/js/calc-bloque3.js"></script>
<script src="/js/calc-bloque4.js"></script>
HTML;
include '../components/footer.php';
?>
