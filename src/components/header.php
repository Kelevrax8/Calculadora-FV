<?php defined('APP') or die('Access denied'); ?>
<?php require_once __DIR__ . '/../app/Core/Config.php'; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle ?? 'Calculadora FV - IPTE') ?></title>

  <!-- Bootstrap 4 (AdminLTE dependency) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- AdminLTE 3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
  <style>
    :root { --ipte-blue: #171933; --ipte-blue2: #0665F7; }
    /* Responsive hero title: slightly reduce size on very small viewports to avoid overflow
       Keeps desktop display-4 unchanged while preserving visual identity. */
    @media (max-width: 420px) {
      .hero-title {
        font-size: 2.3rem !important;
        line-height: 1.02 !important;
        word-break: break-word;
      }
    }
  </style>
</head>
<body style="background-color:#171933; min-height:100vh; display:flex; flex-direction:column;">

  <!-- Top bar -->
  <nav class="navbar" style="background:white">
    <a class="navbar-brand py-1" href="<?= BASE_URL ?>/">
      <img src="<?= BASE_URL ?>/Images/Logo-IPTE.png" alt="Logo IPTE"
           style="height:3rem; width:auto; object-fit:contain;">
    </a>
  </nav>
