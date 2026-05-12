<style>
/* Setting Account CSS Raw Text Fix V1 */
/* =====================================================
   Setting Account page - Job style fixed V2
   目標：Account 分頁仿照 Job 頁面，不讓 New/Edit/Delete 跑到左下角
   ===================================================== */
#AccountDisplay.setting-account-page {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 125px);
    padding-bottom: 105px;
    box-sizing: border-box;
}


#AccountDisplay .account-summary-bar {
    width: 96%;
    margin: 14px auto 0 auto;
    min-height: 42px;
    padding: 8px 14px;
    box-sizing: border-box;
    border: 1px solid #d8d8d8;
    border-radius: 8px;
    background: #f7f7f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    color: #333333;
    font-size: 15px;
    font-weight: 700;
}

#AccountDisplay .account-summary-left,
#AccountDisplay .account-summary-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

#AccountDisplay .account-search-input {
    width: 220px;
    height: 32px;
    padding: 4px 10px;
    border: 1px solid #cfcfcf;
    border-radius: 8px;
    background: #ffffff;
    color: #222222;
    font-size: 15px;
    font-weight: 700;
    box-sizing: border-box;
}

#AccountDisplay .account-search-input:focus {
    outline: none;
    border-color: #888888;
}

#AccountDisplay .account-action-busy,
#AccountDisplay .account-action-busy:hover {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

#AccountDisplay .account-protected-badge {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    border-radius: 12px;
    background: #e8e8e8;
    color: #555555;
    font-size: 12px;
    font-weight: 800;
    vertical-align: middle;
}

#AccountDisplay .account-admin-badge {
    background: #e6f0ff;
    color: #174a8b;
}

#AccountDisplay .account-sync-controller-btn {
    width: 150px !important;
}

#AccountDisplay .account-table-container {
    width: 96%;
    margin: 18px auto 0 auto;
    overflow: hidden;
}

#AccountDisplay #style-accounttable {
    float: none;
    width: 100%;
    height: calc(100vh - 255px);
    min-height: 270px;
    max-height: 430px;
    overflow-y: auto;
    overflow-x: hidden;
}

#AccountDisplay .scrollbar-force-overflow {
    min-height: auto;
}

#AccountDisplay #account_user_table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

/* User List 隱藏 Law 後，No 欄縮小、User Name 欄放大 */
#AccountDisplay #account_user_table th:first-child,
#AccountDisplay #account_user_table td:first-child {
    width: 25%;
}

#AccountDisplay #account_user_table th:nth-child(2),
#AccountDisplay #account_user_table td:nth-child(2) {
    width: 75%;
}

#AccountDisplay #account_user_table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #616161;
    color: #fff;
    font-size: 18px;
    height: 40px;
    text-align: center;
    vertical-align: middle;
}

#AccountDisplay #account_user_table th,
#AccountDisplay #account_user_table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

#AccountDisplay #account_user_table tbody td {
    height: 36px;
    font-size: 17px;
}

#AccountDisplay #account_user_table tbody tr {
    cursor: pointer;
}

#AccountDisplay #account_user_table tbody tr:nth-child(odd) td {
    background-color: #eeeeee;
}

#AccountDisplay #account_user_table tbody tr:nth-child(even) td {
    background-color: #ffffff;
}

/* 與 Job 頁面一致：onclick 選取列使用 .selected */
#AccountDisplay #account_user_table tbody tr.selected,
#AccountDisplay #account_user_table tbody tr.selected td {
    background-color: #9AC0CD !important;
    color: #000000;
    font-weight: normal;
}

/* Footer buttons：參考 Job 頁面，固定下方置中 */
#AccountDisplay .account-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    padding: 12px 20px 22px 20px;
    background-color: #ffffff;
    text-align: center;
    z-index: 2;
    box-sizing: border-box;
}

#AccountDisplay .account-footer .buttonbox {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 28px;
    padding: 0;
    margin: 0 auto;
}

