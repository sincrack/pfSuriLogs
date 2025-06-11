<?php
// --- v20.0: VERSIÓN FINAL SEGURA Y COMPLETA. Incluye sistema de login y todo el código funcional. ---

// --- ⚙️ 1. CONFIGURACIÓN DE SEGURIDAD ---
// Define aquí tu nombre de usuario.
define('USERNAME', 'admin'); 

// Pega aquí el HASH que generaste con el script 'generar_hash.php'.
define('PASSWORD_HASH', 'AQUÍ_ENTRA_EL_HASH_GENERADO_SIN_ESPACIOS'); 

// --- INICIO DE LA SESIÓN (DEBE SER LO PRIMERO EN EL FICHERO) ---
session_start();

// --- Lógica de Logout ---
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- Lógica de Login ---
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    if ($_POST['username'] === USERNAME && password_verify($_POST['password'], PASSWORD_HASH)) {
        $_SESSION['is_logged_in'] = true;
        $_SESSION['username'] = USERNAME;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Usuario o contraseña incorrectos.';
    }
}

// --- Función para comprobar si el usuario está logueado ---
function is_user_logged_in(): bool {
    return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

// --- Si el usuario no está logueado, mostramos el formulario y paramos la ejecución ---
if (!is_user_logged_in()) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - pfSuriLogs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #2b2b2b; margin: 0; padding: 20px; box-sizing: border-box; }
        .login-container { background-color: #3c3c3c; padding: 40px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); text-align: center; color: #d4d4d4; width: 100%; max-width: 400px; }
        .login-container img { max-width: 200px; margin-bottom: 20px; }
        .login-container h1 { color: #5da5d5; margin-top: 0; font-size: 1.5em; }
        .login-container input { display: block; width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #666; background-color: #2b2b2b; color: #d4d4d4; box-sizing: border-box; font-size: 1em; }
        .login-container button { width: 100%; padding: 12px; border: none; border-radius: 4px; background-color: #5da5d5; color: #fff; font-weight: bold; cursor: pointer; font-size: 1.1em; transition: transform 0.2s ease; }
        .login-container button:hover { transform: scale(1.02); }
        .login-container .error { color: #ff6347; margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="https://sincrack.com/loguito.png" alt="Logo">
        <h2>Acceso a pfSuriLogs</h2>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
        <?php if ($login_error): ?>
            <p class="error"><?php echo $login_error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
    exit; // Importante: detenemos la ejecución del script aquí.
}

// --- A PARTIR DE AQUÍ, EL CÓDIGO COMPLETO DEL VISOR RECUERDA EDITAR LAS RUTAS Y NOMBRES DE TUS INTERFACES---
define('INTERFACES', [
    'wan1' => ['name' => 'WAN1 (pppoe164118)', 'path' => '/var/log/suricata/suricata_pppoe164118/'],
    'lan' => ['name' => 'LAN (vtnet131562)', 'path' => '/var/log/suricata/suricata_vtnet131562/'],
    'wan2' => ['name' => 'WAN2 (vtnet244995)', 'path' => '/var/log/suricata/suricata_vtnet244995/'],
]);
define('LOG_TYPES', [
    'eve_json' => ['name' => 'EVE JSON (Eventos)', 'pattern' => 'eve.json*', 'view' => 'eve_json'],
    'block' => ['name' => 'Block Log (Bloqueos)', 'pattern' => 'block.log', 'view' => 'generic_text'],
    'http' => ['name' => 'HTTP Log', 'pattern' => 'http.log', 'view' => 'generic_text'],
    'passlist_debug' => ['name' => 'Passlist Debug (Permitidos)', 'pattern' => 'passlist_debug.log', 'view' => 'color_coded_text'],
    'alerts' => ['name' => 'Alerts Log (Texto)', 'pattern' => 'alerts.log', 'view' => 'generic_text'],
    'suricata' => ['name' => 'Suricata Log (Texto)', 'pattern' => 'suricata.log', 'view' => 'generic_text'],
]);
define('ITEMS_PER_PAGE', 100);
define('MAX_LOG_FILES_TO_READ', 10);
define('PAGINATION_RANGE', 7);
function e(?string $string): string { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }
function get_nested_value(array $array, string $key, $default = null) { $keys = explode('.', $key); foreach ($keys as $k) { if (!isset($array[$k])) return $default; $array = $array[$k]; } return $array; }
$selected_interface_key = $_GET['interface'] ?? null;
$selected_log_type_key = $_GET['log_type'] ?? 'eve_json';
$interface_data = $selected_interface_key ? (INTERFACES[$selected_interface_key] ?? null) : null;
$log_type_data = $interface_data ? (LOG_TYPES[$selected_log_type_key] ?? null) : null;

if ($interface_data && $log_type_data) {
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $error_message = '';
    $log_dir = $interface_data['path'];
    $file_pattern = $log_dir . $log_type_data['pattern'];
    $log_files = glob($file_pattern);
    $filter_event_type = $_GET['event_type'] ?? '';
    $filter_src_ip = $_GET['src_ip'] ?? '';
    $filter_dest_ip = $_GET['dest_ip'] ?? '';
    $filter_proto = $_GET['proto'] ?? '';
    $filter_detail = $_GET['detail'] ?? '';
    $search_filter = $_GET['search'] ?? '';
    $total_items = 0;
    $paginated_items = [];
    
    if (empty($log_files)) {
        $error_message = "No se encontraron ficheros de log para el patrón: " . e($log_type_data['pattern']);
    } else {
        $files_to_process = ($log_type_data['view'] === 'eve_json') ? array_slice(array_reverse($log_files), 0, MAX_LOG_FILES_TO_READ) : [$log_files[0]];
        $eve_filter_logic = function($item) use ($filter_event_type, $filter_src_ip, $filter_dest_ip, $filter_proto, $filter_detail) {
            if (!$item) return false;
            if ($filter_event_type && $item['event_type'] !== $filter_event_type) return false;
            if ($filter_src_ip && $item['src_ip'] !== $filter_src_ip) return false;
            if ($filter_dest_ip && $item['dest_ip'] !== $filter_dest_ip) return false;
            if ($filter_proto && strcasecmp(get_nested_value($item, 'proto', ''), $filter_proto) !== 0) return false;
            if ($filter_detail) {
                $detail_string = '';
                if (isset($item['alert']['signature'])) $detail_string .= $item['alert']['signature'];
                if (isset($item['http']['hostname']))  $detail_string .= ' ' . $item['http']['hostname'];
                if (isset($item['http']['url']))       $detail_string .= ' ' . $item['http']['url'];
                if (isset($item['dns']['rrname']))     $detail_string .= ' ' . $item['dns']['rrname'];
                if (isset($item['tls']['sni']))        $detail_string .= ' ' . $item['tls']['sni'];
                if (stripos($detail_string, $filter_detail) === false) return false;
            }
            return true;
        };

        foreach ($files_to_process as $filepath) {
            $handle = @fopen($filepath, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line); if(empty($line)) continue;
                    $passes_filter = false;
                    if ($log_type_data['view'] === 'eve_json') {
                        if ($eve_filter_logic(json_decode($line, true))) { $passes_filter = true; }
                    } else { if (!$search_filter || stripos($line, $search_filter) !== false) { $passes_filter = true; } }
                    if ($passes_filter) $total_items++;
                }
                fclose($handle);
            }
        }
        
        $total_pages = ceil($total_items / ITEMS_PER_PAGE);
        if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
        $page_to_fetch = $current_page;
        if ($log_type_data['view'] !== 'eve_json' && $total_pages > 0) {
            $page_to_fetch = $total_pages - $current_page + 1;
        }
        $start_index = ($page_to_fetch - 1) * ITEMS_PER_PAGE;
        
        $current_item_index = 0;
        foreach ($files_to_process as $filepath) {
            if (count($paginated_items) >= ITEMS_PER_PAGE) break;
            $handle = @fopen($filepath, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line); if(empty($line)) continue;
                    if (count($paginated_items) >= ITEMS_PER_PAGE) break 2;
                    $item_data = null; $passes_filter = false;
                     if ($log_type_data['view'] === 'eve_json') {
                        $item_data = json_decode($line, true);
                        if ($eve_filter_logic($item_data)) { $passes_filter = true; }
                    } else {
                        if (!$search_filter || stripos($line, $search_filter) !== false) {
                            $item_data = ['line' => $line, 'num' => 0]; // num se calculará luego
                            $passes_filter = true;
                        }
                    }
                    if ($passes_filter) { if ($current_item_index >= $start_index) { $paginated_items[] = $item_data; } $current_item_index++; }
                }
                fclose($handle);
            }
        }
        if ($log_type_data['view'] !== 'eve_json') {
            foreach($paginated_items as $i => &$item) { $item['num'] = $total_items - $start_index - $i; }
            unset($item);
        }
        $paginated_items = array_reverse($paginated_items);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pfSuriLogs - by SinCracK</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #2b2b2b; color: #d4d4d4; margin: 0; padding: 20px; }
        .container { max-width: 1600px; margin: auto; background-color: #3c3c3c; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
        h1, h2, h3 { color: #5da5d5; }
        h2, h3 { border-bottom: 2px solid #555; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        th, td { padding: 10px 12px; border: 1px solid #555; text-align: left; font-size: 0.9em; word-wrap: break-word; }
        th { background-color: #4a4a4a; color: #e0e0e0; }
        tr:nth-child(even) { background-color: #424242; } tr:hover { background-color: #5a5a5a; }
        .filter-form { background-color: #4a4a4a; padding: 15px; border-radius: 6px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; align-items: end; }
        .filter-form label { display: block; margin-bottom: 5px; font-weight: bold; }
        .filter-form input, .filter-form select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #666; background-color: #2b2b2b; color: #d4d4d4; box-sizing: border-box; }
        .filter-form button { padding: 10px 20px; border: none; border-radius: 4px; background-color: #5da5d5; color: #fff; font-weight: bold; cursor: pointer; grid-column: -1; transition: transform 0.2s ease; }
        .filter-form button:hover { transform: scale(1.05); }
        .error { padding: 15px; border-radius: 6px; margin-bottom: 20px; background-color: #8B0000; color: white; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 14px; margin: 0 4px; border-radius: 4px; background-color: #4a4a4a; color: #d4d4d4; text-decoration: none; }
        .pagination a:hover { background-color: #5da5d5; color: #fff; } .pagination .current { background-color: #5da5d5; color: #fff; font-weight: bold; }
        .pagination .disabled { background-color: #3a3a3a; color: #888; cursor: default; }
        .text-log-viewer { background-color: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', Courier, monospace; font-size: 0.9em; border: 1px solid #555; border-radius: 4px; padding: 15px; white-space: pre; overflow-x: auto; }
        .text-log-viewer .line-number { color: #858585; display: inline-block; width: 60px; text-align: right; padding-right: 15px; user-select: none; }
        .highlight { background-color: #f0e68c; color: #2b2b2b; font-weight: bold; padding: 0 2px; border-radius: 2px;}
        .view-json-btn { color: #5da5d5; text-decoration: underline; cursor: pointer; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7); backdrop-filter: blur(5px); }
        .modal-content { background-color: #3c3c3c; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); position: relative; }
        .modal-close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        #jsonModalContent { background-color: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; max-height: 60vh; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
        .log-info { color: #87ceeb !important; } .log-notice { color: #98fb98 !important; }
        .log-warning { color: #ffd700 !important; } .log-error { color: #ff6347 !important; }
        .logo-container { text-align: center; margin-bottom: 20px; padding-top: 10px; }
        .logo { max-width: 250px; height: auto; }
        footer { text-align: center; margin-top: 30px; color: #999; font-size: 0.9em; }
        .selector-container { text-align: center; padding: 40px 20px; }
        .selector-container h1 { font-size: 2.2em; margin-bottom: 15px; border-bottom: none; }
        .selector-container p { font-size: 1.2em; color: #ccc; margin-bottom: 30px; }
        .selector-container select, .selector-container button { font-size: 1.3em; padding: 12px; }
        .selector-container button { transition: transform 0.2s ease; }
        .selector-container button:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="https://sincrack.com/loguito.png" alt="Logo SinCracK" class="logo">
        </div>
        <?php if (!$interface_data): ?>
            <div class="selector-container">
                 <h1>Visor de Logs Suricata</h1>
                 <p>Selecciona una interfaz para comenzar el análisis.</p>
                 <form method="GET" action=""><select name="interface"><option value="" disabled selected>-- Elige una opción --</option><?php foreach (INTERFACES as $key => $iface): ?><option value="<?php echo e($key); ?>"><?php echo e($iface['name']); ?></option><?php endforeach; ?></select><button type="submit">Ver Logs</button></form>
            </div>
        <?php else: ?>
            <div style="float: right; margin-top: -60px; color: #aaa; text-align: right;">
                Usuario: <strong><?php echo e($_SESSION['username']); ?></strong><br>
                <a href="?logout=true" style="color: #5da5d5;">Cerrar Sesión</a>
            </div>
            <h2>Visor de Logs en: <?php echo e($interface_data['name']); ?></h2>
            <?php if ($error_message): ?><div class="error"><?php echo e($error_message); ?></div><?php endif; ?>
            
            <h3>Controles</h3>
            <form class="filter-form" method="GET" action="">
                <input type="hidden" name="interface" value="<?php echo e($selected_interface_key); ?>">
                <div>
                    <label for="interface_switcher">Cambiar Interfaz</label>
                    <select id="interface_switcher" name="interface" onchange="this.form.submit()">
                        <?php foreach (INTERFACES as $key => $iface): ?>
                            <option value="<?php echo e($key); ?>" <?php echo ($key === $selected_interface_key) ? 'selected' : ''; ?>><?php echo e($iface['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="log_type">Tipo de Log</label>
                    <select id="log_type" name="log_type" onchange="this.form.submit()">
                        <?php foreach (LOG_TYPES as $key => $type): ?><option value="<?php echo e($key); ?>" <?php echo ($key === $selected_log_type_key) ? 'selected' : ''; ?>><?php echo e($type['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($log_type_data['view'] === 'eve_json'): ?>
                    <div><label for="event_type">Tipo de Evento</label><select id="event_type" name="event_type"><option value="">-- Todos --</option><option value="alert" <?php echo $filter_event_type === 'alert' ? 'selected' : ''; ?>>Alert</option><option value="http" <?php echo $filter_event_type === 'http' ? 'selected' : ''; ?>>HTTP</option><option value="dns" <?php echo $filter_event_type === 'dns' ? 'selected' : ''; ?>>DNS</option><option value="tls" <?php echo $filter_event_type === 'tls' ? 'selected' : ''; ?>>TLS</option><option value="flow" <?php echo $filter_event_type === 'flow' ? 'selected' : ''; ?>>Flow</option></select></div>
                    <div><label for="src_ip">IP Origen</label><input type="text" id="src_ip" name="src_ip" value="<?php echo e($_GET['src_ip'] ?? ''); ?>"></div>
                    <div><label for="dest_ip">IP Destino</label><input type="text" id="dest_ip" name="dest_ip" value="<?php echo e($_GET['dest_ip'] ?? ''); ?>"></div>
                    <div><label for="proto">Protocolo</label><input type="text" id="proto" name="proto" value="<?php echo e($_GET['proto'] ?? ''); ?>" placeholder="TCP, UDP..."></div>
                    <div><label for="detail">Buscar en Detalle</label><input type="text" id="detail" name="detail" value="<?php echo e($_GET['detail'] ?? ''); ?>" placeholder="Firma, Host, Dominio..."></div>
                <?php else: ?>
                    <div><label for="search">Buscar y Resaltar</label><input type="text" id="search" name="search" value="<?php echo e($_GET['search'] ?? ''); ?>" placeholder="Filtrar y resaltar texto..."></div>
                <?php endif; ?>
                <button type="submit">🔍 Aplicar</button>
            </form>

            <h3>Resultados (Página <?php echo $current_page; ?> de <?php echo $total_pages ?? 0; ?>. Total: <?php echo $total_items ?? 0; ?>)</h3>
            
            <?php if (isset($log_type_data)): ?>
                <?php if ($log_type_data['view'] === 'eve_json'): ?>
                    <table>
                        <thead><tr><th>Timestamp</th><th>Origen</th><th>Destino</th><th>Tipo</th><th>Protocolo</th><th style="width: 35%;">Detalle</th><th>JSON</th></tr></thead>
                        <tbody>
                            <?php if(empty($paginated_items)): ?> <tr><td colspan="7" style="text-align:center;">No se encontraron eventos.</td></tr> <?php endif; ?>
                            <?php foreach($paginated_items as $event): ?>
                            <tr><td><?php echo e(str_replace('T', ' ', substr($event['timestamp'], 0, 19))); ?></td><td><?php echo e($event['src_ip'] ?? 'N/A'); ?>:<?php echo e($event['src_port'] ?? 'N/A'); ?></td><td><?php echo e($event['dest_ip'] ?? 'N/A'); ?>:<?php echo e($event['dest_port'] ?? 'N/A'); ?></td><td><?php $type = e($event['event_type']); echo "<span style='background-color:#c9302c;padding:3px 7px;border-radius:4px;'>{$type}</span>"; ?></td><td><?php echo e($event['proto'] ?? 'N/A'); ?></td>
                            <td><?php $detail = ''; switch ($event['event_type']) {
                                    case 'alert': $detail = "<b>Sig:</b> " . e(get_nested_value($event, 'alert.signature')); break;
                                    case 'http': $detail = "<b>Host:</b> " . e(get_nested_value($event, 'http.hostname')); break;
                                    case 'dns': $detail = "<b>Query:</b> " . e(get_nested_value($event, 'dns.rrname')); break;
                                    case 'tls': $detail = "<b>SNI:</b> " . e(get_nested_value($event, 'tls.sni')); break;
                                    case 'flow': $detail = "<b>Pkts:</b> " . e(get_nested_value($event, 'flow.pkts_toserver', 0) + get_nested_value($event, 'flow.pkts_toclient', 0)) . " <b>Bytes:</b> " . e(get_nested_value($event, 'flow.bytes_toserver', 0) + get_nested_value($event, 'flow.bytes_toclient', 0)); break;
                                    default: $detail = '...';
                                } echo $detail;
                            ?></td>
                            <td><a href="#" class="view-json-btn" data-jsondata="<?php echo base64_encode(json_encode($event)); ?>">Ver</a></td></tr>
                            <?php endforeach; ?>
                         </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-log-viewer">
                        <?php if(empty($paginated_items)): ?> <div>No se encontraron registros.</div> <?php endif; ?>
                        <?php foreach($paginated_items as $i => $item):
                            $text_class = '';
                            if ($log_type_data['view'] === 'color_coded_text') {
                                if (stripos($item['line'], '<Info>')) $text_class = 'log-info';
                                elseif (stripos($item['line'], '<Notice>')) $text_class = 'log-notice';
                                elseif (stripos($item['line'], '<Warning>')) $text_class = 'log-warning';
                                elseif (stripos($item['line'], '<Error>')) $text_class = 'log-error';
                            }
                            $line_content = e($item['line']);
                            if ($search_filter) {
                                $highlighted_term = '<span class="highlight">' . e($search_filter) . '</span>';
                                $line_content = str_ireplace(e($search_filter), $highlighted_term, $line_content);
                            }
                        ?>
                        <div><span class="line-number"><?php echo e($item['num']); ?></span><span class="<?php echo $text_class; ?>"><?php echo $line_content; ?></span></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="pagination">
                 <?php if (isset($total_pages) && $total_pages > 1) { $queryParams = $_GET;
                    if ($current_page > 1) { $queryParams['page'] = 1; echo '<a href="?' . http_build_query($queryParams) . '">&laquo;&laquo;</a>'; $queryParams['page'] = $current_page - 1; echo '<a href="?' . http_build_query($queryParams) . '">&laquo; Anterior</a>'; } else { echo '<span class="disabled">&laquo;&laquo;</span><span class="disabled">&laquo; Anterior</span>'; }
                    $range = PAGINATION_RANGE; $start = max(1, $current_page - floor($range / 2)); $end = min($total_pages, $current_page + floor($range / 2));
                    if ($start > 1) { $queryParams['page'] = 1; echo '<a href="?' . http_build_query($queryParams) . '">1</a>'; if ($start > 2) { echo '<span class="ellipsis">...</span>'; } }
                    for ($i = $start; $i <= $end; $i++) { $queryParams['page'] = $i; if ($i == $current_page) { echo '<span class="current">' . $i . '</span>'; } else { echo '<a href="?' . http_build_query($queryParams) . '">' . $i . '</a>'; } }
                    if ($end < $total_pages) { if ($end < $total_pages - 1) { echo '<span class="ellipsis">...</span>'; } $queryParams['page'] = $total_pages; echo '<a href="?' . http_build_query($queryParams) . '">' . $total_pages . '</a>'; }
                    if ($current_page < $total_pages) { $queryParams['page'] = $current_page + 1; echo '<a href="?' . http_build_query($queryParams) . '">Siguiente &raquo;</a>'; $queryParams['page'] = $total_pages; echo '<a href="?' . http_build_query($queryParams) . '">&raquo;&raquo;</a>'; } else { echo '<span class="disabled">Siguiente &raquo;</span><span class="disabled">&raquo;&raquo;</span>'; }
                } ?>
            </div>
        <?php endif; ?>
    </div>

<footer>
    <p>Esta aplicación web ha sido desarrollada por SinCracK. Todos los derechos reservados.</p>

    <a href="https://www.paypal.me/SinCracK" target="_blank">
        <img src="https://img.shields.io/badge/Invítame_un_café-FF813F?style=for-the-badge&logo=buy-me-a-coffee&logoColor=white" 
             alt="Invítame un café" />
    </a>
</footer>



    <div id="jsonModal" class="modal">
        <div class="modal-content">
            <span class="modal-close-btn">&times;</span>
            <h2>Detalle del Evento JSON</h2>
            <pre id="jsonModalContent"></pre>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('jsonModal');
            if (!modal) return;
            const modalContent = document.getElementById('jsonModalContent');
            const closeBtn = document.querySelector('.modal-close-btn');

            const closeModal = function () {
                modal.style.display = 'none';
                modalContent.textContent = '';
            };

            document.body.addEventListener('click', function (event) {
                if (event.target.classList.contains('view-json-btn')) {
                    event.preventDefault();
                    const jsonDataBase64 = event.target.getAttribute('data-jsondata');
                    try {
                        const decodedJsonString = atob(jsonDataBase64);
                        const jsonObject = JSON.parse(decodedJsonString);
                        modalContent.textContent = JSON.stringify(jsonObject, null, 2);
                        modal.style.display = 'block';
                    } catch (e) {
                        modalContent.textContent = 'Error: No se pudo interpretar el JSON.';
                        modal.style.display = 'block';
                    }
                }
            });
            
            if(closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            modal.addEventListener('click', function (event) { if (event.target === modal) { closeModal(); } });
            window.addEventListener('keydown', function (event) { if (event.key === 'Escape' && modal.style.display === 'block') { closeModal(); } });
        });
    </script>
</body>
</html>