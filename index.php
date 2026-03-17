<?php
session_start();

// Função para ler o keys.json
function loadKeys() {
    $keysJson = file_get_contents('keys.json');
    return json_decode($keysJson, true);
}

// Lógica de validação da chave (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'validate_key') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $keyInput = isset($_POST['key']) ? strtoupper(trim($_POST['key'])) : '';
    $hwid = isset($_POST['hwid']) ? trim($_POST['hwid']) : '';

    if (empty($keyInput)) {
        $response['message'] = 'Por favor, insira uma key.';
        echo json_encode($response);
        exit();
    }

    $keysData = loadKeys();
    $validKeys = $keysData['keys'] ?? [];

    if (isset($validKeys[$keyInput])) {
        $keyData = $validKeys[$keyInput];
        $savedHwid = $_SESSION['key_hwid_' . $keyInput] ?? null;

        if (!$savedHwid) {
            $_SESSION['key_hwid_' . $keyInput] = $hwid;
            $_SESSION['active_key'] = $keyInput;
            $_SESSION['key_level'] = $keyData['level'] ?? 'BASIC';
            $response['success'] = true;
        } elseif ($savedHwid === $hwid) {
            $_SESSION['active_key'] = $keyInput;
            $_SESSION['key_level'] = $keyData['level'] ?? 'BASIC';
            $response['success'] = true;
        } else {
            $response['message'] = 'Key já usada em outro dispositivo (HWID).';
        }
    } else {
        $response['message'] = 'Key inválida ou não encontrada!';
    }

    echo json_encode($response);
    exit();
}

// Lógica de logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// Verifica estado de login
$loggedIn = isset($_SESSION['active_key']);
$activeKey = $loggedIn ? $_SESSION['active_key'] : '';
$keyLevel = $loggedIn ? ($_SESSION['key_level'] ?? 'BASIC') : 'BASIC';

// Carrega dados do plano para o front-end
$keysData = loadKeys();
$currentPlanData = $keysData['keys'][$activeKey] ?? ['level' => 'BASIC', 'limit' => 5000];
$KEY_LIMIT = $currentPlanData['limit'] ?? 5000;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Gerador</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,700&display=swap');

*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}

:root{
  --blue:#1a56e8;--blue-d:#1240c0;--blue-l:#e8effe;
  --green:#22c55e;--red:#ef4444;--orange:#f59e0b;
  --gray:#6b7280;--bg:#f0f2f7;--white:#ffffff;
  --text:#111827;--text2:#6b7280;--radius:16px;
  --shadow:0 2px 14px rgba(0,0,0,0.08);
}