#AccountDisplay .account-footer .buttonbox input {
    width: 100px;
    height: 50px;
    background: #333333;
    color: #ffffff;
    font-size: 20px;
    border: 2px outset #ffffff;
    border-radius: 10px;
    transition: color 0.3s, background-color 0.3s, transform 0.1s;
}

#AccountDisplay .account-footer .buttonbox input:hover {
    cursor: pointer;
    background: #DDDDDD;
    color: #000000;
}

#AccountDisplay .account-footer .buttonbox input:active {
    background-color: #DDDDDD;
    box-shadow: 0 5px #666;
    transform: translateY(4px);
}

/* Modal：仿 Job modal，尺寸固定避免破版 */
#settingAccountModal.modal {
    display: none;
    position: fixed;
    z-index: 30;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    padding-top: 42px;
    overflow: auto;
    background: rgba(255, 255, 255, 0.35);
}

#settingAccountModal .setting-account-modal {
    width: 58%;
    min-width: 620px;
    max-width: 760px;
    margin: 0 auto;
    background-color: #AAAAAA;
    border: 1px solid #888;
}

#settingAccountModal .modal-header {
    position: relative;
    background-color: #686767;
    height: 56px;
    color: #FFFFFF;
    font-size: 20px;
    display: flex;
    align-items: center;
    padding-left: 18px;
}

#settingAccountModal .modal-header h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 500;
}

#settingAccountModal .account-modal-x {
    position: absolute;
    top: 6px;
    right: 10px;
    width: 44px;
    height: 44px;
    line-height: 36px;
    text-align: center;
    font-size: 32px;
    padding: 0;
}

#settingAccountModal .account-modal-body {
    background-color: #D8D8D8;
    padding: 44px 48px 70px 48px;
}

#settingAccountModal .account-form-row {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

#settingAccountModal .account-form-row .t1 {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 280px;
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

#settingAccountModal .account-form-row .t2 {
    width: 230px;
    margin: 0;
}

#settingAccountModal .account-form-row .t2 input {
    width: 100%;
    height: 36px;
    font-size: 18px;
    border-radius: 7px;
    border: 1px solid #dddddd;
    background: #ffffff;
    padding: 4px 8px;
    box-sizing: border-box;
}

/* Password eye mask toggle：用眼睛符號取代 Show / Hide 文字 */
#settingAccountModal .account-password-wrap {
    position: relative;
    width: 100%;
}

#settingAccountModal .account-password-wrap input {
    width: 100%;
    padding-right: 44px;
}

#settingAccountModal .password-eye-btn,
#settingAccountModal .password-toggle-btn {
    position: absolute;
    top: 50%;
    right: 4px;
    transform: translateY(-50%);
    width: 36px;
    height: 30px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #444444;
    cursor: pointer;
    padding: 0;
    font-size: 0; /* 保險：即使舊版按鈕還有 Show/Hide 文字，也不顯示 */
    display: flex;
    align-items: center;
    justify-content: center;
}

#settingAccountModal .password-eye-btn::before,
#settingAccountModal .password-toggle-btn::before {
    content: "\1F441";
    font-size: 22px;
    line-height: 1;
}

#settingAccountModal .password-eye-btn .eye-symbol,
#settingAccountModal .password-toggle-btn .eye-symbol {
    display: none; /* 使用 ::before 統一顯示，避免不同瀏覽器排版差異 */
}

#settingAccountModal .password-eye-btn.is-visible::after,
#settingAccountModal .password-toggle-btn.is-visible::after {
    content: "";
    position: absolute;
    width: 26px;
    height: 3px;
    border-radius: 3px;
    background: #444444;
    transform: rotate(-45deg);
}

#settingAccountModal .password-eye-btn:hover,
#settingAccountModal .password-toggle-btn:hover {
    background: rgba(0, 0, 0, 0.08);
}

