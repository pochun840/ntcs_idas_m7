<?php
$is_admin_login = (isset($_COOKIE['username']) && strtolower(trim((string)$_COOKIE['username'])) === 'admin');
?>

<div class="container-ms data-audit-log-page">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['data'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="w3-center" style="position: relative; padding-right: 10px">
                <button id="data_bnt1" name="History_Display" class="button" onclick="window.location.href='?url=Data'"><?php echo $text['data_history'];?></button>
                <button id="data_bnt2" name="Export_Data_Display" class="button" onclick="window.location.href='?url=Data'"><?php echo $text['data_export'];?></button>
                <button id="data_bnt3" name="Export_Data_download" class="button" onclick="window.location.href='?url=Data'"><?php echo $text['download_chart'];?></button>
                <button id="data_bnt4" name="Customize" class="button hide-mobile" onclick="window.location.href='?url=Customize'"><?php echo $text['customize'];?></button>
                <button id="data_bnt5" name="Torque_line_chart" class="button hide-mobile" onclick="window.location.href='?url=Data/drawLineChart'"><?php echo $text['tor_line_chart'];?></button>
                <?php if ($is_admin_login) { ?>
                    <button id="data_bnt7" name="Operation_Audit_Log_Display" class="button active operation-audit-top-button" onclick="window.location.href='?url=Data/AuditLog'"><?php echo htmlspecialchars($text['audit_button'] ?? 'operation_audit_log', ENT_QUOTES, 'UTF-8'); ?></button>
                <?php } ?>
            </div>

            <?php require_once '../app/views/setting/operation_audit_log.php'; ?>
            <?php
            $detailPath = '../app/views/setting/operation_audit_log_detail.php';
            if (file_exists($detailPath)) {
                require_once $detailPath;
            }
            ?>
        </div>
    </div>
</div>

<style>
.data-audit-log-page #OperationAuditLogDisplay.operation-audit-log-page {
    display: block !important;
    width: 98%;
    margin: 12px auto 0 auto;
}

.data-audit-log-page .w3-center .button {
    min-width: auto;
    margin: 4px 8px;
    padding: 8px 14px;
    line-height: 1.2;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var panel = document.getElementById('OperationAuditLogDisplay');
    if (panel) panel.style.display = 'block';

    if (typeof applyOperationAuditI18n === 'function') {
        applyOperationAuditI18n();
    }

    if (typeof loadSettingOperationAuditLogs === 'function') {
        loadSettingOperationAuditLogs(true);
    }
});
</script>
