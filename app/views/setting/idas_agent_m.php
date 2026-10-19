<div id="Connect_Setting" class="divMode connect-setting-mobile" style="display: none">
    <div class="col t1 connect-title">
        <?php echo $text['system_connect_setting']; ?>
    </div>

    <!-- Agent IP -->
    <div class="col t1 connect-subtitle">
        <?php echo $text['system_agent_ip']; ?>:
    </div>

    <div class="row t2 border-bottom connect-block connect-ip-row">
        <div class="col t2">
            <form id="agent_ip" class="connect-form" method="post" onsubmit="return false;">
                <div class="connect-stack">
                    <input type="text"
                           name="agent_server_ip"
                           id="agent_server_ip"
                           required
                           class="t3 w3-submit w3-border w3-round connect-input"
                           value="<?php echo htmlspecialchars($data['agent_server_ip'] ?? '', ENT_QUOTES); ?>">

                    <input type="button"
                           value="<?php echo $text['save']; ?>"
                           onclick="agent_ip_save()"
                           class="all-btn w3-submit w3-border w3-round-large connect-single-btn">
                </div>

                <div class="invalid-feedback"></div>

                <div class="connect-current-value">
                    <span>
                        <?php echo $text['system_agent_ip']; ?> :
                        <?php echo htmlspecialchars($data['agent_server_ip'] ?? '', ENT_QUOTES); ?>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Agent Type -->
    <div class="col t1 connect-subtitle">
        <?php echo $text['system_agent_type']; ?>:
    </div>

    <div class="row t2 connect-block">
        <div class="col t2">
            <form id="agent_type_form" class="connect-form" method="post" onsubmit="return false;">
                <div class="connect-radio-group">
                    <label class="form-check form-check-inline connect-radio-item" for="agent_type_0">
                        <input class="form-check-input"
                               type="radio"
                               name="agent_type"
                               id="agent_type_0"
                               value="0"
                               <?php echo (($data['agent_type'] ?? '') == 0) ? 'checked' : ''; ?>>
                        <span class="form-check-label"><?php echo $text['system_agent_none']; ?></span>
                    </label>

                    <label class="form-check form-check-inline connect-radio-item" for="agent_type_1">
                        <input class="form-check-input"
                               type="radio"
                               name="agent_type"
                               id="agent_type_1"
                               value="1"
                               <?php echo (($data['agent_type'] ?? '') == 1) ? 'checked' : ''; ?>>
                        <span class="form-check-label"><?php echo $text['system_agent_client']; ?></span>
                    </label>

                    <label class="form-check form-check-inline connect-radio-item" for="agent_type_2">
                        <input class="form-check-input"
                               type="radio"
                               name="agent_type"
                               id="agent_type_2"
                               value="2"
                               <?php echo (($data['agent_type'] ?? '') == 2) ? 'checked' : ''; ?>>
                        <span class="form-check-label"><?php echo $text['system_agent_server']; ?></span>
                    </label>
                </div>

                <div class="connect-btn-row">
                    <input type="button"
                           value="<?php echo $text['save']; ?>"
                           onclick="agent_type_save()"
                           class="all-btn w3-submit w3-border w3-round-large connect-single-btn">
                </div>
            </form>
        </div>
    </div>

    <!-- Status -->
    <div class="row">
        <div class="col t2 connect-block connect-status-block">
            <div class="connect-status-line">
                <span class="connect-status-label"><?php echo $text['system_client_status']; ?>:</span>
                <span id="c_status" class="connect-status-value"></span>
            </div>

            <div class="connect-status-line">
                <span class="connect-status-label"><?php echo $text['system_server_status']; ?>:</span>
                <span id="s_status" class="connect-status-value"></span>
            </div>

            <div class="connect-status-btns">
                <button type="button"
                        class="all-btn w3-button w3-border w3-round-large"
                        onclick="StatusCheck()">
                    <?php echo $text['system_agent_check']; ?>
                </button>

                <button type="button"
                        class="all-btn w3-button w3-border w3-round-large"
                        onclick="StatusCheck('start')">
                    <?php echo $text['system_agent_start']; ?>
                </button>

                <button type="button"
                        class="all-btn w3-button w3-border w3-round-large"
                        onclick="StatusCheck('stop')">
                    <?php echo $text['system_agent_stop']; ?>
                </button>
            </div>
        </div>
    </div>
</div>



<style>
/* =========================
   Connect Setting Mobile Fix
   ========================= */

#Connect_Setting,
#Connect_Setting *{
    box-sizing: border-box;
}

#Connect_Setting.connect-setting-mobile{
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    padding: 0 10px 14px;
}

#Connect_Setting .connect-title{
    font-weight: bold;
    font-size: 18px;
    line-height: 1.4;
    margin: 0 0 10px 0;
    padding-left: 0 !important;
}

#Connect_Setting .connect-subtitle{
    font-weight: bold;
    font-size: 16px;
    line-height: 1.4;
    margin: 12px 0 6px 0;
    padding-left: 0 !important;
}

#Connect_Setting .connect-block{
    width: 100%;
    margin: 0 0 14px 0 !important;
    padding: 6px 0 12px 0;
}

#Connect_Setting .connect-form{
    width: 100%;
    margin: 0 !important;
}

