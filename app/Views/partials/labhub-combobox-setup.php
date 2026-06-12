<?php
if (!isset($labhub_catalog)) {
    $labhub_catalog = [];
}
if (!isset($labhub_can_create)) {
    $labhub_can_create = (($_SESSION['perfil'] ?? '') === 'coordenador');
}
?>
<link rel="stylesheet" href="css/labhub-combobox.css">
<script>
window.LABHUB_CATALOG = <?= json_encode($labhub_catalog, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
window.LABHUB_CAN_CREATE = <?= $labhub_can_create ? 'true' : 'false' ?>;
window.LABHUB_API_CADASTROS = 'api_cadastros.php';
</script>
<script src="js/labhub-combobox.js"></script>
