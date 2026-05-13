<?php
// ============================================
// dashboard.php — Panel de usuario ProHabil
// ============================================
require_once __DIR__ . '/php/config.php';

// Si no hay sesión activa, redirigir al inicio
if (empty($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit;
}

$usuario_id   = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo'];
$usuario_nombre = $_SESSION['usuario_nombre'];

// Obtener datos completos del usuario
$stmt = db()->prepare('
    SELECT u.*,
           o.nombre AS oficio_nombre, o.icono AS oficio_icono,
           t.id AS trabajador_id, t.descripcion, t.años_experiencia,
           t.precio_hora, t.precio_dia, t.disponible,
           t.calificacion_promedio, t.total_trabajos
    FROM usuarios u
    LEFT JOIN trabajadores t ON t.usuario_id = u.id
    LEFT JOIN oficios o ON o.id = t.oficio_id
    WHERE u.id = ?
');
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

// Contar solicitudes según tipo
if ($usuario_tipo === 'cliente') {
    $stmt2 = db()->prepare('SELECT COUNT(*) FROM solicitudes WHERE cliente_id = ?');
} else {
    $stmt2 = db()->prepare('SELECT COUNT(*) FROM solicitudes s JOIN trabajadores t ON s.trabajador_id = t.id WHERE t.usuario_id = ?');
}
$stmt2->execute([$usuario_id]);
$total_solicitudes = $stmt2->fetchColumn();

$nombre_completo = $user['nombre'] . ' ' . $user['apellido_paterno'];
$iniciales = strtoupper(mb_substr($user['nombre'],0,1) . mb_substr($user['apellido_paterno'],0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mi Panel — ProHabil</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --amber:    #F5A623;
      --amber-dk: #C77D0A;
      --ink:      #1A1208;
      --earth:    #3D2B1F;
      --cream:    #FBF5EC;
      --cream-dk: #F0E6D3;
      --rust:     #C0452A;
      --sage:     #4A7C59;
      --white:    #FFFFFF;
      --shadow:   rgba(26,18,8,.12);
      --ff-head: 'Inter', sans-serif;
      --ff-body: 'DM Sans', sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: var(--ff-body); background: #F4EEE4; color: var(--ink); min-height: 100vh; display: flex; }

    /* ── SIDEBAR ── */
    .sidebar {
      width: 260px; min-height: 100vh;
      background: var(--ink);
      display: flex; flex-direction: column;
      position: fixed; top: 0; left: 0; bottom: 0;
      z-index: 10;
      padding: 0 0 24px;
    }
    .sidebar-logo {
      padding: 28px 28px 24px;
      border-bottom: 1px solid rgba(255,255,255,.07);
      font-family: var(--ff-head);
      font-size: 1.45rem;
      font-weight: 800;
      letter-spacing: -.04em;
      color: var(--cream);
    }
    .sidebar-logo span { color: var(--amber); }

    .sidebar-profile {
      margin: 24px 16px;
      background: rgba(255,255,255,.06);
      border-radius: 14px;
      padding: 16px;
      display: flex; align-items: center; gap: 12px;
    }
    .avatar {
      width: 44px; height: 44px;
      border-radius: 50%;
      background: var(--amber);
      display: flex; align-items: center; justify-content: center;
      font-family: var(--ff-head);
      font-weight: 800;
      font-size: 1rem;
      color: var(--ink);
      flex-shrink: 0;
    }
    .profile-info .name {
      font-weight: 600;
      font-size: .88rem;
      color: var(--cream);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 140px;
    }
    .profile-info .badge {
      display: inline-block;
      margin-top: 3px;
      padding: 2px 8px;
      border-radius: 50px;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .badge-cliente   { background: rgba(74,124,89,.3);  color: #7EC99A; }
    .badge-trabajador{ background: rgba(245,166,35,.2); color: var(--amber); }

    .sidebar-nav { flex: 1; padding: 8px 12px; }
    .nav-section-label {
      font-size: .68rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: rgba(251,245,236,.3);
      padding: 12px 16px 6px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 16px;
      border-radius: 10px;
      font-size: .88rem;
      font-weight: 500;
      color: rgba(251,245,236,.55);
      cursor: pointer;
      transition: background .18s, color .18s;
      text-decoration: none;
      border: none; background: none; width: 100%; text-align: left;
      font-family: var(--ff-body);
    }
    .nav-item:hover { background: rgba(255,255,255,.07); color: var(--cream); }
    .nav-item.active { background: var(--amber); color: var(--ink); font-weight: 700; }
    .nav-item .ni-icon { font-size: 1rem; width: 20px; text-align: center; }

    .sidebar-footer {
      padding: 0 12px;
      border-top: 1px solid rgba(255,255,255,.07);
      padding-top: 16px;
    }
    .btn-logout {
      display: flex; align-items: center; gap: 10px;
      width: 100%; padding: 11px 16px;
      border-radius: 10px;
      font-size: .88rem;
      font-weight: 600;
      color: rgba(192,69,42,.8);
      background: none; border: none;
      cursor: pointer; font-family: var(--ff-body);
      transition: background .18s, color .18s;
    }
    .btn-logout:hover { background: rgba(192,69,42,.15); color: var(--rust); }

    /* ── MAIN ── */
    .main {
      margin-left: 260px;
      flex: 1;
      min-height: 100vh;
      display: flex; flex-direction: column;
    }

    /* top bar */
    .topbar {
      background: var(--cream);
      border-bottom: 1.5px solid var(--cream-dk);
      padding: 0 40px;
      height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 5;
    }
    .topbar-title {
      font-family: var(--ff-head);
      font-size: 1.1rem;
      font-weight: 700;
      letter-spacing: -.02em;
    }
    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .topbar-greeting {
      font-size: .85rem;
      color: var(--earth);
    }
    .topbar-greeting strong { color: var(--ink); }

    /* ── CONTENT ── */
    .content { padding: 36px 40px; flex: 1; }

    /* welcome banner */
    .welcome-banner {
      background: var(--ink);
      border-radius: 20px;
      padding: 32px 36px;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 32px;
      position: relative;
      overflow: hidden;
    }
    .welcome-banner::before {
      content: '';
      position: absolute; right: -60px; top: -60px;
      width: 280px; height: 280px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245,166,35,.25) 0%, transparent 70%);
    }
    .wb-text h2 {
      font-family: var(--ff-head);
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--cream);
      letter-spacing: -.03em;
      margin-bottom: 6px;
    }
    .wb-text p { font-size: .9rem; color: rgba(251,245,236,.55); max-width: 400px; line-height: 1.5; }
    .wb-icon { font-size: 3.5rem; position: relative; z-index: 1; }

    /* stat cards */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }
    .stat-card {
      background: var(--white);
      border-radius: 16px;
      padding: 22px 24px;
      box-shadow: 0 2px 12px var(--shadow);
      border: 1.5px solid transparent;
      transition: border-color .2s, transform .2s;
    }
    .stat-card:hover { border-color: var(--amber); transform: translateY(-2px); }
    .stat-card .sc-label {
      font-size: .75rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--earth);
      margin-bottom: 8px;
    }
    .stat-card .sc-value {
      font-family: var(--ff-head);
      font-size: 2rem;
      font-weight: 800;
      color: var(--ink);
    }
    .stat-card .sc-sub { font-size: .78rem; color: var(--earth); margin-top: 3px; }
    .stat-card .sc-icon { font-size: 1.6rem; margin-bottom: 6px; }

    /* sections */
    .section-title-sm {
      font-family: var(--ff-head);
      font-size: 1.15rem;
      font-weight: 700;
      letter-spacing: -.02em;
      margin-bottom: 16px;
    }

    /* info card */
    .info-card {
      background: var(--white);
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 2px 12px var(--shadow);
      margin-bottom: 24px;
    }
    .info-card h3 {
      font-family: var(--ff-head);
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1.5px solid var(--cream-dk);
      display: flex; align-items: center; gap: 8px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 16px;
    }
    .info-field label {
      display: block;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      color: var(--earth);
      margin-bottom: 4px;
    }
    .info-field span {
      font-size: .92rem;
      color: var(--ink);
      font-weight: 500;
    }
    .info-field span.empty { color: rgba(61,43,31,.35); font-style: italic; }

    /* Stars */
    .stars { color: var(--amber); font-size: 1.1rem; letter-spacing: 2px; }

    /* disponible toggle */
    .disponible-row {
      display: flex; align-items: center; gap: 14px;
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1.5px solid var(--cream-dk);
    }
    .toggle-label { font-size: .9rem; font-weight: 600; }
    .toggle {
      position: relative; width: 46px; height: 26px;
      cursor: pointer;
    }
    .toggle input { opacity: 0; width: 0; height: 0; }
    .toggle-track {
      position: absolute; inset: 0;
      background: var(--cream-dk);
      border-radius: 50px;
      transition: background .25s;
    }
    .toggle input:checked + .toggle-track { background: var(--sage); }
    .toggle-thumb {
      position: absolute;
      top: 3px; left: 3px;
      width: 20px; height: 20px;
      background: var(--white);
      border-radius: 50%;
      transition: transform .25s;
      box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .toggle input:checked ~ .toggle-thumb { transform: translateX(20px); }

    /* CTA card */
    .cta-card {
      background: linear-gradient(135deg, var(--amber) 0%, #E8870A 100%);
      border-radius: 16px;
      padding: 28px;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 24px;
    }
    .cta-card h3 {
      font-family: var(--ff-head);
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--ink);
      margin-bottom: 4px;
    }
    .cta-card p { font-size: .85rem; color: rgba(26,18,8,.65); }
    .btn-cta {
      padding: 10px 22px;
      background: var(--ink);
      color: var(--cream);
      font-family: var(--ff-head);
      font-size: .88rem;
      font-weight: 700;
      border-radius: 50px;
      border: none; cursor: pointer;
      white-space: nowrap;
      transition: opacity .2s;
      text-decoration: none;
    }
    .btn-cta:hover { opacity: .85; }

    /* empty state */
    .empty-state {
      text-align: center;
      padding: 48px 20px;
      color: var(--earth);
    }
    .empty-state .es-icon { font-size: 3rem; margin-bottom: 12px; }
    .empty-state p { font-size: .9rem; line-height: 1.6; }

    /* Responsive */
    @media(max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main { margin-left: 0; }
      .content { padding: 24px 20px; }
      .topbar { padding: 0 20px; }
      .welcome-banner { flex-direction: column; gap: 16px; text-align: center; }
    }
  </style>
</head>
<body>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar">
  <div class="sidebar-logo">Pro<span>Habil</span></div>

  <div class="sidebar-profile">
    <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
    <div class="profile-info">
      <div class="name"><?= htmlspecialchars($nombre_completo) ?></div>
      <span class="badge badge-<?= $usuario_tipo ?>">
        <?= $usuario_tipo === 'cliente' ? '👤 Cliente' : '🔧 Trabajador' ?>
      </span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Menú</div>
    <button class="nav-item active" onclick="showSection('inicio')">
      <span class="ni-icon">🏠</span> Mi panel
    </button>
    <button class="nav-item" onclick="showSection('perfil')">
      <span class="ni-icon">👤</span> Mi perfil
    </button>
    <?php if ($usuario_tipo === 'cliente'): ?>
    <button class="nav-item" onclick="showSection('buscar')">
      <span class="ni-icon">🔍</span> Buscar trabajadores
    </button>
    <?php endif; ?>
    <button class="nav-item" onclick="showSection('solicitudes')">
      <span class="ni-icon">📋</span> Mis solicitudes
    </button>
    <?php if ($usuario_tipo === 'trabajador'): ?>
    <button class="nav-item" onclick="showSection('calificaciones')">
      <span class="ni-icon">⭐</span> Mis calificaciones
    </button>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <button class="btn-logout" onclick="cerrarSesion(this)">
      <span>🚪</span> Cerrar sesión
    </button>
  </div>
</aside>

<!-- ========== MAIN ========== -->
<main class="main">
  <div class="topbar">
    <div class="topbar-title" id="topbarTitle">Mi panel</div>
    <div class="topbar-right">
      <span class="topbar-greeting">Hola, <strong><?= htmlspecialchars($user['nombre']) ?></strong> 👋</span>
    </div>
  </div>

  <div class="content">

    <!-- ===== SECCIÓN INICIO ===== -->
    <div id="sec-inicio">
      <div class="welcome-banner">
        <div class="wb-text">
          <h2>¡Bienvenido, <?= htmlspecialchars($user['nombre']) ?>!</h2>
          <p>
            <?php if ($usuario_tipo === 'cliente'): ?>
              Encuentra al profesional ideal para tu proyecto en Irapuato. Explora los oficios disponibles y contacta directamente.
            <?php else: ?>
              Tu perfil está activo. Los clientes pueden encontrarte y contratarte. Mantén tu información actualizada.
            <?php endif; ?>
          </p>
        </div>
        <div class="wb-icon">
          <?= $usuario_tipo === 'cliente' ? '🏡' : ($user['oficio_icono'] ?? '🔨') ?>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-card">
          <div class="sc-icon">📋</div>
          <div class="sc-label">Solicitudes</div>
          <div class="sc-value"><?= $total_solicitudes ?></div>
          <div class="sc-sub">Total registradas</div>
        </div>

        <?php if ($usuario_tipo === 'trabajador'): ?>
        <div class="stat-card">
          <div class="sc-icon">⭐</div>
          <div class="sc-label">Calificación</div>
          <div class="sc-value"><?= number_format($user['calificacion_promedio'] ?? 0, 1) ?></div>
          <div class="sc-sub">de 5.0 estrellas</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">✅</div>
          <div class="sc-label">Trabajos</div>
          <div class="sc-value"><?= $user['total_trabajos'] ?? 0 ?></div>
          <div class="sc-sub">Completados</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon"><?= $user['oficio_icono'] ?? '🔧' ?></div>
          <div class="sc-label">Mi oficio</div>
          <div class="sc-value" style="font-size:1.1rem;margin-top:4px"><?= htmlspecialchars($user['oficio_nombre'] ?? '—') ?></div>
          <div class="sc-sub"><?= $user['años_experiencia'] ?? 0 ?> años de exp.</div>
        </div>
        <?php else: ?>
        <div class="stat-card">
          <div class="sc-icon">🔍</div>
          <div class="sc-label">Oficios disponibles</div>
          <div class="sc-value">15</div>
          <div class="sc-sub">En Irapuato</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon">👷</div>
          <div class="sc-label">Trabajadores</div>
          <div class="sc-value">150+</div>
          <div class="sc-sub">Verificados</div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($usuario_tipo === 'cliente'): ?>
      <div class="cta-card">
        <div>
          <h3>¿Necesitas un profesional hoy?</h3>
          <p>Explora albañiles, electricistas, plomeros y más oficios disponibles.</p>
        </div>
        <button class="btn-cta" onclick="showSection('buscar')">Ver trabajadores →</button>
      </div>
      <?php else: ?>
      <div class="cta-card">
        <div>
          <h3>Tu perfil es tu carta de presentación</h3>
          <p>Mantén tu descripción, precio y disponibilidad actualizados.</p>
        </div>
        <button class="btn-cta" onclick="showSection('perfil')">Editar perfil →</button>
      </div>
      <?php endif; ?>
    </div>

    <!-- ===== SECCIÓN PERFIL ===== -->
    <div id="sec-perfil" style="display:none">
      <div class="info-card">
        <h3>👤 Datos personales</h3>
        <div class="info-grid">
          <div class="info-field"><label>Nombre completo</label><span><?= htmlspecialchars($nombre_completo) ?></span></div>
          <div class="info-field"><label>Apellido materno</label><span><?= htmlspecialchars($user['apellido_materno'] ?: '') ?><?php if(!$user['apellido_materno']) echo '<span class="empty">No registrado</span>'; ?></span></div>
          <div class="info-field"><label>Teléfono</label><span><?= htmlspecialchars($user['telefono']) ?></span></div>
          <div class="info-field"><label>Correo electrónico</label><span><?= htmlspecialchars($user['correo']) ?></span></div>
          <div class="info-field"><label>Colonia</label><span><?= $user['colonia'] ? htmlspecialchars($user['colonia']) : '<span class="empty">No registrada</span>' ?></span></div>
          <div class="info-field"><label>Dirección</label><span><?= $user['direccion'] ? htmlspecialchars($user['direccion']) : '<span class="empty">No registrada</span>' ?></span></div>
          <div class="info-field"><label>Fecha de nacimiento</label><span><?= $user['fecha_nacimiento'] ? date('d/m/Y', strtotime($user['fecha_nacimiento'])) : '<span class="empty">No registrada</span>' ?></span></div>
          <div class="info-field"><label>Miembro desde</label><span><?= date('d \d\e F \d\e Y', strtotime($user['created_at'])) ?></span></div>
        </div>
      </div>

      <?php if ($usuario_tipo === 'trabajador'): ?>
      <div class="info-card">
        <h3><?= $user['oficio_icono'] ?? '🔧' ?> Información de oficio</h3>
        <div class="info-grid">
          <div class="info-field"><label>Oficio</label><span><?= htmlspecialchars($user['oficio_nombre'] ?? '—') ?></span></div>
          <div class="info-field"><label>Años de experiencia</label><span><?= $user['años_experiencia'] ?? 0 ?> años</span></div>
          <div class="info-field"><label>Precio por hora</label><span><?= $user['precio_hora'] ? '$' . number_format($user['precio_hora'], 0) . ' MXN' : '<span class="empty">No definido</span>' ?></span></div>
          <div class="info-field"><label>Precio por día</label><span><?= $user['precio_dia'] ? '$' . number_format($user['precio_dia'], 0) . ' MXN' : '<span class="empty">No definido</span>' ?></span></div>
          <div class="info-field"><label>Calificación</label>
            <span>
              <?php
                $cal = floatval($user['calificacion_promedio'] ?? 0);
                echo str_repeat('★', (int)round($cal)) . str_repeat('☆', 5-(int)round($cal));
                echo " ($cal / 5)";
              ?>
            </span>
          </div>
          <div class="info-field"><label>Trabajos completados</label><span><?= $user['total_trabajos'] ?? 0 ?></span></div>
        </div>
        <?php if ($user['descripcion']): ?>
        <div style="margin-top:16px;padding-top:16px;border-top:1.5px solid var(--cream-dk)">
          <label style="font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--earth);display:block;margin-bottom:6px">Descripción</label>
          <p style="font-size:.9rem;line-height:1.6;color:var(--ink)"><?= nl2br(htmlspecialchars($user['descripcion'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="disponible-row">
          <span class="toggle-label">Disponible para trabajar</span>
          <label class="toggle">
            <input type="checkbox" id="toggleDisponible" <?= $user['disponible'] ? 'checked' : '' ?> onchange="toggleDisponibilidad(this)"/>
            <span class="toggle-track"></span>
            <span class="toggle-thumb"></span>
          </label>
          <span id="disponibleLabel" style="font-size:.85rem;color:var(--earth)"><?= $user['disponible'] ? '✅ Disponible' : '⏸ No disponible' ?></span>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ===== SECCIÓN BUSCAR (solo cliente) ===== -->
    <?php if ($usuario_tipo === 'cliente'): ?>
    <div id="sec-buscar" style="display:none">
      <div class="info-card">
        <h3>🔍 Buscar trabajadores</h3>
        <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
          <select id="filtroOficio" style="padding:10px 14px;border:1.5px solid var(--cream-dk);border-radius:8px;font-family:var(--ff-body);font-size:.9rem;background:var(--white);color:var(--ink)">
            <option value="">Todos los oficios</option>
            <?php
            $oficios = db()->query('SELECT id, icono, nombre FROM oficios WHERE activo=1 ORDER BY nombre')->fetchAll();
            foreach($oficios as $o):
            ?>
            <option value="<?= $o['id'] ?>"><?= $o['icono'] ?> <?= htmlspecialchars($o['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <button onclick="cargarTrabajadores()" style="padding:10px 24px;background:var(--amber);border:none;border-radius:8px;font-weight:700;cursor:pointer;font-family:var(--ff-body)">Buscar</button>
        </div>
        <div id="resultadosTrabajadores">
          <div class="empty-state">
            <div class="es-icon">👷</div>
            <p>Selecciona un oficio o haz clic en <strong>Buscar</strong> para ver todos los trabajadores disponibles.</p>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ===== SECCIÓN SOLICITUDES ===== -->
    <div id="sec-solicitudes" style="display:none">
      <div class="info-card">
        <h3>📋 Mis solicitudes</h3>
        <div class="empty-state">
          <div class="es-icon">📭</div>
          <p>Aún no tienes solicitudes registradas.<br/>
          <?= $usuario_tipo === 'cliente' ? 'Busca un trabajador y contáctalo para empezar.' : 'Los clientes podrán contactarte a través de tu perfil.' ?></p>
        </div>
      </div>
    </div>

    <!-- ===== SECCIÓN CALIFICACIONES (solo trabajador) ===== -->
    <?php if ($usuario_tipo === 'trabajador'): ?>
    <div id="sec-calificaciones" style="display:none">
      <div class="info-card">
        <h3>⭐ Mis calificaciones</h3>
        <?php if ($user['total_trabajos'] > 0): ?>
          <p>Promedio: <?= number_format($user['calificacion_promedio'],2) ?> / 5.0 (<?= $user['total_trabajos'] ?> reseñas)</p>
        <?php else: ?>
          <div class="empty-state">
            <div class="es-icon">⭐</div>
            <p>Aún no tienes calificaciones.<br/>Completa trabajos para recibir reseñas de tus clientes.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</main>

<script>
// ── NAVEGACIÓN ──
const sections = ['inicio','perfil','buscar','solicitudes','calificaciones'];
const titles = {
  inicio:'Mi panel', perfil:'Mi perfil', buscar:'Buscar trabajadores',
  solicitudes:'Mis solicitudes', calificaciones:'Mis calificaciones'
};
function showSection(name) {
  sections.forEach(s => {
    const el = document.getElementById('sec-'+s);
    if (el) el.style.display = (s===name) ? 'block' : 'none';
  });
  document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
  event.currentTarget.classList.add('active');
  document.getElementById('topbarTitle').textContent = titles[name] || 'Mi panel';
}

// ── CERRAR SESIÓN ──
function cerrarSesion(btn) {
  // UX: Feedback visual inmediato para evitar doble clic y dar certeza
  const textoOriginal = btn.innerHTML;
  btn.innerHTML = '<span>⏳</span> Cerrando...';
  btn.style.pointerEvents = 'none'; // Deshabilita clics adicionales
  btn.style.opacity = '0.7';

  // Pequeño retraso de 300ms para que el usuario perciba la acción 
  // y redirigimos correctamente al archivo en la raíz
  setTimeout(() => {
    window.location.href = 'php/logout.php';
  }, 300);
}

// ── DISPONIBILIDAD ──
async function toggleDisponibilidad(el) {
  const disponible = el.checked ? 1 : 0;
  const lbl = document.getElementById('disponibleLabel');
  lbl.textContent = disponible ? '✅ Disponible' : '⏸ No disponible';
  await fetch('php/disponibilidad.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({disponible})
  });
}

// ── BUSCAR TRABAJADORES ──
async function cargarTrabajadores() {
  const oficio = document.getElementById('filtroOficio').value;
  const container = document.getElementById('resultadosTrabajadores');
  container.innerHTML = '<p style="color:var(--earth);font-size:.9rem;padding:12px 0">Cargando…</p>';

  const url = `php/trabajadores.php${oficio ? '?oficio='+oficio : ''}`;
  const res  = await fetch(url);
  const data = await res.json();

  if (!data.ok || !data.data.length) {
    container.innerHTML = `<div class="empty-state"><div class="es-icon">🔍</div><p>No se encontraron trabajadores disponibles para ese oficio.</p></div>`;
    return;
  }

  container.innerHTML = data.data.map(t => `
    <div style="display:flex;align-items:center;gap:16px;padding:16px;background:var(--white);border-radius:12px;margin-bottom:12px;box-shadow:0 2px 8px var(--shadow);">
      <div style="width:50px;height:50px;border-radius:50%;background:var(--cream-dk);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">${t.oficio_icono}</div>
      <div style="flex:1">
        <div style="font-family:var(--ff-head);font-weight:700;font-size:.95rem">${t.nombre_completo}</div>
        <div style="font-size:.82rem;color:var(--earth);margin-top:2px">${t.oficio} · ${t.años_experiencia || 0} años de exp.</div>
        <div style="font-size:.8rem;color:var(--amber-dk);margin-top:4px">⭐ ${parseFloat(t.calificacion_promedio||0).toFixed(1)} · ${t.total_trabajos||0} trabajos</div>
      </div>
      <div style="text-align:right">
        ${t.precio_hora ? `<div style="font-family:var(--ff-head);font-weight:700;font-size:.95rem">$${parseInt(t.precio_hora)}/hr</div>` : ''}
        <!-- Botón Ghost para reducir peso visual -->
        <a href="tel:${t.telefono}" style="display:inline-block;margin-top:6px;padding:6px 14px;background:transparent;color:var(--ink);border:1.5px solid var(--cream-dk);border-radius:50px;font-size:.78rem;font-weight:700;text-decoration:none;transition: border-color .2s" onmouseover="this.style.borderColor='var(--amber)'" onmouseout="this.style.borderColor='var(--cream-dk)'">📞 Llamar</a>
      </div>
    </div>
  `).join('');
}
</script>

</body>
</html>