#settingAccountModal .modal-footer {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 26px;
    background-color: #686767;
    height: 64px;
    padding: 0;
}

#settingAccountModal .button-modal {
    background-color: #3333ff;
    border: 1px outset #CCFFFF;
    border-radius: 8px;
    color: white;
    padding: 0;
    text-align: center;
    font-size: 20px;
    cursor: pointer;
    height: 42px;
    width: 96px;
}

#settingAccountModal .button-modal.closebtn {
    background-color: #888899;
}

#settingAccountModal .button-modal:hover {
    cursor: pointer;
    background: #336699;
    color: white;
}



/* operation_audit_log top tab button */
.operation-audit-top-button {
    min-width: 168px;
    padding-left: 10px !important;
    padding-right: 10px !important;
}

/* AlertifyJS：不要 display:none header，避免移除 title 後破版 */
.alertify .ajs-header,
.alertifyjs .ajs-header,
.ajs-dialog .ajs-header {
    color: transparent !important;
    text-shadow: none !important;
    min-height: 22px !important;
    padding: 8px 16px 0 16px !important;
    box-sizing: border-box !important;
}

.alertify .ajs-close,
.alertifyjs .ajs-close,
.ajs-dialog .ajs-close {
    color: #333333 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.alertify .ajs-dialog,
.alertifyjs .ajs-dialog,
.ajs-dialog {
    width: 520px !important;
    max-width: 92vw !important;
    min-height: auto !important;
    box-sizing: border-box !important;
}

.alertify .ajs-body,
.alertifyjs .ajs-body,
.ajs-dialog .ajs-body {
    min-height: auto !important;
}

.alertify .ajs-content,
.alertifyjs .ajs-content,
.ajs-dialog .ajs-content {
    font-size: 17px !important;
    line-height: 1.75 !important;
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    padding: 12px 24px 16px 24px !important;
    text-align: left !important;
}

.alertify .ajs-footer,
.alertifyjs .ajs-footer,
.ajs-dialog .ajs-footer {
    padding: 8px 18px 14px 18px !important;
}

.alertify .ajs-footer .ajs-buttons,
.alertifyjs .ajs-footer .ajs-buttons,
.ajs-dialog .ajs-footer .ajs-buttons {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
    gap: 18px !important;
}

@media (max-width: 900px) {
    #AccountDisplay .account-footer .buttonbox {
        gap: 14px;
    }

    #AccountDisplay .account-footer .buttonbox input {
        width: 88px;
        height: 46px;
        font-size: 18px;
    }

    #settingAccountModal .setting-account-modal {
        width: 92%;
        min-width: 0;
    }

    #settingAccountModal .account-form-row {
        display: block;
    }

    #settingAccountModal .account-form-row .t1,
    #settingAccountModal .account-form-row .t2 {
        width: 100%;
        margin-bottom: 6px;
    }
}

/* Account i18n DOM force patch style: labels can be wider in Chinese */
#settingAccountModal .account-form-row .t1 { white-space: nowrap; }

/* QRCode force patch */
#AccountDisplay #account_user_table th:first-child,
#AccountDisplay #account_user_table td:first-child { width: 20% !important; }

#AccountDisplay #account_user_table th:nth-child(2),
#AccountDisplay #account_user_table td:nth-child(2) { width: 55% !important; }

#AccountDisplay #account_user_table th:nth-child(3),
#AccountDisplay #account_user_table td:nth-child(3) { width: 25% !important; }

#AccountDisplay .account-qrcode-cell { text-align: center !important; }

#AccountDisplay .account-qrcode-btn {
    min-width: 92px;
    height: 32px;
    padding: 0 14px;
    border: 1px solid #ffffff;
    border-radius: 7px;
    background: #333333;
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
}

#AccountDisplay .account-qrcode-btn:hover {
    background: #dddddd;
    color: #000000;
}
</style>
