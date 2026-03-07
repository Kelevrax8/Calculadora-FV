<?php
defined('APP') or die('Access denied');
$adminlteLayout = true;
$currentPage    = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard - IPTE') ?></title>

  <!-- Bootstrap 4 (AdminLTE dependency) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- AdminLTE 3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
  <!-- Toastr notifications -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
  <!-- IPTE brand overrides -->
  <style>
    :root { --ipte-blue: #171933; --ipte-blue2: #0665F7; }
    .main-sidebar,
    .main-sidebar .brand-link          { background-color: #171933 !important; }
    .main-header.navbar                { background-color: #171933 !important; }
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active:hover
                                       { background-color: #0665F7 !important; }
    .brand-link:hover                  { background-color: rgba(255,255,255,.05) !important; }
    .content-wrapper                   { background-color: #f4f6f9; }
  </style>

  <!-- Per-page extra head tags (stylesheets, preloads, etc.) -->
  <?= $extraHead ?? '' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">


  <!-- ── Top Navbar ──────────────────────────────────────── -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-sm-flex align-items-center">
        <span class="nav-link text-white-50 py-0">usuario@ipte.com</span>
      </li>
      <li class="nav-item d-flex align-items-center pl-2">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle
                     text-white font-weight-bold"
              style="width:32px;height:32px;background-color:#0665F7;font-size:.75rem;">U</span>
      </li>
    </ul>
  </nav>

  <!-- ── Sidebar ──────────────────────────────────────────── -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/pages/dashboard.php" class="brand-link">
      <img src="/Images/Logo-IPTE.png" alt="IPTE" class="brand-image img-fluid"
           style="opacity:.9; filter:brightness(0) invert(1); max-height:33px;">
      <span class="brand-text font-weight-bold">Calculadora</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column"
            data-widget="treeview" role="menu" data-accordion="false">

          <li class="nav-item">
            <a href="/pages/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-home"></i>
              <p>Inicio</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="/pages/calculadora.php"
               class="nav-link <?= $currentPage === 'calculadora.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-solar-panel"></i>
              <p>Calculadora FV</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="/pages/inventario.php"
               class="nav-link <?= $currentPage === 'inventario.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-boxes-stacked"></i>
              <p>Inventario</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <!-- ── Content Wrapper ──────────────────────────────────── -->
  <div class="content-wrapper">