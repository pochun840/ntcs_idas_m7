<?php
$maintenanceText = [
    'zh-tw' => ['title'=>'資料庫儲存空間','free'=>'可用空間','last'=>'最近維護','cleanup'=>'立即安全清理','loading'=>'讀取中…','never'=>'尚無紀錄','confirm'=>'只會清除過期成功備份、舊 Log 與更新暫存；不會刪除正式 DB、失敗備份或進行中的備份。確定執行？','completed'=>'安全清理完成','failed'=>'清理程序忙碌或失敗','freed'=>'釋放空間'],
    'zh-cn' => ['title'=>'数据库存储空间','free'=>'可用空间','last'=>'最近维护','cleanup'=>'立即安全清理','loading'=>'读取中…','never'=>'尚无记录','confirm'=>'只会清除过期成功备份、旧 Log 与更新暂存；不会删除正式 DB、失败备份或进行中的备份。确定执行？','completed'=>'安全清理完成','failed'=>'清理程序忙碌或失败','freed'=>'释放空间'],
    'en-us' => ['title'=>'Database storage','free'=>'Free space','last'=>'Last maintenance','cleanup'=>'Run safe cleanup','loading'=>'Loading…','never'=>'No record','confirm'=>'Only expired successful backups, old logs and update temporary files will be cleaned. Production DBs and failed/pending backups are preserved. Continue?','completed'=>'Safe cleanup completed','failed'=>'Cleanup is busy or failed','freed'=>'Freed'],
];
$maintenanceLang = $idasUpdateLang ?? 'en-us';
$maintenanceI18n = $maintenanceText[$maintenanceLang] ?? $maintenanceText['en-us'];
?>
<!-- V20: manual storage panel is retained for future maintenance use but hidden from the production UI. -->
<div class="row t2 idas-storage-row" hidden aria-hidden="true">
    <div class="col-3 t1"><?php echo htmlspecialchars($maintenanceI18n['title'], ENT_QUOTES, 'UTF-8'); ?>:</div>
    <div class="col-7 t2">
        <div class="idas-storage-panel" id="idas-storage-panel">
            <span><?php echo htmlspecialchars($maintenanceI18n['free'], ENT_QUOTES, 'UTF-8'); ?>: <strong id="idas-storage-free"><?php echo htmlspecialchars($maintenanceI18n['loading'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <span><?php echo htmlspecialchars($maintenanceI18n['last'], ENT_QUOTES, 'UTF-8'); ?>: <span id="idas-storage-last">-</span></span>
            <button type="button" id="idas-storage-cleanup-btn" class="all-btn w3-border w3-round-large" onclick="idasRunSafeDatabaseCleanup()"><?php echo htmlspecialchars($maintenanceI18n['cleanup'], ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
    </div>
</div>
<script>window.IDAS_STORAGE_I18N=<?php echo json_encode($maintenanceI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;</script>
