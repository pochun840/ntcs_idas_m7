<?php if (idas_is_icontroller()): ?>
<?php
$idasUpdateLangRaw = strtolower(trim((string)(
    $_SESSION['language']
    ?? $_COOKIE['language']
    ?? $_COOKIE['languages']
    ?? 'en-us'
)));
$idasUpdateLangRaw = str_replace('_', '-', $idasUpdateLangRaw);

if (in_array($idasUpdateLangRaw, ['zh-tw', 'zh-hant', 'tw'], true)) {
    $idasUpdateLang = 'zh-tw';
} elseif (in_array($idasUpdateLangRaw, ['zh-cn', 'zh-hans', 'cn'], true)) {
    $idasUpdateLang = 'zh-cn';
} else {
    $idasUpdateLang = 'en-us';
}

$idasUpdateI18nAll = [
    'en-us' => [
        'downgrade_detected' => '⚠ Downgrade detected',
        'special_switch_detected' => '⚠ Special version switch detected',
        'downgrade_warning' => "The selected package is a downgrade or special version switch.\nFor downgrade, the system will rebuild the iDAS DB. Switching between Standard and SA349 in either direction also forces a DB rebuild to avoid incompatible edition data remaining.\nA backup and rollback snapshot will be created before the update.",
        'pack_version' => 'Update package version',
        'version_status' => 'Version status',
        'status_upgrade' => 'Upgrade',
        'status_upgrade_special' => 'Upgrade / Special version switch',
        'status_same' => 'Same version',
        'status_downgrade' => 'Downgrade',
        'status_special_switch' => 'Special version switch',
        'db_rebuild_required' => 'DB rebuild required',
        'checking' => 'Checking package version...',
        'check_failed' => 'Unable to verify package version.',
    ],
    'zh-tw' => [
        'downgrade_detected' => '⚠ 偵測到降版本',
        'special_switch_detected' => '⚠ 偵測到特殊版本切換',
        'downgrade_warning' => "系統偵測到更新包為降版本或特殊版本切換。\n降版本時會重建 iDAS DB；Standard ↔ SA349 任一方向切換也會強制重建 DB，避免不同版本格式資料殘留。\n更新前系統會先建立備份與 rollback 快照。",
        'pack_version' => '更新包版本',
        'version_status' => '版本判斷',
        'status_upgrade' => '升版本',
        'status_upgrade_special' => '升版本 / 特殊版本切換',
        'status_same' => '同版本更新',
        'status_downgrade' => '降版本',
        'status_special_switch' => '特殊版本切換',
        'db_rebuild_required' => '需要重建 DB',
        'checking' => '正在檢查更新包版本...',
        'check_failed' => '無法驗證更新包版本。',
    ],
    'zh-cn' => [
        'downgrade_detected' => '⚠ 检测到降版本',
        'special_switch_detected' => '⚠ 检测到特殊版本切换',
        'downgrade_warning' => "系统检测到更新包为降版本或特殊版本切换。\n降版本时会重建 iDAS DB；Standard ↔ SA349 任一方向切换也会强制重建 DB，避免不同版本格式数据残留。\n更新前系统会先建立备份与 rollback 快照。",
        'pack_version' => '更新包版本',
        'version_status' => '版本判断',
        'status_upgrade' => '升版本',
        'status_upgrade_special' => '升版本 / 特殊版本切换',
        'status_same' => '同版本更新',
        'status_downgrade' => '降版本',
        'status_special_switch' => '特殊版本切换',
        'db_rebuild_required' => '需要重建 DB',
        'checking' => '正在检查更新包版本...',
        'check_failed' => '无法验证更新包版本。',
    ],
];
$idasUpdateI18n = $idasUpdateI18nAll[$idasUpdateLang] ?? $idasUpdateI18nAll['en-us'];
?>
<script>
window.IDAS_UPDATE_I18N = <?php echo json_encode($idasUpdateI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<div id="iDas-Update_Setting" class="divMode" style="display: none;">
    <div class="row t2" style="padding-top: 30px">
        <div class="col-3 t1"><?php echo $text['system_idas_current_version'];?>:</div>
        <div class="col-3 t2">
            <input id="idas_software_version" name="idas_software_version" type="text" value="<?php echo htmlspecialchars((string)$data['idas_version'], ENT_QUOTES, 'UTF-8');?>" style="height: 32px" class="form-control" disabled>
        </div>
    </div>
 
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_idas_upload_file'];?>:</div>
        <div class="col-3 t2">
            <input type="file" id="file-uploader" data-target="file-uploader" accept=".pack" class="form-control" style="height: 32px">
        </div>
    </div>

    <div class="row t2 idas-pack-check-row is-hidden" id="idas-pack-check-row">
        <div class="col-3 t1"></div>
        <div class="col-7 t2">
            <div class="idas-pack-check-panel" id="idas-pack-check-panel">
                <div><strong><?php echo htmlspecialchars($idasUpdateI18n['pack_version'], ENT_QUOTES, 'UTF-8'); ?>：</strong><span id="idas-pack-version">-</span></div>
                <div><strong><?php echo htmlspecialchars($idasUpdateI18n['version_status'], ENT_QUOTES, 'UTF-8'); ?>：</strong><span id="idas-pack-status">-</span></div>
            </div>
        </div>
    </div>

    <div class="row t2 idas-downgrade-row is-hidden" id="idas-downgrade-row">
        <div class="col-3 t1"></div>
        <div class="col-7 t2">
            <div class="idas-downgrade-notice">
                <strong id="idas-risk-title"><?php echo htmlspecialchars($idasUpdateI18n['downgrade_detected'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <div class="idas-downgrade-warning">
                    <?php echo nl2br(htmlspecialchars($idasUpdateI18n['downgrade_warning'], ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;margin-top:50px;">
        <input id="idas-upload-btn" class="all-btn w3-submit w3-border w3-round-large idas-btn-disabled" type="button" value="<?php echo $text['system_idas_upload_file'];?>" onclick='idas_update();' disabled>
    </div>
    <?php require '../app/views/setting/idas_database_maintenance.php'; ?>
</div>
<?php else: ?>
<?php
$idasUpdateLangRaw = strtolower(trim((string)(
    $_SESSION['language']
    ?? $_COOKIE['language']
    ?? $_COOKIE['languages']
    ?? 'en-us'
)));
$idasUpdateLangRaw = str_replace('_', '-', $idasUpdateLangRaw);

if (in_array($idasUpdateLangRaw, ['zh-tw', 'zh-hant', 'tw'], true)) {
    $idasUpdateLang = 'zh-tw';
} elseif (in_array($idasUpdateLangRaw, ['zh-cn', 'zh-hans', 'cn'], true)) {
    $idasUpdateLang = 'zh-cn';
} else {
    $idasUpdateLang = 'en-us';
}

$idasUpdateI18nAll = [
    'en-us' => [
        'downgrade_detected' => '⚠ Downgrade detected',
        'special_switch_detected' => '⚠ Special version switch detected',
         'downgrade_warning' => "The system detected that this update is a downgrade or a special version switch.\n\nDuring the update, iDAS settings will be refreshed to prevent older versions from being unable to read the data correctly.\n\nIf the system is switching from the SA349 special version back to a standard version, SA349-only special accounts and permission settings will be removed automatically.\n\nA backup will be created before the update. Please make sure the update package is correct before continuing.",
        'pack_version' => 'Update package version',
        'version_status' => 'Version status',
        'status_upgrade' => 'Upgrade',
        'status_upgrade_special' => 'Upgrade / Special version switch',
        'status_same' => 'Same version',
        'status_downgrade' => 'Downgrade',
        'status_special_switch' => 'Special version switch',
        'db_rebuild_required' => 'DB rebuild required',
        'checking' => 'Checking package version...',
        'check_failed' => 'Unable to verify package version.',
    ],
    'zh-tw' => [
        'downgrade_detected' => '⚠ 偵測到降版本',
        'special_switch_detected' => '⚠ 偵測到特殊版本切換',
        'downgrade_warning' => "系統偵測到這次更新屬於降版本或特殊版本切換。\n\n更新過程中，系統會重新整理 iDAS 的設定資料，以避免舊版本無法正常讀取資料。\n\n如果是從 SA349 特殊版本切換回一般版本，系統會自動移除只適用於 SA349 的特殊帳號與權限設定。\n\n更新前系統會先建立備份，請確認更新包正確後再繼續。",
        'pack_version' => '更新包版本',
        'version_status' => '版本判斷',
        'status_upgrade' => '升版本',
        'status_upgrade_special' => '升版本 / 特殊版本切換',
        'status_same' => '同版本更新',
        'status_downgrade' => '降版本',
        'status_special_switch' => '特殊版本切換',
        'db_rebuild_required' => '需要重建 DB',
        'checking' => '正在檢查更新包版本...',
        'check_failed' => '無法驗證更新包版本。',
    ],
    'zh-cn' => [
        'downgrade_detected' => '⚠ 检测到降版本',
        'special_switch_detected' => '⚠ 检测到特殊版本切换',
        'downgrade_warning' => "系统检测到本次更新属于降版本或特殊版本切换。\n\n更新过程中，系统会重新整理 iDAS 的设置资料，以避免旧版本无法正常读取数据。\n\n如果是从 SA349 特殊版本切换回一般版本，系统会自动移除只适用于 SA349 的特殊账号与权限设置。\n\n更新前系统会先建立备份，请确认更新包正确后再继续。",
        'pack_version' => '更新包版本',
        'version_status' => '版本判断',
        'status_upgrade' => '升版本',
        'status_upgrade_special' => '升版本 / 特殊版本切换',
        'status_same' => '同版本更新',
        'status_downgrade' => '降版本',
        'status_special_switch' => '特殊版本切换',
        'db_rebuild_required' => '需要重建 DB',
        'checking' => '正在检查更新包版本...',
        'check_failed' => '无法验证更新包版本。',
    ],
];
$idasUpdateI18n = $idasUpdateI18nAll[$idasUpdateLang] ?? $idasUpdateI18nAll['en-us'];
?>
<script>
window.IDAS_UPDATE_I18N = <?php echo json_encode($idasUpdateI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<?php
$idasCurrentVersionDisplay = (string)($data['idas_version'] ?? ($data['iDas_Vesion'] ?? ''));
$idasCurrentVersionDisplay = trim($idasCurrentVersionDisplay);
if ($idasCurrentVersionDisplay !== '' && preg_match('/^(\d+\.\d+)\.0(_[A-Za-z0-9][A-Za-z0-9._-]*)$/', $idasCurrentVersionDisplay, $m)) {
    $idasCurrentVersionDisplay = $m[1] . $m[2];
}
?>
<div id="iDas-Update_Setting" class="divMode" style="display: none;">
    <div class="row t2" style="padding-top: 30px">
        <div class="col-3 t1"><?php echo $text['system_idas_current_version'];?>:</div>
        <div class="col-3 t2">
            <input id="idas_software_version" name="idas_software_version" type="text" value="<?php echo htmlspecialchars($idasCurrentVersionDisplay, ENT_QUOTES, 'UTF-8');?>" style="height: 32px" class="form-control" disabled>
        </div>
    </div>
 
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_idas_upload_file'];?>:</div>
        <div class="col-3 t2">
            <input type="file" id="file-uploader" data-target="file-uploader" accept=".pack" class="form-control" style="height: 32px">
        </div>
    </div>

    <div class="row t2 idas-pack-check-row is-hidden" id="idas-pack-check-row">
        <div class="col-3 t1"></div>
        <div class="col-7 t2">
            <div class="idas-pack-check-panel" id="idas-pack-check-panel">
                <div><strong><?php echo htmlspecialchars($idasUpdateI18n['pack_version'], ENT_QUOTES, 'UTF-8'); ?>：</strong><span id="idas-pack-version">-</span></div>
                <div><strong><?php echo htmlspecialchars($idasUpdateI18n['version_status'], ENT_QUOTES, 'UTF-8'); ?>：</strong><span id="idas-pack-status">-</span></div>
            </div>
        </div>
    </div>

    <div class="row t2 idas-downgrade-row is-hidden" id="idas-downgrade-row">
        <div class="col-3 t1"></div>
        <div class="col-7 t2">
            <div class="idas-downgrade-notice">
                <strong id="idas-risk-title"><?php echo htmlspecialchars($idasUpdateI18n['downgrade_detected'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <div class="idas-downgrade-warning">
                    <?php echo nl2br(htmlspecialchars($idasUpdateI18n['downgrade_warning'], ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;margin-top:50px;">
        <input id="idas-upload-btn" class="all-btn w3-submit w3-border w3-round-large idas-btn-disabled" type="button" value="<?php echo $text['system_idas_upload_file'];?>" onclick='idas_update();' disabled>
    </div>
    <?php require '../app/views/setting/idas_database_maintenance.php'; ?>
</div>
<?php endif; ?>