#Connect_Setting .connect-input{
    width: 100% !important;
    max-width: 260px;
    min-width: 0;
    height: 40px !important;
    font-size: 15px !important;
    padding: 8px 10px;
}

#Connect_Setting .connect-current-value{
    margin-top: 8px;
    font-size: 14px;
    line-height: 1.5;
    word-break: break-word;
    overflow-wrap: anywhere;
}

#Connect_Setting .connect-btn-row{
    margin-top: 10px;
}

#Connect_Setting .connect-radio-group{
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    align-items: center;
}

#Connect_Setting .connect-radio-item{
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    margin: 0 !important;
    cursor: pointer;
}

#Connect_Setting .connect-status-line{
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 15px;
    line-height: 1.5;
    flex-wrap: wrap;
}

#Connect_Setting .connect-status-btns{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

#Connect_Setting .connect-status-btns .all-btn,
#Connect_Setting .connect-btn-row .all-btn{
    min-width: 90px;
}

/* 外層共同修正 */
.container-ms{
    margin: 0 auto;
    padding: 0;
    overflow-x: hidden;
    overflow-y: visible;
    position: static;
    width: 100%;
    max-width: 100%;
}

.setting_scrollbar{
    height: calc(100vh - 290px);
    width: 100%;
    max-width: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    display: block;
    box-sizing: border-box;
}

select{
    height: 35px;
    width: 100%;
    max-width: 150px;
    font-size: 16px;
    border: 1px solid #CCCCCC;
    border-radius: 5px;
    color: #000;
    cursor: pointer;
    background: #fff;
}

/* =========================
   Tablet / Mobile
   ========================= */
@media (max-width: 768px){

    #Connect_Setting{
        padding: 0 8px 12px !important;
    }

    /* 關鍵：強制 row / col 全部改單欄 */
    #Connect_Setting .row{
        display: block !important;
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    #Connect_Setting .col,
    #Connect_Setting .col-3,
    #Connect_Setting .col-4,
    #Connect_Setting .col-6{
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin: 0 !important;
    }

    .divMode{
        width: 100%;
        height: auto;
        margin: 4px 0;
    }

    .setting_scrollbar{
        width: 100%;
        max-width: 100%;
        height: auto !important;
        max-height: none !important;
        overflow-y: visible !important;
        overflow-x: hidden !important;
    }

    .setting_force-overflow{
        height: auto !important;
        min-height: 0 !important;
    }

    #Connect_Setting .connect-title{
        font-size: 17px !important;
    }

    #Connect_Setting .connect-subtitle{
        font-size: 15px !important;
        margin-top: 10px !important;
    }

    .t1{
        font-size: 15px !important;
        line-height: 1.45 !important;
        margin: 4px 0 !important;
        display: block !important;
        padding-left: 0 !important;
    }

    .t2{
        font-size: 15px !important;
        line-height: 1.45 !important;
        margin: 4px 0 !important;
    }

    .t3{
        font-size: 15px !important;
        height: 40px !important;
    }

    /* Agent IP input + Save 改直排 */
    #Connect_Setting #agent_ip{
        display: block !important;
        width: 100% !important;
    }

    #Connect_Setting #agent_ip .connect-input{
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
        margin-bottom: 10px !important;
    }

    #Connect_Setting #agent_ip .connect-btn-row{
        display: block !important;
        width: 100% !important;
    }

    #Connect_Setting #agent_ip .all-btn{
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }

    /* Agent Mode 改直排 */
    #Connect_Setting .connect-radio-group{
        display: block !important;
        width: 100% !important;
    }

    #Connect_Setting .connect-radio-item{
        display: flex !important;
        width: 100% !important;
        margin: 0 0 10px 0 !important;
    }

    #Connect_Setting #agent_type_form .connect-btn-row{
        display: block !important;
        width: 100% !important;
    }

    #Connect_Setting #agent_type_form .all-btn{
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }

    /* Status 改直排 */
    #Connect_Setting .connect-status-line{
        display: block !important;
        width: 100% !important;
        margin-bottom: 10px !important;
    }

    #Connect_Setting .connect-status-value{
        display: block !important;
        margin-top: 2px !important;
    }

    #Connect_Setting .connect-status-btns{
        display: block !important;
        width: 100% !important;
        margin-top: 12px !important;
    }

    #Connect_Setting .connect-status-btns .all-btn{
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
        margin: 0 0 10px 0 !important;
    }

    select{
        width: 100% !important;
        max-width: 100% !important;
        height: 40px !important;
        font-size: 15px !important;
    }

    .all-btn{
        font-size: 14px !important;
        min-height: 38px !important;
        height: auto !important;
        padding: 8px 12px !important;
        white-space: nowrap !important;
    }

    #Connect_Setting .connect-current-value{
        font-size: 13px !important;
    }
}

/* =========================
   Small phones
   ========================= */
@media (max-width: 480px){

    #Connect_Setting{
        padding: 0 6px 10px !important;
    }

    #Connect_Setting .connect-title{
        font-size: 16px !important;
    }

    #Connect_Setting .connect-subtitle{
        font-size: 14px !important;
    }

    .t1,
    .t2,
    .t3{
        font-size: 14px !important;
    }

    .all-btn{
        font-size: 13px !important;
        padding: 8px 10px !important;
    }
}
</style>