html,body{font-family:'DM Sans',sans-serif;background:#c7cfe0;display:flex;justify-content:center;align-items:center;min-height:100vh;min-height:100dvh;}

.phone{width:100vw;max-width:430px;height:100dvh;max-height:932px;background:var(--bg);position:relative;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 0 80px rgba(0,0,0,0.35);}

/* ===== LOGIN SYSTEM STYLES ===== */
#login-page {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(145deg, #1a56e8 0%, #1240c0 100%);
  z-index: 99999;
  display: <?php echo $loggedIn ? 'none' : 'flex'; ?>;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  color: white;
  text-align: center;
}
.login-container {
  width: 100%;
  max-width: 320px;
  background: rgba(255, 255, 255, 0.1);
  padding: 30px;
  border-radius: 24px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.login-logo { font-size: 48px; margin-bottom: 20px; }
.login-title { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
.login-subtitle { font-size: 14px; opacity: 0.8; margin-bottom: 30px; }
.login-input {
  width: 100%;
  padding: 15px;
  border-radius: 12px;
  border: none;
  background: white;
  color: #111;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 15px;
  outline: none;
  text-align: center;
  text-transform: uppercase;
}
.login-btn {
  width: 100%;
  padding: 15px;
  border-radius: 12px;
  border: none;
  background: #22c55e;
  color: white;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.1s;
}
.login-btn:active { transform: scale(0.98); }
.login-error { color: #ff4d4d; font-size: 13px; margin-top: 15px; font-weight: 600; display: none; }

.page{display:none;flex-direction:column;height:100%;overflow:hidden;width:100%;}
.page.active{display:flex;width:100%;}
.scroll-area{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding-bottom:84px;width:100%;}
.scroll-area::-webkit-scrollbar{display:none;}

/* ===== HOME ===== */
.home-header{background:linear-gradient(145deg,#1a56e8 0%,#1240c0 100%);padding:20px 20px 48px;flex-shrink:0;}
.home-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.user-info{display:flex;align-items:center;gap:12px;}
.avatar{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#0d0d0d,#2a2a2a);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#f59e0b;font-style:italic;border:2px solid rgba(255,255,255,0.2);}
.user-name{color:#fff;font-size:17px;font-weight:700;line-height:1.2;}
.user-plan{color:rgba(255,255,255,0.65);font-size:12px;margin-top:2px;}
.header-icons{display:flex;gap:14px;}
.header-icons button{background:rgba(255,255,255,0.15);border:none;cursor:pointer;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;transition:background 0.15s;}
.header-icons button:active{background:rgba(255,255,255,0.25);}

.limit-bar-wrap{background:rgba(255,255,255,0.1);border-radius:12px;padding:10px 14px;margin-bottom:14px;}
.limit-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.limit-label{color:rgba(255,255,255,0.8);font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;}
.limit-count{color:#fff;font-size:13px;font-weight:700;}
.limit-bar-bg{background:rgba(255,255,255,0.2);border-radius:99px;height:5px;overflow:hidden;}
.limit-bar-fill{height:100%;background:linear-gradient(90deg,#4ade80,#22c55e);border-radius:99px;transition:width 0.5s ease;}

.stats-cards{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.stat-card{background:rgba(255,255,255,0.13);border-radius:14px;padding:14px;color:#fff;cursor:pointer;transition:transform 0.15s;border:1px solid rgba(255,255,255,0.1);}
.stat-card:active{transform:scale(0.97);}
.stat-label{font-size:11px;opacity:0.75;display:flex;align-items:center;gap:5px;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.04em;font-weight:600;}
.stat-num{font-size:30px;font-weight:800;}
.stat-footer{display:flex;align-items:center;justify-content:space-between;margin-top:8px;}
.stat-sub{font-size:11px;opacity:0.75;}
.stat-sub-val{font-size:13px;font-weight:700;color:#fff;}
.go-btn{width:28px;height:28px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0;}

.home-body{background:var(--bg);border-radius:24px 24px 0 0;margin-top:-20px;flex:1;overflow-y:auto;padding:22px 14px 0;}
.home-body::-webkit-scrollbar{display:none;}

.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:22px;}
.qa-btn{display:flex;flex-direction:column;align-items:center;gap:7px;cursor:pointer;}
.qa-icon{width:62px;height:62px;border-radius:16px;background:var(--white);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow);transition:transform 0.15s;}
.qa-btn:active .qa-icon{transform:scale(0.9);}
.qa-label{font-size:11px;color:var(--text);font-weight:600;text-align:center;line-height:1.3;}

.section-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:10px;}
.chart-card{background:var(--white);border-radius:var(--radius);padding:16px;box-shadow:var(--shadow);margin-bottom:16px;}
.chart-legend{display:flex;align-items:center;gap:8px;margin-bottom:14px;}
.chart-dot{width:10px;height:10px;border-radius:50%;background:var(--blue);}
.chart-legend-label{font-size:13px;font-weight:600;color:var(--text);}
canvas{width:100%!important;display:block;}

/* ===== PAGE HEADER ===== */
.page-header{background:var(--white);padding:14px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0;border-bottom:1px solid #f0f2f7;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.page-header-title{font-size:17px;font-weight:700;color:var(--text);flex:1;}
.page-header-actions{display:flex;align-items:center;gap:10px;}
.ph-btn{background:none;border:none;cursor:pointer;padding:5px;display:flex;border-radius:8px;transition:background 0.1s;}
.ph-btn:active{background:var(--bg);}
.ph-btn-text{background:none;border:none;cursor:pointer;font-size:14px;font-weight:700;color:var(--red);font-family:inherit;padding:4px 8px;border-radius:8px;}
.ph-btn-text:active{background:#fee2e2;}

.search-bar{padding:10px 14px;background:var(--white);border-bottom:1px solid #f0f2f7;flex-shrink:0;}
.search-input{width:100%;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;outline:none;background:var(--bg);transition:border-color 0.2s;}
.search-input:focus{border-color:var(--blue);}

/* ===== LIST ITEMS ===== */
.list-item{background:var(--white);border-bottom:1px solid #f3f4f6;padding:14px 14px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:background 0.1s;}
.list-item:first-child{border-top:1px solid #f3f4f6;}
.list-item:active{background:#f9fafb;}
.list-item.selected{background:#eff6ff;}

.item-check{width:22px;height:22px;border-radius:50%;border:2px solid #c7d2fe;flex-shrink:0;transition:all 0.15s;display:flex;align-items:center;justify-content:center;}
.item-check.checked{background:var(--blue);border-color:var(--blue);}
.item-check.checked::after{content:'';width:6px;height:6px;border-radius:50%;background:#fff;}

.device-action-btn{width:22px;height:22px;border-radius:50%;border:2px solid #c7d2fe;flex-shrink:0;transition:all 0.15s;display:flex;align-items:center;justify-content:center;cursor:pointer;background:none;position:relative;}
.device-action-btn:active{background:var(--blue-l);}

.item-body{flex:1;min-width:0;}
.item-key{font-size:13.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.item-key.red-key{color:var(--red);}
.item-meta{font-size:12px;color:var(--gray);margin-top:2px;}

.badge{padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;flex-shrink:0;letter-spacing:0.02em;}
.badge.active{background:#dcfce7;color:#15803d;}
.badge.pending{background:#fef3c7;color:#92400e;}
.badge.expired{background:#fee2e2;color:#991b1b;}
.badge.used{background:#e0f2fe;color:#075985;}
.badge.current{background:#dbeafe;color:#1d4ed8;}

.device-item{background:var(--white);border-bottom:1px solid #f3f4f6;}
.device-item-header{padding:14px 14px;display:flex;align-items:center;gap:12px;}
.device-keys-sub{background:#f8f9ff;border-top:1px solid #f0f2f7;padding:10px 14px 10px 48px;}
.device-key-sub-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;border-bottom:1px solid #eef0f5;}
.device-key-sub-row:last-child{border-bottom:none;}
.device-key-sub-text{font-size:11.5px;font-family:monospace;color:var(--text);font-weight:600;flex:1;word-break:break-all;}

.copy-btn{background:var(--blue-l);border:none;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.15s;}
.copy-btn:active{background:#c7d2fe;}

/* ===== PACKAGES ===== */
.pkg-item{background:var(--white);border-bottom:1px solid #f3f4f6;padding:14px 16px;display:flex;align-items:center;gap:12px;}
.pkg-icon{width:38px;height:38px;border-radius:12px;background:var(--blue-l);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.pkg-body{flex:1;}
.pkg-name{font-size:14px;font-weight:700;color:var(--text);}
.pkg-url-hidden{display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;border-radius:8px;padding:3px 9px;margin-top:5px;font-size:11px;color:var(--gray);font-weight:600;letter-spacing:0.05em;}
.pkg-url-hidden svg{flex-shrink:0;}
.pkg-sent{font-size:11px;color:var(--blue);font-weight:600;margin-top:4px;}

.toggle{width:50px;height:30px;border-radius:20px;background:var(--blue);position:relative;cursor:pointer;flex-shrink:0;transition:background 0.2s;}
.toggle.off{background:#d1d5db;}
.toggle-knob{width:26px;height:26px;border-radius:50%;background:#fff;position:absolute;top:2px;left:22px;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:left 0.2s;}
.toggle.off .toggle-knob{left:2px;}

/* ===== PROFILE ===== */
.profile-top-bar{background:var(--white);padding:16px 18px;display:flex;align-items:center;gap:14px;flex-shrink:0;border-bottom:1px solid #f0f2f7;}
.profile-top-avatar{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#1a56e8,#1240c0);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;border:2px solid #fff;box-shadow:0 2px 10px rgba(26,86,232,0.2);}

.bottom-nav{position:absolute;bottom:0;left:0;width:100%;height:74px;background:var(--white);display:grid;grid-template-columns:repeat(5,1fr);border-top:1px solid #f0f2f7;padding-bottom:12px;z-index:100;}
.nav-item{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;cursor:pointer;color:var(--gray);transition:all 0.2s;}
.nav-item.active{color:var(--blue);}
.nav-label{font-size:10px;font-weight:700;letter-spacing:0.02em;}

/* ===== MODALS ===== */
.modal-overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;align-items:flex-end;z-index:1000;backdrop-filter:blur(2px);}
.modal-overlay.open{display:flex;}
.modal{width:100%;background:var(--white);border-radius:24px 24px 0 0;padding:20px 20px 40px;transform:translateY(100%);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);max-height:90%;overflow-y:auto;}
.modal-overlay.open .modal{transform:translateY(0);}
.modal-handle{width:40px;height:5px;background:#e5e7eb;border-radius:10px;margin:0 auto 15px;}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.modal-title{font-size:18px;font-weight:800;color:var(--text);}
.modal-close-btn{background:var(--bg);border:none;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;}

.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:12px;font-weight:700;color:var(--gray);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em;}
.req{color:var(--red);margin-left:2px;}
.form-input,.form-select,.form-textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:inherit;outline:none;background:var(--bg);transition:border-color 0.2s;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--blue);}
.form-hint{font-size:11px;color:var(--gray);margin-top:6px;line-height:1.4;}

.create-btn{width:100%;padding:16px;background:var(--blue);color:#fff;border:none;border-radius:14px;font-size:15px;font-weight:700;cursor:pointer;transition:transform 0.1s,background 0.2s;box-shadow:0 4px 12px rgba(26,86,232,0.25);}
.create-btn:active{transform:scale(0.97);background:var(--blue-d);}
.create-btn:disabled{background:var(--gray);opacity:0.6;cursor:not-allowed;}

.generated-results{background:var(--bg);border-radius:16px;padding:16px;margin-bottom:18px;}
.generated-results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.generated-results-title{font-size:13px;font-weight:700;color:var(--text);}
.result-key-item{display:flex;align-items:center;justify-content:space-between;background:var(--white);padding:10px 12px;border-radius:10px;margin-bottom:8px;border:1px solid #e5e7eb;}
.result-key-text{font-size:12px;font-family:monospace;font-weight:700;color:var(--text);word-break:break-all;flex:1;}
.result-copy-btn{background:var(--blue);border:none;width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;margin-left:10px;}

.toast{position:absolute;bottom:90px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.85);color:#fff;padding:10px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;white-space:nowrap;animation:toastIn 0.3s ease-out;}
@keyframes toastIn{from{transform:translate(-50%,20px);opacity:0;}to{transform:translate(-50%,0);opacity:1;}}

.empty{padding:40px 20px;text-align:center;}
.empty-icon{font-size:40px;margin-bottom:10px;opacity:0.5;}
.empty-text{font-size:14px;color:var(--gray);font-weight:500;}

.preview-box{background:#1e293b;color:#e2e8f0;padding:14px;border-radius:12px;font-family:monospace;font-size:12px;margin-top:10px;overflow-x:auto;}

.device-action-info{background:var(--bg);border-radius:14px;padding:14px;margin-bottom:15px;text-align:center;}
.device-action-info-key{font-size:15px;font-weight:800;color:var(--text);margin-bottom:4px;}
.device-action-info-dev{font-size:12px;color:var(--gray);font-weight:600;}
.device-action-list{display:flex;flex-direction:column;gap:10px;}
.device-action-item{display:flex;align-items:center;gap:14px;padding:14px;background:var(--white);border:1.5px solid #f0f2f7;border-radius:14px;cursor:pointer;transition:all 0.15s;}
.device-action-item:active{background:var(--bg);transform:scale(0.98);}
.dai-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.dai-icon.reset{background:#e0f2fe;}
.dai-icon.revoke{background:#fee2e2;}
.dai-label{font-size:14px;font-weight:700;color:var(--text);}
.dai-sub{font-size:11px;color:var(--gray);margin-top:2px;}

.lang-option{display:flex;align-items:center;gap:12px;padding:14px;background:var(--white);border:1.5px solid #f0f2f7;border-radius:14px;margin-bottom:8px;cursor:pointer;}
.lang-flag{font-size:20px;}
.lang-name{font-size:14px;font-weight:700;color:var(--text);flex:1;}
.lang-check{width:20px;height:20px;border-radius:50%;border:2px solid #e5e7eb;}
.lang-check.selected{background:var(--blue);border-color:var(--blue);position:relative;}
.lang-check.selected::after{content:'';position:absolute;top:4px;left:4px;width:8px;height:4px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg);}

.support-hero{text-align:center;padding:10px 0 20px;}
.support-hero-icon{font-size:48px;margin-bottom:10px;}
.support-hero-title{font-size:20px;font-weight:800;color:var(--text);margin-bottom:5px;}
.support-hero-sub{font-size:13px;color:var(--gray);line-height:1.5;}
</style>
</head>
<body>

<div class="phone">

  <!-- ========== LOGIN PAGE (PHP Controlled) ========== -->
  <div id="login-page" style="<?php echo $loggedIn ? 'display: none;' : 'display: flex;'; ?>">
    <div class="login-container">
      <div class="login-logo">🔑</div>
      <div class="login-title">Bem-vindo!</div>
      <div class="login-subtitle">Insira sua key para continuar</div>
      <input type="text" class="login-input" id="access-key" placeholder="Sua Key de Acesso"/>
      <button class="login-btn" onclick="validateKey()">Entrar</button>
      <div class="login-error" id="login-error"></div>
    </div>
  </div>

  <!-- ========== MAIN APP (PHP Controlled) ========== -->
  <div id="main-app" style="<?php echo $loggedIn ? 'display: block;' : 'display: none;'; ?>">
    
    <!-- HOME PAGE -->
    <div class="page active" id="page-home">
      <div class="home-header">
        <div class="home-top">
          <div class="user-info">
            <div class="avatar">F</div>
            <div>
              <div class="user-name">FERRAO</div>
              <div class="user-plan">Plano <?php echo $keyLevel; ?> • Ativo</div>
            </div>
          </div>
          <div class="header-icons">
            <button onclick="refreshAll()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.5M22 12.5a10 10 0 0 1-18.8 4.5"/></svg></button>
            <button onclick="openModal('lang-modal')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></button>
          </div>
        </div>
        <div class="limit-bar-wrap">
          <div class="limit-row">
            <span class="limit-label" data-i18n="keys_generated">Keys Geradas</span>
            <span class="limit-count" id="limit-count-text">0 / <?php echo number_format($KEY_LIMIT, 0, ',', '.'); ?></span>
          </div>
          <div class="limit-bar-bg"><div class="limit-bar-fill" id="limit-bar" style="width:0%;"></div></div>
        </div>
        <div class="stats-cards">
          <div class="stat-card" onclick="navigate('keys')">
            <div class="stat-label" data-i18n="pending">Pendentes</div>
            <div class="stat-num" id="stat-pending">0</div>
            <div class="stat-footer"><span class="stat-sub">Ver todas</span><div class="go-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></div></div>
          </div>
          <div class="stat-card" onclick="navigate('devices')">
            <div class="stat-label" data-i18n="active">Ativas</div>
            <div class="stat-num" id="stat-active">0</div>
            <div class="stat-footer"><span class="stat-sub">Devices</span><div class="go-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></div></div>
          </div>
        </div>
      </div>
      <div class="home-body">
        <div class="quick-actions">
          <div class="qa-btn" onclick="openCreateModal()">
            <div class="qa-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1a56e8" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg></div>
            <div class="qa-label" data-i18n="create_key">Criar Key</div>
          </div>
          <div class="qa-btn" onclick="navigate('keys')">
            <div class="qa-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1a56e8" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/></svg></div>
            <div class="qa-label" data-i18n="nav_keys">Keys</div>
          </div>
          <div class="qa-btn" onclick="navigate('devices')">
            <div class="qa-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1a56e8" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div>
            <div class="qa-label" data-i18n="nav_devices">Devices</div>
          </div>
          <div class="qa-btn" onclick="navigate('packages')">
            <div class="qa-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1a56e8" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
            <div class="qa-label" data-i18n="nav_packages">Pacotes</div>
          </div>
        </div>

        <div class="section-title" data-i18n="recent_activations">Ativações Recentes</div>
        <div class="chart-card">
          <div class="chart-legend"><div class="chart-dot"></div><span class="chart-legend-label" data-i18n="keys_per_day">Keys geradas por dia</span></div>
          <canvas id="mainChart"></canvas>
        </div>

        <div class="section-title" data-i18n="latest_keys">Últimas Keys</div>
        <div id="home-keys-list"></div>
        <div style="text-align:center;margin-top:15px;"><button class="ph-btn-text" onclick="navigate('keys')" data-i18n="see_all">Ver todas →</button></div>
      </div>
    </div>

    <!-- KEYS PAGE -->
    <div class="page" id="page-keys">
      <div class="page-header">
        <div class="page-header-title" data-i18n="nav_keys">Keys</div>
        <div class="page-header-actions">
          <button class="ph-btn" onclick="toggleSearch('keys')"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
          <button class="ph-btn" onclick="openCreateModal()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
          <button class="ph-btn-text" onclick="deleteSelected()" data-i18n="clear">Excluir</button>
        </div>
      </div>
      <div class="search-bar" id="search-keys" style="display:none;"><input type="text" class="search-input" placeholder="Buscar keys..." oninput="filterKeys(this.value)"/></div>
      <div class="scroll-area">
        <div id="keys-list"></div>
        <div style="text-align:center;margin-top:15px;"><button class="ph-btn-text" onclick="copyAllPending()" data-i18n="copy_all">Copiar todas pendentes</button></div>
      </div>
    </div>

    <!-- DEVICES PAGE -->
    <div class="page" id="page-devices">
      <div class="page-header">
        <div class="page-header-title" data-i18n="nav_devices">Devices</div>
        <div class="page-header-actions">
          <button class="ph-btn" onclick="toggleSearch('devices')"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
          <button class="ph-btn" onclick="refreshSessions()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.5M22 12.5a10 10 0 0 1-18.8 4.5"/></svg></button>
          <button class="ph-btn-text" onclick="clearSessions()" data-i18n="clear">Limpar</button>
        </div>
      </div>
      <div class="search-bar" id="search-devices" style="display:none;"><input type="text" class="search-input" placeholder="Buscar devices..." oninput="filterDevices(this.value)"/></div>
      <div class="scroll-area">
        <div id="devices-list"></div>
        <div id="sessions-list"></div>
      </div>
    </div>

    <!-- PACKAGES PAGE -->
    <div class="page" id="page-packages">
      <div class="page-header">
        <div class="page-header-title" data-i18n="nav_packages">Pacotes</div>
        <div class="page-header-actions">
          <button class="ph-btn" onclick="openPkgModal()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
        </div>
      </div>
      <div class="scroll-area">
        <div id="packages-list"></div>
      </div>
    </div>

    <!-- PROFILE PAGE -->
    <div class="page" id="page-profile">
      <div class="profile-top-bar">
        <div class="profile-top-avatar">F</div>
        <div>
          <div class="page-header-title">FERRAO</div>
          <div class="user-plan">Plano <?php echo $keyLevel; ?> • Ativo</div>
        </div>
      </div>
      <div class="scroll-area">
        <div class="list-item" onclick="openModal('integration-modal')">
          <div class="item-body"><div class="item-key" data-i18n="integration_code">🔌 Código de Integração</div></div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="list-item" onclick="openModal('lang-modal')">
          <div class="item-body"><div class="item-key" data-i18n="language">🌐 Idioma</div></div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="list-item" onclick="openModal('support-modal')">
          <div class="item-body"><div class="item-key" data-i18n="support">🎧 Suporte</div></div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
        <div class="list-item" onclick="resetAllKeys()">
          <div class="item-body"><div class="item-key red-key" data-i18n="clear_keys">Limpar todas as keys</div></div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <div class="list-item" onclick="confirmLogout()">
          <div class="item-body"><div class="item-key red-key" data-i18n="logout">Sair</div></div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </div>
        <div style="padding:20px;text-align:center;color:var(--gray);font-size:12px;" data-i18n="version">Versão: 1.0.0 (Prod)</div>
      </div>
    </div>

    <!-- BOTTOM NAV -->
    <nav class="bottom-nav">
      <div class="nav-item active" onclick="navigate('home')" id="nav-home"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7" rx="1.5" opacity="0.85"/><rect x="14" y="3" width="7" height="7" rx="1.5" opacity="0.85"/><rect x="3" y="14" width="7" height="7" rx="1.5" opacity="0.85"/><rect x="14" y="14" width="7" height="7" rx="1.5" opacity="0.85"/></svg><span class="nav-label" data-i18n="nav_home">Home</span></div>
      <div class="nav-item" onclick="navigate('keys')" id="nav-keys"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/></svg><span class="nav-label" data-i18n="nav_keys">Keys</span></div>
      <div class="nav-item" onclick="navigate('devices')" id="nav-devices"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg><span class="nav-label" data-i18n="nav_devices">Devices</span></div>
      <div class="nav-item" onclick="navigate('packages')" id="nav-packages"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg><span class="nav-label" data-i18n="nav_packages">Pacotes</span></div>
      <div class="nav-item" onclick="navigate('profile')" id="nav-profile"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span class="nav-label" data-i18n="nav_profile">Perfil</span></div>
    </nav>

  </div>

  <!-- MODALS -->
  <!-- CREATE KEY -->
  <div class="modal-overlay" id="create-modal" onclick="closeOnBg(event,'create-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">✦ Gerar Key</span><button class="modal-close-btn" onclick="closeModal('create-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div class="form-group"><label class="form-label">Quantidade</label><input class="form-input" id="key-qty" type="number" value="1" min="1" max="100"/></div>
      <div class="form-group"><label class="form-label">Tipo</label><select class="form-select" id="key-type" onchange="updatePreview()"><option value="weekly">1 Semana</option><option value="monthly">1 Mês</option><option value="lifetime">Lifetime</option></select></div>
      <div class="form-group"><label class="form-label">Duração (dias)</label><input class="form-input" id="key-duration" type="number" value="1" min="1"/></div>
      <div class="form-group"><label class="form-label">Package</label><select class="form-select" id="key-package"></select></div>
      <div class="form-group"><label class="form-label">Preview</label><input class="form-input" id="key-preview" readonly value="GHOST-weekly-XXXXXXXXXX"/></div>
      <div class="generated-results" id="generated-results" style="display:none;">
        <div class="generated-results-header"><div class="generated-results-title">Keys Geradas</div><button class="ph-btn-text" onclick="copyAllGenerated()">Copiar todas</button></div>
        <div id="generated-keys-list"></div>
      </div>
      <button class="create-btn" id="create-btn-main" onclick="createKeys()">✦ Gerar Keys</button>
    </div>
  </div>

  <!-- ADD PACKAGE -->
  <div class="modal-overlay" id="pkg-modal" onclick="closeOnBg(event,'pkg-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">📦 Novo Package</span><button class="modal-close-btn" onclick="closeModal('pkg-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div class="form-group"><label class="form-label">Nome do Package <span class="req">*</span></label><input class="form-input" id="pkg-name" placeholder="Ex: api, loja1, scripts..."/></div>
      <div class="form-group"><label class="form-label">URL da API <span class="req">*</span></label><input class="form-input" id="pkg-url" placeholder="https://sua-api.com/keys" type="url"/><div class="form-hint">As keys serão enviadas via POST para essa URL (URL ficará oculta)</div></div>
      <div class="form-group"><label class="form-label">Descrição (opcional)</label><input class="form-input" id="pkg-desc" placeholder="Ex: API do sistema de scripts..."/></div>
      <button class="create-btn" onclick="addPackage()">+ Adicionar Package</button>
    </div>
  </div>

  <!-- INTEGRATION -->
  <div class="modal-overlay" id="integration-modal" onclick="closeOnBg(event,'integration-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">🔌 Integração</span><button class="modal-close-btn" onclick="closeModal('integration-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div style="font-size:12px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Verificar key (JavaScript/Fetch)</div>
      <div class="preview-box" id="integration-js" style="font-size:11px;line-height:1.7;cursor:pointer;text-align:left;white-space:pre-wrap;" onclick="copyIntegration('js')"></div>
      <div style="font-size:12px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin:12px 0 8px;">Verificar key (Luau / Roblox)</div>
      <div class="preview-box" id="integration-luau" style="font-size:11px;line-height:1.7;cursor:pointer;text-align:left;white-space:pre-wrap;" onclick="copyIntegration('luau')"></div>
      <div style="font-size:11px;color:var(--gray);margin-top:8px;">👆 Clique no código para copiar</div>
    </div>
  </div>

  <!-- DEVICE ACTION -->
  <div class="modal-overlay" id="device-action-modal" onclick="closeOnBg(event,'device-action-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">📱 Ações do Device</span><button class="modal-close-btn" onclick="closeModal('device-action-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div class="device-action-info"><div class="device-action-info-key" id="da-key-text">–</div><div class="device-action-info-dev" id="da-dev-text">–</div></div>
      <div class="device-action-list">
        <div class="device-action-item" onclick="doDeviceAction('reset')">
          <div class="dai-icon reset"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg></div>
          <div><div class="dai-label">Resetar Device</div><div class="dai-sub">Remove o vínculo com o device, key fica disponível novamente</div></div>
        </div>
        <div class="device-action-item" onclick="doDeviceAction('revoke')">
          <div class="dai-icon revoke"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
          <div><div class="dai-label" style="color:#ef4444;">Revogar Device</div><div class="dai-sub">Exclui a key permanentemente da API e do sistema</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- LANGUAGE -->
  <div class="modal-overlay" id="lang-modal" onclick="closeOnBg(event,'lang-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">🌐 Language</span><button class="modal-close-btn" onclick="closeModal('lang-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div>
        <div class="lang-option" onclick="setLanguage('en')"><span class="lang-flag">🇺🇸</span><span class="lang-name">English</span><div class="lang-check" id="lang-check-en"></div></div>
        <div class="lang-option" onclick="setLanguage('pt')"><span class="lang-flag">🇧🇷</span><span class="lang-name">Português (BR)</span><div class="lang-check" id="lang-check-pt"></div></div>
        <div class="lang-option" onclick="setLanguage('vi')"><span class="lang-flag">🇻🇳</span><span class="lang-name">Tiếng Việt</span><div class="lang-check" id="lang-check-vi"></div></div>
      </div>
    </div>
  </div>

  <!-- SUPPORT -->
  <div class="modal-overlay" id="support-modal" onclick="closeOnBg(event,'support-modal')">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header"><span class="modal-title">🎧 Support</span><button class="modal-close-btn" onclick="closeModal('support-modal')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
      <div class="support-hero"><div class="support-hero-icon">🛟</div><div class="support-hero-title">Como podemos ajudar?</div><div class="support-hero-sub">Preencha o formulário e responderemos em breve via Discord</div></div>
      <div class="form-group"><label class="form-label">Nome <span class="req">*</span></label><input class="form-input" id="support-name" placeholder="Seu nome ou usuário..."/></div>
      <div class="form-group"><label class="form-label">Problema <span class="req">*</span></label><select class="form-select" id="support-problem"><option value="">— Selecione o tipo de problema —</option><option value="Key não funciona">🔑 Key não funciona</option><option value="Erro ao gerar key">⚠️ Erro ao gerar key</option><option value="Problema com device">📱 Problema com device</option><option value="Problema com package">📦 Problema com package</option><option value="Erro de login">🔐 Erro de login</option><option value="Pagamento / Plano">💳 Pagamento / Plano</option><option value="Outro">❓ Outro</option></select></div>
      <div class="form-group"><label class="form-label">Descrição — explique o problema <span class="req">*</span></label><textarea class="form-textarea" id="support-desc" placeholder="Descreva o problema com o máximo de detalhes possível..." rows="5"></textarea></div>
      <button class="create-btn" id="support-send-btn" onclick="sendSupportTicket()">📨 Enviar para o Suporte</button>
    </div>
  </div>

</div>

<script>
/* ============================================================
   LOGIN & HWID SYSTEM
   ============================================================ */
function getHWID() {
    let hwid = localStorage.getItem('device_hwid');
    if (!hwid) {
        hwid = 'WEB-' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('device_hwid', hwid);
    }
    return hwid;
}

async function validateKey() {
    const keyInput = document.getElementById('access-key').value.trim().toUpperCase();
    const errorMsg = document.getElementById('login-error');
    const hwid = getHWID();

    if (!keyInput) {
        errorMsg.innerText = "Por favor, insira uma key.";
        errorMsg.style.display = "block";
        return;
    }

    try {
        const response = await fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=validate_key&key=${keyInput}&hwid=${hwid}`
        });
        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            errorMsg.innerText = result.message;
            errorMsg.style.display = "block";
        }
    } catch (err) {
        console.error('Erro ao validar key:', err);
        errorMsg.innerText = "Erro ao validar key. Tente novamente.";
        errorMsg.style.display = "block";
    }
}

function confirmLogout() {
    if (confirm('Tem certeza que deseja sair?')) {
        window.location.href = 'index.php?action=logout';
    }
}

/* ============================================================
   DISCORD WEBHOOK
   ============================================================ */
const DISCORD_WEBHOOK = 'https://discord.com/api/webhooks/1446276085397590018/Uwj0sKu8SKD4ZqVtnkdz4nhfEd_DlKE_AdeEde3EX9NglfGPhuTz9pXLPSiROrBNmhXy';

/* ============================================================
   TRANSLATIONS
   ============================================================ */
const translations = {
  en:{keys_generated:'Keys Generated',pending:'Pending',active:'Active',create_key:'Create Key',copy_all:'Copy All',profile:'Profile',recent_activations:'Recent Activations',keys_per_day:'Keys generated per day',latest_keys:'Latest Keys',see_all:'See all →',logout:'Logout',notifications:'Notifications',group:'Group',change_profile:'Change profile info',change_password:'Change password',key_alias:'Key alias',login_session:'Login session',twofa:'2FA setting',privacy:'Privacy and policy',support:'Support',language:'Language',integration_code:'Integration Code',clear_keys:'Clear all keys',version:'Version: 1.0.0 (Prod)',clear:'Clear',nav_home:'Home',nav_keys:'Keys',nav_devices:'Devices',nav_packages:'Packages',nav_profile:'Profile'},
  pt:{keys_generated:'Keys Geradas',pending:'Pendentes',active:'Ativas',create_key:'Criar Key',copy_all:'Copiar All',profile:'Perfil',recent_activations:'Ativações Recentes',keys_per_day:'Keys geradas por dia',latest_keys:'Últimas Keys',see_all:'Ver todas →',logout:'Sair',notifications:'Notificações',group:'Grupo',change_profile:'Alterar dados do perfil',change_password:'Alterar senha',key_alias:'Alias da key',login_session:'Sessão de login',twofa:'Configuração 2FA',privacy:'Privacidade e política',support:'Suporte',language:'Idioma',integration_code:'Código de Integração',clear_keys:'Limpar todas as keys',version:'Versão: 1.0.0 (Prod)',clear:'Limpar',nav_home:'Home',nav_keys:'Keys',nav_devices:'Devices',nav_packages:'Pacotes',nav_profile:'Perfil'},
  vi:{keys_generated:'Keys Đã Tạo',pending:'Chờ xử lý',active:'Đang hoạt động',create_key:'Tạo Key',copy_all:'Sao chép tất cả',profile:'Hồ sơ',recent_activations:'Kích hoạt gần đây',keys_per_day:'Keys được tạo mỗi ngày',latest_keys:'Keys gần nhất',see_all:'Xem tất cả →',logout:'Đăng xuất',notifications:'Thông báo',group:'Nhóm',change_profile:'Thay đổi thông tin',change_password:'Đổi mật khẩu',key_alias:'Bí danh key',login_session:'Phiên đăng nhập',twofa:'Cài đặt 2FA',privacy:'Quyền riêng tư',support:'Hỗ trợ',language:'Ngôn ngữ',integration_code:'Mã tích hợp',clear_keys:'Xóa tất cả keys',version:'Phiên bản: 1.0.0 (Sản xuất)',clear:'Xóa',nav_home:'Trang chủ',nav_keys:'Keys',nav_devices:'Thiết bị',nav_packages:'Gói',nav_profile:'Hồ sơ'}
};
let currentLang = localStorage.getItem('ferrao_lang') || 'en';
function applyLanguage(lang){
  currentLang=lang; localStorage.setItem('ferrao_lang',lang);
  const t=translations[lang]||translations['en'];
  document.querySelectorAll('[data-i18n]').forEach(el=>{const k=el.getAttribute('data-i18n');if(t[k])el.textContent=t[k];});
  const labels={en:'English',pt:'Português (BR)',vi:'Tiếng Việt'};
  const el=document.getElementById('current-lang-label'); if(el) el.textContent=labels[lang]||'English';
  ['en','pt','vi'].forEach(l=>{const c=document.getElementById('lang-check-'+l);if(c)c.className='lang-check'+(l===lang?' selected':'');});
}
function setLanguage(lang){ applyLanguage(lang); closeModal('lang-modal'); showToast('🌐 Language changed!'); }

/* ============================================================
   STORAGE & CONFIG
   ============================================================ */
const STORAGE_KEYS={keys:'ferrao_keys',packages:'ferrao_packages',limit:'ferrao_limit',chartData:'ferrao_chart',apiKeys:'ferrao_api_keys',deleted:'ferrao_deleted'};
let KEY_LIMIT = <?php echo $KEY_LIMIT; ?>;
let currentPlanLevel = '<?php echo $keyLevel; ?>';

function save(k,v){try{localStorage.setItem(k,JSON.stringify(v));}catch(e){}}
function load(k,def=null){try{const v=localStorage.getItem(k);return v!==null?JSON.parse(v):def;}catch(e){return def;}}

let generatedKeys=load(STORAGE_KEYS.keys,[]);
let apiKeys=load(STORAGE_KEYS.apiKeys,[]);
let packages=load(STORAGE_KEYS.packages,[{id:'default',name:'API',url:'https://teste-api-mcok.vercel.app/keys',desc:'API principal FERRAO',enabled:true,sent:0}]);
let limitCount=load(STORAGE_KEYS.limit,0);
let chartData=load(STORAGE_KEYS.chartData,{});
let selectedIds=new Set();
let deletedIds=new Set(load(STORAGE_KEYS.deleted,[]));
let activeDeviceKey=null;
let clearedSessionDevices=new Set(load('ferrao_cleared_sessions',[]));

/* ============================================================
   NAVIGATION
   ============================================================ */
const mainPages=['home','keys','devices','packages','profile'];
function navigate(page){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  if(mainPages.includes(page)){
    document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
    const navEl=document.getElementById('nav-'+page); if(navEl) navEl.classList.add('active');
    document.getElementById('page-'+page).classList.add('active');
  }
  if(page==='home'){renderChart();renderHomeKeys();}
  if(page==='keys')renderKeysList(getMergedKeys());
  if(page==='devices')renderDevicesList(getMergedKeys());
  if(page==='packages')renderPackages();
}

/* ============================================================
   CORE DATA
   ============================================================ */
function getMergedKeys(){
  const all=[...generatedKeys,...apiKeys];
  const seen=new Set();
  return all.filter(k=>{
    if(seen.has(k.id)||deletedIds.has(k.id))return false;
    seen.add(k.id);return true;
  }).sort((a,b)=>(b.createdAt||0)-(a.createdAt||0));
}

async function fetchRemoteKeys(){
  try{
    const res=await fetch('https://teste-api-mcok.vercel.app/keys',{signal:AbortSignal.timeout(8000)});
    if(res.ok){
      const data=await res.json();
      apiKeys=data.map(k=>({...k,id:k.id?.toString()||'api-'+Math.random()}));
      save(STORAGE_KEYS.apiKeys,apiKeys);
    }
  }catch(e){console.warn('API Offline, using cache');}
}

async function deleteKeyFromApi(id,key){
  try{
    const res=await fetch(`https://teste-api-mcok.vercel.app/keys/${id}`,{method:'DELETE',signal:AbortSignal.timeout(6000)});
    return res.ok;
  }catch(e){return false;}
}

/* ============================================================
   DEVICES & SESSIONS
   ============================================================ */
let realSessions=[];
async function fetchRealSessions(){
  const el=document.getElementById('sessions-list');
  el.innerHTML='<div style="padding:30px;text-align:center;"><div class="loading-spinner"></div><div style="margin-top:10px;font-size:12px;color:var(--gray);">Buscando sessões na API...</div></div>';
  try{
    const res=await fetch('https://teste-api-mcok.vercel.app/keys',{signal:AbortSignal.timeout(10000)});
    const data=await res.json();
    const seenDevices=new Map();
    data.forEach(k=>{
      if(k.used && k.device && !clearedSessionDevices.has(k.device)){
        const dev=k.device;
        if(!seenDevices.has(dev)){
          seenDevices.set(dev,{
            device:dev,
            platform:detectPlatform(dev),
            activatedAt:k.activatedAt||0,
            expiresAt:k.expiresAt||0,
            ip:k.ip||k.clientIp||k.userIp||k.ipAddress||'',
            version:k.version||k.osVersion||'–',
            keyCount:1
          });
        }else{
          seenDevices.get(dev).keyCount++;
          if((k.activatedAt||0)>seenDevices.get(dev).activatedAt){
            seenDevices.get(dev).activatedAt=k.activatedAt;
            seenDevices.get(dev).expiresAt=k.expiresAt;
            seenDevices.get(dev).ip=k.ip||k.clientIp||k.userIp||k.ipAddress||seenDevices.get(dev).ip;
          }
        }
      }
    });
    realSessions=[...seenDevices.values()];
    if(realSessions.length===0){
      el.innerHTML='<div class="empty"><div class="empty-icon">📱</div><div class="empty-text">Nenhum device ativo encontrado</div></div>';
      return;
    }
    realSessions.sort((a,b)=>(b.activatedAt||0)-(a.activatedAt||0));
    renderSessionCards(realSessions);
  }catch(e){
    console.warn('Sessions fetch error:',e);
    el.innerHTML=`<div class="empty"><div class="empty-icon">⚠️</div><div class="empty-text">Erro ao buscar sessões<br><small style="font-size:11px;color:#9ca3af;">${e.message}</small></div></div>`;
  }
}

function detectPlatform(deviceName){
  const d=deviceName.toLowerCase();
  if(d.includes('iphone')||d.includes('ipad')||d.includes('ipod')) return 'iOS';
  if(d.includes('android')||d.includes('samsung')||d.includes('xiaomi')||d.includes('pixel')||d.includes('huawei')||d.includes('motorola')||d.includes('oppo')||d.includes('vivo')) return 'Android';
  if(d.includes('mac')||d.includes('darwin')) return 'macOS';
  if(d.includes('win')||d.includes('windows')) return 'Windows';
  if(d.includes('linux')||d.includes('ubuntu')) return 'Linux';
  return 'Unknown';
}

function renderSessionCards(sessions){
  const el=document.getElementById('sessions-list');
  if(!sessions.length){el.innerHTML='<div class="empty"><div class="empty-icon">📱</div><div class="empty-text">Nenhuma sessão ativa</div></div>';return;}
  el.innerHTML=sessions.map((s,idx)=>{
    const isCurrent=idx===0;
    const actDate=s.activatedAt>0?new Date(s.activatedAt*1000).toLocaleString('pt-BR'):'–';
    const expDate=s.expiresAt>0?new Date(s.expiresAt*1000).toLocaleString('pt-BR'):'Lifetime';
    const isExpired=s.expiresAt>0&&s.expiresAt<Date.now()/1000;
    const statusColor=isExpired?'background:#fee2e2;color:#991b1b':'background:#dcfce7;color:#15803d';
    const statusLabel=isExpired?'Expirado':'Ativo';
    return `<div class="session-card${isCurrent?' is-current':''}" style="background:var(--white);border-radius:16px;padding:16px;margin-bottom:12px;border:1px solid #f0f2f7;box-shadow:var(--shadow);">
      <div class="session-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:18px;">${s.platform==='iOS'?'📱':s.platform==='Android'?'🤖':s.platform==='Windows'?'💻':'🖥️'}</span>
          <div><div class="session-device-name" style="font-size:14px;font-weight:700;">${s.device}</div><div style="font-size:11px;color:var(--gray);margin-top:2px;">${s.platform} ${s.version!=='–'?'· v'+s.version:''}</div></div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;"><span class="badge" style="${statusColor}">${statusLabel}</span>${isCurrent?'<span class="badge current">📍 Recente</span>':''}</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;">
        <div style="color:var(--gray);">IP: <span style="color:var(--text);font-weight:600;">${s.ip||'–'}</span></div>
        <div style="color:var(--gray);">Keys: <span style="color:var(--text);font-weight:600;">${s.keyCount}</span></div>
        <div style="color:var(--gray);">Ativado: <span style="color:var(--text);font-weight:600;">${actDate}</span></div>
        <div style="color:var(--gray);">Expira: <span style="color:var(--text);font-weight:600;">${expDate}</span></div>
      </div>
    </div>`;
  }).join('');
}

function refreshSessions(){ showToast('🔄 Atualizando sessões...'); fetchRealSessions(); }
function clearSessions(){
  if(!confirm('Marcar todas as sessões como encerradas?')) return;
  realSessions.forEach(s=>clearedSessionDevices.add(s.device));
  save('ferrao_cleared_sessions',[...clearedSessionDevices]);
  document.getElementById('sessions-list').innerHTML='<div class="empty"><div class="empty-icon">✅</div><div class="empty-text">Todas as sessões foram encerradas</div></div>';
  showToast('✅ Sessões encerradas localmente');
}

/* ============================================================
   KEYS PAGE
   ============================================================ */
function renderKeysList(keys){
  const el=document.getElementById('keys-list');
  if(!keys.length){el.innerHTML='<div class="empty"><div class="empty-icon">🔑</div><div class="empty-text">Nenhuma key encontrada</div></div>';return;}
  el.innerHTML=keys.map(k=>{
    const isSelected=selectedIds.has(k.id);
    const statusClass=k.used?'active':'pending';
    const statusLabel=k.used?'Ativa':'Pendente';
    return `<div class="list-item ${isSelected?'selected':''}" onclick="toggleSelect('${k.id}',this)">
      <div class="item-check ${isSelected?'checked':''}" id="chk-${k.id}"></div>
      <div class="item-body"><div class="item-key">${k.key}</div><div class="item-meta">${k.type} · ${k._pkg||'API'}</div></div>
      <span class="badge ${statusClass}">${statusLabel}</span>
      <button class="copy-btn" onclick="copyText('${k.key}');event.stopPropagation();"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
    </div>`;
  }).join('');
}

function filterKeys(val){const v=val.toLowerCase();renderKeysList(getMergedKeys().filter(k=>k.key.toLowerCase().includes(v)||k.type.toLowerCase().includes(v)));}
function toggleSelect(id,row){const chk=document.getElementById('chk-'+id);if(selectedIds.has(id)){selectedIds.delete(id);chk?.classList.remove('checked');row.classList.remove('selected');}else{selectedIds.add(id);chk?.classList.add('checked');row.classList.add('selected');}}

async function deleteSelected(){
  if(!selectedIds.size){showToast('⚠️ Selecione keys primeiro');return;}
  const n=selectedIds.size; showToast(`⏳ Excluindo ${n} key(s)...`);
  const toDelete=getMergedKeys().filter(k=>selectedIds.has(k.id));
  selectedIds.forEach(id=>deletedIds.add(id)); save(STORAGE_KEYS.deleted,[...deletedIds]);
  generatedKeys=generatedKeys.filter(k=>!deletedIds.has(k.id)); apiKeys=apiKeys.filter(k=>!deletedIds.has(k.id));
  save(STORAGE_KEYS.keys,generatedKeys);save(STORAGE_KEYS.apiKeys,apiKeys);
  selectedIds.clear(); renderKeysList(getMergedKeys()); renderStats(); renderHomeKeys();
  let apiDeletedCount=0;
  for(const k of toDelete){const ok=await deleteKeyFromApi(k.id,k.key);if(ok)apiDeletedCount++;}
  showToast(`🗑️ ${n} key(s) removida(s)${apiDeletedCount>0?` (${apiDeletedCount} da API)`:''}`);
}

/* ============================================================
   DEVICES LIST
   ============================================================ */
function renderDevicesList(keys){
  const el=document.getElementById('devices-list');
  const usedKeys=keys.filter(k=>k.used&&k.device);
  if(!usedKeys.length){el.innerHTML='<div class="empty"><div class="empty-icon">📱</div><div class="empty-text">Nenhum device conectado</div></div>';return;}
  const grouped={};
  usedKeys.forEach(k=>{const dev=k.device||'(desconhecido)';if(!grouped[dev])grouped[dev]=[];grouped[dev].push(k);});
  el.innerHTML=Object.entries(grouped).map(([dev,devKeys])=>{
    const firstKey=devKeys[0];
    const isExpired=firstKey.expiresAt>0&&firstKey.expiresAt<Date.now()/1000;
    const activatedAt=firstKey.activatedAt?new Date(firstKey.activatedAt*1000).toLocaleString('pt-BR'):'–';
    const keysSubHtml=devKeys.map(k=>{
      const exp=k.expiresAt>0&&k.expiresAt<Date.now()/1000;
      return `<div class="device-key-sub-row"><div class="device-key-sub-text">${k.key}</div><span class="badge" style="flex-shrink:0;${exp?'background:#fee2e2;color:#991b1b':'background:#dcfce7;color:#15803d'}">${exp?'Expirada':'Ativa'}</span><button class="copy-btn" style="width:24px;height:24px;margin-left:6px;" onclick="copyText('${k.key}');event.stopPropagation();"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div>`;
    }).join('');
    return `<div class="device-item"><div class="device-item-header"><button class="device-action-btn" onclick="openDeviceActionModal('${firstKey.id}',event)"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5"><circle cx="12" cy="5" r="1.5" fill="#6366f1"/><circle cx="12" cy="12" r="1.5" fill="#6366f1"/><circle cx="12" cy="19" r="1.5" fill="#6366f1"/></svg></button><div class="item-body"><div class="item-key" style="font-size:13px;">📱 ${dev}</div><div class="item-meta" style="font-size:11px;">Ativado: ${activatedAt} · ${devKeys.length} key(s)</div></div><span class="badge" style="${isExpired?'background:#fee2e2;color:#991b1b':'background:#dcfce7;color:#15803d'}">${isExpired?'Expirado':'Online'}</span></div><div class="device-keys-sub"><div style="font-size:10px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Keys vinculadas</div>${keysSubHtml}</div></div>`;
  }).join('');
}
function filterDevices(val){const v=val.toLowerCase();renderDevicesList(getMergedKeys().filter(k=>k.device?.toLowerCase().includes(v)||k.key.toLowerCase().includes(v)));}

function openDeviceActionModal(keyId,event){
  event.stopPropagation();
  const k=getMergedKeys().find(k=>k.id===keyId);if(!k)return;
  activeDeviceKey=k;
  document.getElementById('da-key-text').textContent=k.key;
  document.getElementById('da-dev-text').textContent='📱 '+(k.device||'Device desconhecido');
  openModal('device-action-modal');
}
async function doDeviceAction(action){
  if(!activeDeviceKey)return;
  const k=activeDeviceKey; closeModal('device-action-modal');
  if(action==='reset'){
    showToast('⏳ Resetando device...');
    const upd=a=>a.map(i=>i.id===k.id?{...i,used:false,device:'',activatedAt:0,expiresAt:0}:i);
    generatedKeys=upd(generatedKeys);apiKeys=upd(apiKeys);
    save(STORAGE_KEYS.keys,generatedKeys);save(STORAGE_KEYS.apiKeys,apiKeys);
    try{await fetch(`https://teste-api-mcok.vercel.app/keys/${k.id}`,{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({used:false,device:'',activatedAt:0,expiresAt:0}),signal:AbortSignal.timeout(6000)});}catch(e){}
    renderDevicesList(getMergedKeys());renderStats();renderHomeKeys();
    showToast('✅ Device resetado! Key disponível.');
  } else if(action==='revoke'){
    showToast('⏳ Revogando device...');
    deletedIds.add(k.id);save(STORAGE_KEYS.deleted,[...deletedIds]);
    generatedKeys=generatedKeys.filter(i=>i.id!==k.id);apiKeys=apiKeys.filter(i=>i.id!==k.id);
    save(STORAGE_KEYS.keys,generatedKeys);save(STORAGE_KEYS.apiKeys,apiKeys);
    await deleteKeyFromApi(k.id,k.key);
    renderDevicesList(getMergedKeys());renderStats();renderHomeKeys();
    showToast('🚫 Device revogado e key excluída.');
  }
  activeDeviceKey=null;
}

/* ============================================================
   PACKAGES
   ============================================================ */
function renderPackages(){
  const el=document.getElementById('packages-list');
  if(!packages.length){el.innerHTML='<div class="empty"><div class="empty-icon">📦</div><div class="empty-text">Nenhum package ainda</div></div>';return;}
  el.innerHTML=packages.map((p,i)=>`
    <div class="pkg-item">
      <div class="pkg-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1a56e8" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
      <div class="pkg-body">
        <div class="pkg-name">${p.name}</div>
        <div class="pkg-url-hidden"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> URL protegida</div>
        ${p.desc?`<div class="item-meta" style="margin-top:4px;font-size:11px;">${p.desc}</div>`:''}
        <div class="pkg-sent">✦ ${p.sent||0} keys enviadas</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
        <div class="toggle ${p.enabled?'':'off'}" onclick="togglePkg(${i})"><div class="toggle-knob"></div></div>
        <button onclick="deletePkg(${i})" style="background:none;border:none;cursor:pointer;padding:2px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
      </div>
    </div>`).join('');
}
function togglePkg(i){packages[i].enabled=!packages[i].enabled;save(STORAGE_KEYS.packages,packages);renderPackages();updatePkgSelect();}
function deletePkg(i){const n=packages[i].name;packages.splice(i,1);save(STORAGE_KEYS.packages,packages);renderPackages();updatePkgSelect();showToast(`📦 Package "${n}" removido`);}
function openPkgModal(){ document.getElementById('pkg-name').value=''; document.getElementById('pkg-url').value=''; document.getElementById('pkg-desc').value=''; openModal('pkg-modal'); }
function addPackage(){
  const name=document.getElementById('pkg-name').value.trim();
  const url=document.getElementById('pkg-url').value.trim();
  const desc=document.getElementById('pkg-desc').value.trim();
  if(!name){showToast('⚠️ Informe o nome');return;}
  if(!url||!url.startsWith('http')){showToast('⚠️ URL inválida');return;}
  packages.push({id:'pkg-'+Date.now(),name,url,desc,enabled:true,sent:0});
  save(STORAGE_KEYS.packages,packages);renderPackages();updatePkgSelect();
  closeModal('pkg-modal');showToast(`📦 Package "${name}" adicionado!`);
}
function updatePkgSelect(){
  const sel=document.getElementById('key-package');if(!sel)return;
  const cur=sel.value;
  sel.innerHTML='<option value="">— Selecionar package —</option>'+packages.filter(p=>p.enabled).map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
  if(cur)sel.value=cur;
}

/* ============================================================
   CREATE KEY
   ============================================================ */
function openCreateModal(){
  document.getElementById('generated-results').style.display='none';
  document.getElementById('generated-keys-list').innerHTML='';
  document.getElementById('create-btn-main').disabled=false;
  document.getElementById('create-btn-main').textContent='✦ Gerar Keys';
  updatePkgSelect();updatePreview();openModal('create-modal');
}
function updatePreview(){
  const type=document.getElementById('key-type')?.value||'weekly';
  document.getElementById('key-preview').textContent=`GHOST-${type}-${'X'.repeat(10)}${Math.floor(Math.random()*99999)}`;
}
function generateKeyString(type){
  const chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';let rand='';
  for(let i=0;i<8;i++)rand+=chars[Math.floor(Math.random()*chars.length)];
  return `GHOST-${type}-${rand}${Math.floor(Math.random()*99999).toString().padStart(5,'0')}`;
}
async function sendKeyToApi(pkgUrl,keyData){
  try{const res=await fetch(pkgUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(keyData),signal:AbortSignal.timeout(8000)});return res.ok;}catch(e){return false;}
}
async function createKeys(){
  const qty=Math.min(parseInt(document.getElementById('key-qty').value)||1,100);
  const type=document.getElementById('key-type').value;
  const dur=parseInt(document.getElementById('key-duration').value)||1;
  const pkgId=document.getElementById('key-package').value;
  if(!pkgId){showToast('⚠️ Selecione um Package!');return;}
  if(limitCount>=KEY_LIMIT){showToast('❌ Limite de '+KEY_LIMIT+' atingido');return;}
  const realQty=Math.min(qty,KEY_LIMIT-limitCount);
  const pkg=packages.find(p=>p.id===pkgId);if(!pkg){showToast('⚠️ Package não encontrado');return;}
  const btn=document.getElementById('create-btn-main');btn.disabled=true;btn.textContent='⏳ Gerando...';
  const today=new Date().toISOString().slice(0,10);
  const newKeys=[];const sentOk=[];
  for(let i=0;i<realQty;i++){
    const key=generateKeyString(type);
    const keyObj={id:'local-'+Date.now()+'-'+i,key,type,expire:dur,used:false,device:'',createdAt:Math.floor(Date.now()/1000),activatedAt:0,expiresAt:0,_pkg:pkg.name,_pkgId:pkgId};
    newKeys.push(keyObj);sentOk.push(await sendKeyToApi(pkg.url,keyObj));
  }
  generatedKeys.unshift(...newKeys);save(STORAGE_KEYS.keys,generatedKeys);
  updateLimit(realQty);chartData[today]=(chartData[today]||0)+realQty;save(STORAGE_KEYS.chartData,chartData);
  const pkgIdx=packages.findIndex(p=>p.id===pkgId);if(pkgIdx>=0)packages[pkgIdx].sent=(packages[pkgIdx].sent||0)+realQty;
  save(STORAGE_KEYS.packages,packages);
  document.getElementById('generated-results').style.display='block';
  document.getElementById('generated-keys-list').innerHTML=newKeys.map(k=>`<div class="result-key-item"><div class="result-key-text">${k.key}</div><button class="result-copy-btn" onclick="copyText('${k.key}')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div>`).join('');
  btn.disabled=false;btn.textContent='✦ Gerar Mais';
  renderStats();renderHomeKeys();renderChart();
  if(document.getElementById('page-keys').classList.contains('active'))renderKeysList(getMergedKeys());
  const okCount=sentOk.filter(Boolean).length;
  showToast(`✅ ${realQty} key(s)! ${okCount===realQty?'📡 Enviadas!':okCount>0?`⚠️ ${okCount}/${realQty}`:'💾 Local'}`);
}
function copyAllGenerated(){const items=document.querySelectorAll('.result-key-text');copyText(Array.from(items).map(el=>el.textContent).join('\n'),`${items.length} keys copiadas!`);}

/* ============================================================
   SUPPORT & MISC
   ============================================================ */
async function sendSupportTicket(){
  const name=document.getElementById('support-name').value.trim();
  const problem=document.getElementById('support-problem').value;
  const desc=document.getElementById('support-desc').value.trim();
  if(!name||!problem||!desc){showToast('⚠️ Preencha todos os campos');return;}
  const btn=document.getElementById('support-send-btn');
  btn.disabled=true; btn.textContent='⏳ Enviando...';
  const embed={title:'🎫 Novo Ticket de Suporte',color:0x1a56e8,fields:[{name:'👤 Nome',value:name,inline:true},{name:'🔴 Problema',value:problem,inline:true},{name:'📝 Descrição',value:desc,inline:false}]};
  try{
    const res=await fetch(DISCORD_WEBHOOK,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({embeds:[embed]})});
    if(res.ok){showToast('✅ Ticket enviado!');closeModal('support-modal');}
  }catch(e){showToast('⚠️ Erro ao enviar');}
  btn.disabled=false; btn.textContent='📨 Enviar para o Suporte';
}

function updateLimit(added=0){
  if(added>0) limitCount=Math.min(limitCount+added,KEY_LIMIT);
  save(STORAGE_KEYS.limit,limitCount);
  const pct=(limitCount/KEY_LIMIT)*100;
  document.getElementById('limit-count-text').textContent=`${limitCount.toLocaleString('pt-BR')} / ${KEY_LIMIT.toLocaleString('pt-BR')}`;
  document.getElementById('limit-bar').style.width=pct+'%';
  document.getElementById('limit-bar').style.background=pct>90?'linear-gradient(90deg,#f87171,#ef4444)':pct>70?'linear-gradient(90deg,#fbbf24,#f59e0b)':'linear-gradient(90deg,#4ade80,#22c55e)';
}
function renderStats(){
  const all=getMergedKeys();
  document.getElementById('stat-pending').textContent=all.filter(k=>!k.used).length;
  document.getElementById('stat-active').textContent=all.filter(k=>k.used).length;
}
function renderHomeKeys(){
  const el=document.getElementById('home-keys-list');
  const all=getMergedKeys().slice(0,5);
  if(!all.length){el.innerHTML='<div style="text-align:center;padding:20px;color:#9ca3af;font-size:12px;">Nenhuma key gerada</div>';return;}
  el.innerHTML=all.map(k=>`<div class="list-item" style="border-radius:12px;margin-bottom:5px;box-shadow:var(--shadow);border:none;"><div class="item-body"><div class="item-key" style="font-size:12.5px;">${k.key}</div><div class="item-meta">${k.type}</div></div><span class="badge ${k.used?'active':'pending'}">${k.used?'Ativa':'Pendente'}</span><button class="copy-btn" onclick="copyText('${k.key}');event.stopPropagation();"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div>`).join('');
}

function buildChartData(){const out={};for(let i=6;i>=0;i--){const d=new Date();d.setDate(d.getDate()-i);const k=d.toISOString().slice(0,10);out[k]=chartData[k]||0;}return out;}
function renderChart(){
  const canvas=document.getElementById('mainChart');if(!canvas)return;
  const ctx=canvas.getContext('2d'),dpr=window.devicePixelRatio||1,W=canvas.offsetWidth||340,H=170;
  canvas.width=W*dpr;canvas.height=H*dpr;ctx.scale(dpr,dpr);
  const raw=buildChartData(),labels=Object.keys(raw).map(d=>d.slice(5)),values=Object.values(raw),maxV=Math.max(...values,5);
  const pad={l:36,r:14,t:14,b:30},W2=W-pad.l-pad.r,H2=H-pad.t-pad.b;
  ctx.clearRect(0,0,W,H);ctx.setLineDash([3,4]);ctx.strokeStyle='#e9ecf3';ctx.lineWidth=1;
  for(let i=0;i<=4;i++){const y=pad.t+(H2/4)*i;ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(W-pad.r,y);ctx.stroke();ctx.fillStyle='#9ca3af';ctx.font='9px DM Sans';ctx.textAlign='right';ctx.fillText(Math.round(maxV-(maxV/4)*i),pad.l-4,y+4);}
  ctx.setLineDash([]);
  const pts=values.map((v,i)=>({x:pad.l+(W2/(values.length-1||1))*i,y:pad.t+H2-(v/maxV)*H2}));
  const grad=ctx.createLinearGradient(0,pad.t,0,pad.t+H2);grad.addColorStop(0,'rgba(26,86,232,0.18)');grad.addColorStop(1,'rgba(26,86,232,0)');
  ctx.beginPath();ctx.moveTo(pts[0].x,pts[0].y);pts.forEach((p,i)=>{if(i>0)ctx.lineTo(p.x,p.y);});ctx.lineTo(pts[pts.length-1].x,pad.t+H2);ctx.lineTo(pts[0].x,pad.t+H2);ctx.fillStyle=grad;ctx.fill();
  ctx.beginPath();ctx.strokeStyle='#1a56e8';ctx.lineWidth=2.5;ctx.lineJoin='round';pts.forEach((p,i)=>i===0?ctx.moveTo(p.x,p.y):ctx.lineTo(p.x,p.y));ctx.stroke();
  pts.forEach(p=>{ctx.beginPath();ctx.arc(p.x,p.y,4,0,Math.PI*2);ctx.fillStyle='#1a56e8';ctx.fill();ctx.beginPath();ctx.arc(p.x,p.y,2,0,Math.PI*2);ctx.fillStyle='#fff';ctx.fill();});
  ctx.fillStyle='#9ca3af';ctx.font='9px DM Sans';ctx.textAlign='center';labels.forEach((l,i)=>ctx.fillText(l,pts[i].x,H-6));
}

function resetAllKeys(){ if(confirm('Limpar TODAS as keys?')){generatedKeys=[];apiKeys=[];limitCount=0;chartData={};deletedIds=new Set();save(STORAGE_KEYS.keys,[]);save(STORAGE_KEYS.apiKeys,[]);save(STORAGE_KEYS.limit,0);save(STORAGE_KEYS.chartData,{});save(STORAGE_KEYS.deleted,[]);updateLimit(0);renderStats();renderHomeKeys();renderChart();showToast('🗑️ Todas as keys limpas');} }
function copyAllPending(){const pending=getMergedKeys().filter(k=>!k.used).map(k=>k.key);if(!pending.length){showToast('Nenhuma key pendente');return;}copyText(pending.join('\n'),`${pending.length} keys copiadas!`);}
function showIntegration(){
  const apiUrl=packages[0]?.url||'https://teste-api-mcok.vercel.app/keys';
  document.getElementById('integration-js').textContent=`// Fetch API Example\nfetch("${apiUrl}")\n  .then(res => res.json())\n  .then(data => console.log(data));`;
  document.getElementById('integration-luau').textContent=`-- Roblox Example\nlocal res = game:GetService("HttpService"):GetAsync("${apiUrl}")\nprint(res)`;
  openModal('integration-modal');
}
function copyIntegration(type){copyText(document.getElementById('integration-'+type).textContent,'Código copiado!');}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function closeOnBg(e,id){if(e.target.id===id)closeModal(id);}
function toggleSearch(page){const el=document.getElementById('search-'+page);if(el.style.display==='none'||!el.style.display){el.style.display='block';el.querySelector('input').focus();}else{el.style.display='none';el.querySelector('input').value='';if(page==='keys')renderKeysList(getMergedKeys());if(page==='devices')renderDevicesList(getMergedKeys());}}
async function copyText(text,msg='Copiado!'){try{await navigator.clipboard.writeText(text);showToast('📋 '+msg);}catch(e){const ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);showToast('📋 '+msg);}}
function showToast(msg){document.querySelectorAll('.toast').forEach(t=>t.remove());const t=document.createElement('div');t.className='toast';t.textContent=msg;document.querySelector('.phone').appendChild(t);setTimeout(()=>t.remove(),3400);}
async function refreshAll(){showToast('🔄 Atualizando...');await fetchRemoteKeys();renderStats();renderHomeKeys();renderChart();if(document.getElementById('page-keys').classList.contains('active'))renderKeysList(getMergedKeys());if(document.getElementById('page-devices').classList.contains('active'))renderDevicesList(getMergedKeys());showToast('✅ Dados atualizados!');}

async function init(){
  applyLanguage(currentLang);
  updateLimit(0);updatePkgSelect();renderStats();renderHomeKeys();renderChart();
  await fetchRemoteKeys();renderStats();renderHomeKeys();
}

window.addEventListener('resize',()=>{if(document.getElementById('mainChart'))renderChart();});
init();
</script>
</body>
</html>
