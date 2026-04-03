<?php
defined('APP') or die('Access denied');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Config.php';

use App\Core\AuthGuard;
AuthGuard::requirePage();

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

  <!-- Auth guard: must load before page content renders -->
  <script>
    var BASE_URL = '<?= BASE_URL ?>';
    // Sync the server-side session into localStorage so auth-guard.js finds it.
    // requirePage() already confirmed this user is authenticated.
    localStorage.setItem('cuenta', JSON.stringify(<?= json_encode(\App\Core\AuthGuard::currentUser()) ?>));
  </script>
  <script src="<?= BASE_URL ?>/js/auth-guard.js"></script>
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

    <!-- Right side: logged-in user + sign-out -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-flex align-items-center pr-2">
        <span id="nav-username" class="text-white-50" style="font-size:.85rem;"></span>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="signOut(); return false;" title="Cerrar sesión">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </li>
    </ul>
  </nav>

  <!-- ── Sidebar ──────────────────────────────────────────── -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4 main-sidebar-custom">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="brand-link">
      <img src="<?= BASE_URL ?>/Images/Ipte-logo-negativo.png" alt="Logo IPTE" class="brand-image img-fluid">
      <span class="brand-text font-weight-bold">IPTE Soluciones</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column"
            data-widget="treeview" role="menu" data-accordion="false">

            <li class="nav-item">
              <a href="/pages/firmas.php" class="nav-link">
                <i class="nav-icon fas fa-edit"></i>
                <p>Firmas</p>
              </a>
            </li>

          <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-home"></i>
              <p>Inicio</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/calculadora.php"
               class="nav-link <?= $currentPage === 'calculadora.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-solar-panel"></i>
              <p>Calculadora FV</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= BASE_URL ?>/pages/inventario.php"
               class="nav-link <?= $currentPage === 'inventario.php' ? 'active' : '' ?>">
              <i class="nav-icon fas fa-boxes-stacked"></i>
              <p>Inventario</p>
            </a>
          </li>

          <li class="nav-header">Herramientas</li>
          <li class="nav-item">
            <a href="/solar/login.html" class="nav-link">
              <i class="nav-icon fas fa-sun"></i>
              <p>Calculadora Aislada</p>
            </a>
          </li>

          <li class="nav-header">Grupos</li>
          <li class="nav-item">
            <a href="https://teams.microsoft.com/l/team/19%3Aea96ea5e62894ffa8ee8a9c3d9c871fd%40thread.tacv2/conversations?groupId=89781700-ea3d-42be-a2e2-daefb226de2d&amp;tenantId=641dfc1b-79a5-4f78-8365-73af7c2d0126" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>Ingeniería</p>
              </a>
          </li>
          <li class="nav-item">
              <a href="https://teams.microsoft.com/l/team/19%3ApUFQCRZF-Ywg21dH-llkZgfrk8WilG_1iRXGSqSlMUg1%40thread.tacv2/conversations?groupId=4ea39ea6-2d68-4acb-9557-fb077e08f226&amp;tenantId=641dfc1b-79a5-4f78-8365-73af7c2d0126" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>Soporte Técnico</p>
              </a>
            </li>
          <li class="nav-item">
              <a href="https://teams.microsoft.com/l/team/19%3AFQfeXmEqu9H2Yi1w4b5pvcP1ViKNuzHqTaJFlm7kmcA1%40thread.tacv2/conversations?groupId=277b6079-120d-4514-bbf2-a3c31b212941&amp;tenantId=641dfc1b-79a5-4f78-8365-73af7c2d0126" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>Implementación</p>
              </a>
          </li>
        </ul>
      </nav>
    </div>
    <div class="sidebar-custom">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="#" class="nav-link" onclick="signOut(); return false;">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Cerrar sesión</p>
            </a>
          </li>
      </ul>
    </div>
  </aside>

  <!-- ── Content Wrapper ──────────────────────────────────── -->
  <div class="content-wrapper">