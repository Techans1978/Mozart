<?php
// NUNCA deixe espaços/linhas acima deste <?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../config.php'; // ajuste se necessário
?>
<!-- Topbar -->
<header class="topbar">
  <div class="wrap">
    <div class="brand"><div class="logo" aria-hidden="true"></div> MOZART</div>

        <div class="top-actions">
          <button class="btn primary" id="btnNovo">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Login
          </button>

         </div>

      </div>

    </div>

  </div>
</header>

