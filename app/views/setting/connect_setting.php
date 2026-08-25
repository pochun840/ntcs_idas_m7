<div id="Connect_Setting" class="divMode" style="display: none; overflow-x: hidden;" >

    <div class="row">
        <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1.5%;">
            <?php echo $text['system_connect_setting']; ?>
        </div>
    </div>

    <!-- Agent IP -->
    <div class="row t2 align-items-center">
        <div class="col-3 t1"><?php echo $text['system_agent_ip'];?>:</div>
        <div class="col">
                <div class="connect-inline-row">
                    <div class="connect-input-wrap connect-agent-ip-wrap">
                        <input type="text" name="agent_server_ip" id="agent_server_ip" size="15"
                            value='<?php echo $data['agent_server_ip'];?>' required class="form-control">
                        <div class="invalid-feedback"></div>
                    </div>
                    <input type="button" onclick="agent_ip_save_new()" value="<?php echo $text['save']; ?>" class="all-btn w3-submit w3-border w3-round-large connect-save-btn">
                </div>
        </div>
    </div>

    <!-- Agent Type -->
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_agent_type'];?>:</div>
        <div class="col">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_0" value="0" <?php if($data['agent_type'] == 0){ echo "checked";} ?> >
                    <label class="form-check-label" for="agent_type_0"><?php echo $text['system_agent_none'];?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_1" value="1" <?php if($data['agent_type'] == 1){ echo "checked";} ?>>
                    <label class="form-check-label" for="agent_type_1"><?php echo $text['system_agent_client'];?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_2" value="2" <?php if($data['agent_type'] == 2){ echo "checked";} ?>>
                    <label class="form-check-label" for="agent_type_2"><?php echo $text['system_agent_server'];?></label>
                </div>

                <input type="button" onclick="agent_type_save()" value="<?php echo $text['save']; ?>"
                class="all-btn w3-submit w3-border w3-round-large" >
        </div>
    </div>

    <!-- Status -->
    <div class="row t2">
        <div class="col-3 t1"></div>
        <div class="col">
            <div style="margin-bottom: 5px;">
                <span><?php echo $text['system_client_status'];?>: </span><span id="c_status" style="display:inline-block;"></span>&nbsp;&nbsp;
                <span><?php echo $text['system_server_status'];?>: </span><span id="s_status" style="display:inline-block;"></span>
 
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck()"><?php echo $text['system_agent_check'];?></button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('start')"><?php echo $text['system_agent_start'];?></button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('stop')"><?php echo $text['system_agent_stop'];?></button>
 
            </div>
        </div>
    </div>
</div>


<script>
function agent_ip_save_new() {
    const ipEl = document.getElementById('agent_server_ip');
    const feedbackEl = (ipEl?.nextElementSibling && ipEl.nextElementSibling.classList.contains('invalid-feedback'))
        ? ipEl.nextElementSibling
        : null;

    // 清除欄位提示
    if (feedbackEl) {
        feedbackEl.textContent = '';
        feedbackEl.style.display = 'none';
    }

    // 語系
    let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
    lang = String(lang).toLowerCase().replace('_', '-');
    if (lang === 'en') lang = 'en-us';
    if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

    const MSG = {
        'en-us': 'Please enter a valid IP address.',
        'zh-tw': '請輸入有效的 IP 地址。',
        'zh-cn': '请输入有效的 IP 地址。'
    };
    const TITLE = { 'en-us': 'Warning', 'zh-tw': '警告', 'zh-cn': '警告' }[lang];
    const OKLBL  = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
    const CCLBL  = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

    const ip = (ipEl.value || '').trim();
    const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    // 先清狀態
    ipEl.classList.remove('is-invalid');

    // ❌ 驗證失敗
    if (!ip || !ipRegex.test(ip)) {
        ipEl.classList.add('is-invalid');

        if (!ipEl._bindInvalidClear) {
        ipEl.addEventListener('input', function onIn() {
            ipEl.classList.remove('is-invalid');
            ipEl.removeEventListener('input', onIn);
            ipEl._bindInvalidClear = false;
        });
        ipEl._bindInvalidClear = true;
        }

        alertify.confirm(
        TITLE,
        MSG[lang],
        function onOk() {
            ipEl.focus();
            ipEl.select?.();
        },
        function onCancel() {
            // 取消時，直接清掉紅框
            ipEl.classList.remove('is-invalid');
        }
        ).set('labels', { ok: OKLBL, cancel: CCLBL });

        return; // 不送出
    }

    // ✅ 驗證通過 → 呼叫後端
    const spinner = document.getElementById('spinner');
    if (spinner) spinner.style.display = 'block';

    $.ajax({
        url: "?url=Admins/SetAgentIp",
        method: "POST",
        data: { agent_server_ip: ip },
        success: function (response) {
        let res = {};
        try { res = ((typeof response === 'string') ? JSON.parse(response) : response) || {}; } catch {}

        if (spinner) spinner.style.display = 'none';

        alertify.confirm(
            res.res_type || 'Info',
            res.res_msg || 'Done.',
            function onOk() {
            sessionStorage.setItem('Connect_Setting', 'block');
            sessionStorage.setItem('Controller_Setting', 'none');
            },
            function onCancel() {
            // 取消時不用做事
            }
        ).set('labels', { ok: OKLBL, cancel: CCLBL });

        setTimeout(() => alertify.closeAll(), 3000);

        if (res.res_number != null) ipEl.value = res.res_number;
        },
        error: function (xhr) {
        if (spinner) spinner.style.display = 'none';
        alertify.confirm(
            'Error',
            (xhr && xhr.responseText) || 'Request failed.',
            null,
            null
        ).set('labels', { ok: OKLBL, cancel: CCLBL });
        }
    });
}
</script>
