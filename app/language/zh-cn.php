<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
$text = array();

// Shared NTCS / iController translations.
$text['Acceleration_Slope'] = '加速度斜率';
$text['Acceleration_text'] = '加速度';
$text['Accumulate_Angle'] = '累计角度';
$text['Align'] = '套用';
$text['Angle'] = '角度';
$text['Angle Time'] = "角度 v.s 時間";
$text['Angle_Calculation'] = '角度计算';
$text['Angle_Window'] = '角度上/下限';
$text['Auto_text'] = '自动';
$text['BS'] = '条码';
$text['Background_Color_text'] = '背景颜色';
$text['Barcode'] = '条码停止';
$text['Blackout Recovery_text']  = '状态储存';
$text['Button_Access_With_Password_text'] = '按钮密码';
$text['CCW'] = '逆时针';
$text['CW'] = '顺时针';
$text['Choose_option'] = '选项';
$text['Circular Archive_text'] = '循环存档';
$text['Clear'] = '清除';
$text['Clear_Seq_Button_text'] = '清除工序键';
$text['Clear_button_text'] = '清除颗数键';
$text['Confirm'] = '确认';
$text['Confirm_button_text'] = '确认键';
$text['Copy'] = '复制';
$text['Copy_Sequence'] = '复制工序';
$text['Custom_text'] = '手动';
$text['DT_Time'] = '颗数间隔时间';
$text['Delay Time'] = '延迟时间';
$text['Delete'] = '删除';
$text['Disable'] = '禁用';
$text['Disable_button_text'] = '禁用键';
$text['Downshift'] = '降速';
$text['Downshift_Angle'] = '降速点角度(度)';
$text['Downshift_Enable'] = '降速启动';
$text['Downshift_Speed'] = '降速点转速';
$text['Downshift_Speed_temp'] = '降檔转速';
$text['Downshift_Torque'] = '降速点扭力';
$text['Downshift_Torque_temp'] = '降檔扭力';
$text['Edit'] = '编辑';
$text['Enable'] = '致能';
$text['Enable_button_text'] = '启用键';
$text['Export Format'] = '导出格式';
$text['Force'] = '力度';
$text['FreeRotate'] = '自由旋转';
$text['GATE_TWICE'] = '二次感应';
$text['Gate Once'] = '一次感应';
$text['Hi-Lo'] = '上下限';
$text['High_Torque'] = '扭力上限';
$text['Interrupt_Alarm'] = '中斷警告';
$text['JOB_COMPLETED'] = '工作完成按确认';
$text['Joint_Offset'] = '扭力补偿值';
$text['Lbf.in'] = '磅.英吋';
$text['Low_Angle'] = '角度下限(度)';
$text['Low_Torque'] = '扭力下限';
$text['Lower_text'] = '下限';
$text['Mac'] = 'MAC地址';
$text['Minus_text'] = '负';
$text['Monitor angle by window']  = '监控角度窗口';
$text['Monitor torque by window'] = '监控扭力窗口';
$text['Monitor_Angle'] = '监控角度';
$text['Monitor_Mode'] = '监控模式';
$text['N.m'] = '牛頓.米';
$text['NG'] = '失敗';
$text['NG-High'] = '超出上限';
$text['NG-Low'] = '低于下限';
$text['NG_Reverse'] = 'NG 退螺丝';
$text['NG_Stop'] = 'NG停止';
$text['NO'] = '否';
$text['New'] = '新增';
$text['Nm'] = '牛顿.米';
$text['OFF_text'] = '关';
$text['OK'] = '完成';
$text['OK-JOB'] = '工作完成';
$text['OK-SEQ'] = '工序完成';
$text['OK-Sequence'] = '工序完成';
$text['OK_All_Stop'] = 'OK All 禁止起子启动';
$text['OK_Sequence'] = '工序完成信号';
$text['OK_Sequence_Stop'] = '工序完成停止';
$text['ON_text'] = '开';
$text['Over_Angle_Stop'] = '超过角度停止';
$text['Plus_text'] = '正';
$text['Pre_Run'] = '寻牙设定';
$text['Pre_Run_Angle'] = '寻牙角度';
$text['Pre_Run_RPM'] = '寻牙转速';
$text['REVERSE_IN'] = '拆螺丝';
$text['RPM Time'] = "转速 v.s 時間";
$text['Reboot'] = '重启';
$text['Reverse'] = '反向';
$text['Reverse_mode'] = '拆螺丝模式';
$text['Run_Down_Speed'] = '转速(圈/分钟)';
$text['SYS_READY'] = '系統就緒';
$text['Second'] = '秒';
$text['Sequence Clear'] = '工序清除';
$text['Skip'] = '跳工序';
$text['Skip_button_text'] = '下一工序鍵';
$text['Start-IN'] = '启动';
$text['Step_angle']  = "步骤角度";
$text['TT_Time'] = '工序完成时间';
$text['Table'] = '表格';
$text['Target Delay Time'] = '目标延迟时间';
$text['Target_Angle'] = '目标角度 (度)';
$text['Target_Time'] = '目标时间';
$text['Target_Torque'] = '目标扭力';
$text['Threshold_Angle'] = '门槛点角度';
$text['Threshold_Torque'] = '门槛点扭力';
$text['Threshold_Type'] = '门槛选项';
$text['Time'] = '时间';
$text['Timeout'] = '超时锁附';
$text['Tool Runing'] = '马达信号';
$text['Tool Trigger'] = '启动信号';
$text['Tool_Max_Torque'] = '工具最大扭力';
$text['Torque'] = '扭力';
$text['Torque Angle'] = "扭力 v.s 角度";
$text['Torque Speed'] = "扭力 & 转速";
$text['Torque Time'] = "扭力 v.s 時間";
$text['Torque_Window'] = '扭力上/下限';
$text['Total_angle'] = "总角度";
$text['UDEFINE'] = '自定义';
$text['Unlimited_text'] = '无限大';
$text['Unscrew(Remote)'] = '反向';
$text['Upper_text'] = '上限';
$text['UserDefine1'] = '自定义1';
$text['UserDefine2'] = '自定义2';
$text['UserDefine3'] = '自定义3';
$text['UserDefine4'] = '自定义4';
$text['UserDefine5'] = '自定义5';
$text['Window'] = '等比例';
$text['YES'] = '是';
$text['add_seq'] = '新增工序';
$text['add_step'] = '新增步骤';
$text['agent_title'] = '代理';
$text['alert_message_1'] = '请选择要删除的事件';
$text['already_bottom'] = '已经在最下方';
$text['already_top'] = '已经在最上方';
$text['angle'] = '角度';
$text['barcode'] = '锁附条码';
$text['barcode_add_fail']     = '条码「%s」新增失败';
$text['barcode_add_success']  = '条码「%s」新增成功';
$text['barcode_delete_fail']      = '删除条码失败，请稍后再试';
$text['barcode_delete_no_select'] = '请先勾选要删除的条码';
$text['barcode_delete_not_found'] = '未找到可删除的条码';
$text['barcode_delete_success']   = '已成功删除 %d 条条码';
$text['barcode_edit_fail']    = '条码「%s」更新失败';
$text['barcode_edit_success'] = '条码「%s」更新成功';
$text['cN.m'] = '牛頓.厘米';
$text['calibration_value'] = '校准值';
$text['cancel'] = '取消';
$text['cannot_open_pack'] = '无法打开 .pack 更新文件';
$text['check_step_target'] = '已有其他的步骤目标 建立了目标扭力';
$text['close'] = '关闭';
$text['column_count'] = '颗数';
$text['column_datetime'] = '时间';
$text['column_no'] = '编号';
$text['column_status'] = '状态';
$text['column_total'] = '总数';
$text['column_unit'] = '单位';
$text['command'] = '命令';
$text['confirm'] = '确认';
$text['controller_info'] = '控制器信息';
$text['controller_sn'] = '控制器序号';
$text['controller_version'] = '控制器版本';
$text['copy_from'] = '复制自';
$text['copy_input'] = '复制输入';
$text['copy_job'] = '复制工作';
$text['copy_output'] = '复制输出';
$text['copy_step'] = '复制步骤';
$text['copy_success'] = '复制完成';
$text['copy_to'] = '复制到';
$text['count_type'] = '锁附颗数计数方式';
$text['cover_confirm_text'] = '工作 id已存在，是否要覆盖?';
$text['cover_text'] = '覆盖';
$text['cpb_version'] = '电源板韌体版本';
$text['csv_file_path'] = '档案路径';
$text['customize'] = '自定义';
$text['data'] = '资料';
$text['data_export'] = '历史数据导出';
$text['data_history'] = '历史资料';
$text['data_history_fail'] = '拧紧失败数据';
$text['data_history_success'] = '全部拧紧数据';
$text['data_filter_all'] = '全部';
$text['data_filter_rev'] = '反转';
$text['data_time'] = '锁附记录时间';
$text['db_version'] = '数据库版本';
$text['del_event'] = '刪除事件';
$text['del_seq'] = '刪除工序';
$text['del_step'] = '刪除步驟';
$text['delay_time'] = '延迟时间';
$text['delay_ttime'] = '延迟时间';
$text['delete_confirm_text'] = '是否要删除 工作 id: ';
$text['delete_seq_confirm_text'] = '是否要刪除 工序 id: ';
$text['delete_step_confirm_text'] = '是否要删除 步骤 id: ';
$text['delete_success'] = '删除完成';
$text['delete_text'] = '删除';
$text['device_id'] = '锁附控制器编号';
$text['device_name'] = '设备名称';
$text['device_sn'] = '锁附控制器序号';
$text['device_type'] = '设备类型';
$text['direction'] = '起子运转方向';
$text['disable'] = '禁用';
$text['down'] = '下';
$text['download_chart'] = '曲线图下载';
$text['downshift_angle'] = '锁附降速点角度';
$text['downshift_speed'] = '锁附降速点转速';
$text['downshift_torque'] = '锁附降速点扭力';
$text['edit_event'] = '编辑事件';
$text['edit_job'] = '编辑工作';
$text['edit_seq'] = '编辑工序';
$text['edit_step'] = '编辑步骤';
$text['enable'] = '启用';
$text['end_date'] = '结束日期';
$text['error_job_name'] = '工作名称输入有误';
$text['error_message'] = '错误信息';
$text['error_seq_name'] = '工序名称输入有误';
$text['extract_failed'] = '解压缩失败';
$text['fail'] = '失败';
$text['fasten_status'] = '锁附结果';
$text['fasten_status_0'] = 'INIT';
$text['fasten_status_1'] = 'READY';
$text['fasten_status_10'] = 'EOC';
$text['fasten_status_11'] = 'C1';
$text['fasten_status_12'] = 'C1_ERR';
$text['fasten_status_13'] = 'C2';
$text['fasten_status_14'] = 'C2_ERR';
$text['fasten_status_15'] = 'C4';
$text['fasten_status_16'] = 'C4_ERR';
$text['fasten_status_17'] = 'C5';
$text['fasten_status_18'] = 'C5_ERR';
$text['fasten_status_19'] = 'BS';
$text['fasten_status_2'] = 'RUNNING';
$text['fasten_status_3'] = 'REVERSE';
$text['fasten_status_4'] = 'OK';
$text['fasten_status_5'] = 'OK-SEQ';
$text['fasten_status_6'] = 'OK-JOB';
$text['fasten_status_7'] = 'NG';
$text['fasten_status_8'] = 'NS';
$text['fasten_status_9'] = 'SETTING';
$text['fasten_time'] = '总锁附时间';
$text['final_fasten_angle'] = '锁附角度';
$text['final_fasten_torque'] = '锁附扭力';
$text['final_tool_current'] = '工具颗数';
$text['final_tool_voltage'] = '工具电压';
$text['gate_confirm'] = '工件复归确认';
$text['gateway'] = '网关IP';
$text['get_job'] = '读取工作';
$text['green_text'] = '绿色';
$text['hard'] = '硬';
$text['hi_angle'] = '锁附角度上限';
$text['hi_torque'] = '锁附扭力上限';
$text['id'] = '系统流水号';
$text['image_version'] = 'Image 版本/系統版本';
$text['info_device_type'] = '锁附控制器类型';
$text['info_fasten_direction'] = '锁附起子转向';
$text['info_json_invalid'] = 'info.json 格式错误或缺少 Match_TCC_Version';
$text['info_tool_sn'] = '锁附起子序号';
$text['info_tool_status'] = '锁附起子状态';
$text['info_tool_type'] = '锁附起子型号';
$text['input_delete_notice'] = '是否要删除';
$text['input_replace_notice'] = '若设定已存在，将会取代原有设定';
$text['invalid_file_extension'] = '上传文件必须为 .pack 格式，目前为：';
$text['job'] = '工作';
$text['job_management'] = '工作管理';
$text['job_ok'] = '完工信号';
$text['job_ok_stop'] = '完工确认停止';
$text['join'] ='结合';
$text['kgf.cm'] = '公斤.公分';
$text['kgf.m'] = '公斤.米';
$text['last_screw_count'] = '锁附颗数';
$text['lo_angle'] = '锁附角度下限';
$text['lo_torque'] = '锁附扭力下限';
$text['login_text'] = '登录';
$text['logout_text'] = '退出';
$text['maintain_counts'] = '本周期拧紧次数';
$text['mask'] = '子网掩码';
$text['max_rpm'] = '最大转速';
$text['max_torque'] = '最大扭力';
$text['missing_info_json'] = '缺少 info.json，无法验证更新文件';
$text['mode'] = '模式';
$text['network_ip'] = '网路 IP';
$text['new_event'] = '建立事件';
$text['new_job'] = '新增工作';
$text['new_seq'] = '新增工序';
$text['new_step'] = '新增步骤';
$text['no_extracted_folder'] = '未找到解压缩文件夹';
$text['normal_step'] = '快速工序目标';
$text['ok_all_alarm_time'] = 'OK All 持续时间';
$text['ok_time'] = "OK one持续时间";
$text['open'] = '打开';
$text['operation_result'] = '锁附结果';
$text['opt'] = '最佳化';
$text['option_no'] = '否';
$text['output_replace_notice'] = '若设定已存在，将会取代原有设定';
$text['over_size_text'] = '檔案大小超過限制：30M';
$text['page'] = '页数';
$text['page_of'] = '之';
$text['password_text'] = '密码';
$text['record_angle'] = '纪录角度值';
$text['refresh'] = '刷新';
$text['return'] = '返回';
$text['rev_count'] = '拆螺丝计数';
$text['rev_tor_threshold'] = '拆螺丝门槛点扭力';
$text['reverse_direction'] = '拆螺丝设定';
$text['reverse_power'] = '拆螺丝扭力';
$text['reverse_rpm'] = '拆螺丝转速';
$text['sample_rate'] = '采样率';
$text['save'] = '储存';
$text['screws'] = '颗数';
$text['select_job'] = '选择工作';
$text['seq_id'] = '工序ID';
$text['seq_management'] = '工序管理';
$text['seq_name'] = '工序名称';
$text['sequence'] = '工序';
$text['sequence_id'] = '锁附工序编号';
$text['sequence_name'] = '锁附工序名称';
$text['setting'] = '设定';
$text['skip'] = '不计';
$text['soft'] = '軟';
$text['start_date'] = '开始日期';
$text['step'] = '步骤';
$text['step0_last_angle']       = '锁附步骤0角度';
$text['step0_last_threadshold'] = '锁附步骤0门槛';
$text['step0_last_times']       = '锁附步骤0时间';
$text['step0_last_torque']      = '锁附步骤0扭力';
$text['step10_last_angle']       = '锁附步骤10角度';
$text['step10_last_threadshold'] = '锁附步骤10门槛';
$text['step10_last_times']       = '锁附步骤10时间';
$text['step10_last_torque']      = '锁附步骤10扭力';
$text['step11_last_angle']       = '锁附步骤11角度';
$text['step11_last_threadshold'] = '锁附步骤11门槛';
$text['step11_last_times']       = '锁附步骤11时间';
$text['step11_last_torque']      = '锁附步骤11扭力';
$text['step12_last_angle']       = '锁附步骤12角度';
$text['step12_last_threadshold'] = '锁附步骤12门槛';
$text['step12_last_times']       = '锁附步骤12时间';
$text['step12_last_torque']      = '锁附步骤12扭力';
$text['step13_last_angle']       = '锁附步骤13角度';
$text['step13_last_threadshold'] = '锁附步骤13门槛';
$text['step13_last_times']       = '锁附步骤13时间';
$text['step13_last_torque']      = '锁附步骤13扭力';
$text['step14_last_angle']       = '锁附步骤14角度';
$text['step14_last_threadshold'] = '锁附步骤14门槛';
$text['step14_last_times']       = '锁附步骤14时间';
$text['step14_last_torque']      = '锁附步骤14扭力';
$text['step15_last_angle']       = '锁附步骤15角度';
$text['step15_last_threadshold'] = '锁附步骤15门槛';
$text['step15_last_times']       = '锁附步骤15时间';
$text['step15_last_torque']      = '锁附步骤15扭力';
$text['step1_last_angle']       = '锁附步骤1角度';
$text['step1_last_threadshold'] = '锁附步骤1门槛';
$text['step1_last_times']       = '锁附步骤1时间';
$text['step1_last_torque']      = '锁附步骤1扭力';
$text['step2_last_angle']       = '锁附步骤2角度';
$text['step2_last_threadshold'] = '锁附步骤2门槛';
$text['step2_last_times']       = '锁附步骤2时间';
$text['step2_last_torque']      = '锁附步骤2扭力';
$text['step3_last_angle']       = '锁附步骤3角度';
$text['step3_last_threadshold'] = '锁附步骤3门槛';
$text['step3_last_times']       = '锁附步骤3时间';
$text['step3_last_torque']      = '锁附步骤3扭力';
$text['step4_last_angle']       = '锁附步骤4角度';
$text['step4_last_threadshold'] = '锁附步骤4门槛';
$text['step4_last_times']       = '锁附步骤4时间';
$text['step4_last_torque']      = '锁附步骤4扭力';
$text['step5_last_angle']       = '锁附步骤5角度';
$text['step5_last_threadshold'] = '锁附步骤5门槛';
$text['step5_last_times']       = '锁附步骤5时间';
$text['step5_last_torque']      = '锁附步骤5扭力';
$text['step6_last_angle']       = '锁附步骤6角度';
$text['step6_last_threadshold'] = '锁附步骤6门槛';
$text['step6_last_times']       = '锁附步骤6时间';
$text['step6_last_torque']      = '锁附步骤6扭力';
$text['step7_last_angle']       = '锁附步骤7角度';
$text['step7_last_threadshold'] = '锁附步骤7门槛';
$text['step7_last_times']       = '锁附步骤7时间';
$text['step7_last_torque']      = '锁附步骤7扭力';
$text['step8_last_angle']       = '锁附步骤8角度';
$text['step8_last_threadshold'] = '锁附步骤8门槛';
$text['step8_last_times']       = '锁附步骤8时间';
$text['step8_last_torque']      = '锁附步骤8扭力';
$text['step9_last_angle']       = '锁附步骤9角度';
$text['step9_last_threadshold'] = '锁附步骤9门槛';
$text['step9_last_times']       = '锁附步骤9时间';
$text['step9_last_torque']      = '锁附步骤9扭力';
$text['step_management'] = '步骤管理';
$text['step_name'] = '步骤名称';
$text['step_target_type'] = '步骤目标';
$text['success'] = '成功';
$text['sw_version'] = '軟體版本';
$text['switch_job'] = '切换工作';
$text['switch_off'] = '关';
$text['switch_on'] = '开';
$text['system_agent_check'] = '检查';
$text['system_agent_client'] = '客户端';
$text['system_agent_frequency'] = '发送频率';
$text['system_agent_ip'] = '代理服务器IP';
$text['system_agent_none'] = '无';
$text['system_agent_server'] = '服务器';
$text['system_agent_start'] = '启动';
$text['system_agent_status'] = '状态';
$text['system_agent_staus2'] = '代理状态';
$text['system_agent_stop'] = '停止';
$text['system_agent_type'] = '代理模式';
$text['system_barcode_del_notice'] = '请选择要删除的条码';
$text['system_barcode_del_notice2'] = '条码删除确认';
$text['system_barcode_from'] = '起始位';
$text['system_barcode_match_from'] = '条码有效起始位';
$text['system_barcode_match_to'] = '条码有效位数';
$text['system_barcode_mode'] = '条码模式';
$text['system_barcode_select_job'] = '选择工作';
$text['system_barcode_select_seq'] = '选择工序';
$text['system_barcode_to'] = '位数';
$text['system_batch'] = '计数模式';
$text['system_blackout'] = '状态储存';
$text['system_buzzer'] = '声响模式';
$text['system_client_status'] = '客户端 状态';
$text['system_confirm_password'] = '确认密码';
$text['system_connect_guest_pwd'] = '访客密码';
$text['system_connect_max_number'] = '目前最大连线人数';
$text['system_connect_number'] = '连线数量';
$text['system_connect_timestamp'] = '最后连线时间';
$text['system_connect_username'] = '登入帐号';
$text['system_db_C2D'] = '控制器 -> iDas';
$text['system_db_D2C'] = 'iDas -> 控制器';
$text['system_db_del_notice'] = '请选择要删除的档案';
$text['system_db_exchange'] = '资料库交换';
$text['system_dec'] = '倒数';
$text['system_delete_database'] = '删除拧紧数据';
$text['system_disc_space'] = '储存容量';
$text['system_diskfull_warning'] = '磁碟已满警告(%)';
$text['system_export_config'] = '导出系统资料';
$text['system_export_import'] = '导出/导入';
$text['system_firmware_reset'] = '预设设定重置';
$text['system_firmware_update'] = '韧体更新';
$text['system_func_permissions'] = '控制命令权限';
$text['system_id'] = '编号';
$text['system_idas_current_version'] = '当前 iDAS 版本';
$text['system_idas_upload_file'] = '上传文件';
$text['system_import_config'] = '导入系统资料';
$text['system_inc'] = '正数';
$text['system_language'] = '语言';
$text['system_name'] = '名称';
$text['system_new_password'] = '新密码';
$text['system_page_block'] = '功能权限管理';
$text['system_password'] = '密码';
$text['system_password_diff'] = '密码不一致';
$text['system_password_require'] = '密码必须至少包含 1 个字母和 1 个数字，且长度至少为 4 个字符。';
$text['system_screwdriver_setting'] = '起子选择';
$text['system_seq_clear'] = '工序清除';
$text['system_server_status'] = '服务器 状态';
$text['system_sn'] = '系统流水号';
$text['system_sw_job_seq'] = '工作任务/工序切换';
$text['system_sync_notice'] = '控制器最后更新时间(UTC)：';
$text['system_sync_warning'] = 'iDas的DB版本小于控制器';
$text['system_sync_warning_login'] = '控制器已登入';
$text['system_sync_warning_title'] = 'iDas更新时间较控制器旧，是否仍要同步';
$text['system_sys_date'] = '系统日期';
$text['system_torque_filter'] = '扭力过滤';
$text['system_unit'] = '单位';
$text['target_angle'] = '目标角度';
$text['target_time'] = '目标时间';
$text['target_torque'] = '目标扭力';
$text['threshold_angle'] = '锁附门槛点角度';
$text['threshold_torque'] = '锁附门槛点扭力';
$text['tighten_repeat'] = '锁附颗数';
$text['tightening_repeat'] = '颗数';
$text['time'] = '时间';
$text['tool'] = '工具';
$text['tool_info'] = '工具信息';
$text['tool_max_speed'] = '最大转速';
$text['tool_max_torque2'] = '最大扭力';
$text['tool_sn']   = '工具序号';
$text['tool_status'] = '工具状态';
$text['tool_type'] = '工具型号';
$text['tools_version'] = '软件版本';
$text['torque'] = '扭力';
$text['total_counts'] = '本周期拧紧次数';
$text['total_fasten_angle'] = '锁附总角度';
$text['total_high_angle'] = '总角度上限';
$text['total_job'] = '总工作数';
$text['total_low_angle'] = '总角度下限';
$text['total_screw_count'] = '总颗数';
$text['unfasten_force'] = '超出范围 1 - 10';
$text['up'] = '上';
$text['update_success'] = '更新成功，已将文件移动至 tccidas 目录';
$text['version_too_low'] = '更新文件版本低于当前版本，无法更新，当前版本：';
$text['yellow_text'] = '黄色';

if (idas_is_icontroller()) {

//login page

//job management
//$text['normal_job_management'] = '快速工作管理';
//$text['advanced_job_management'] = '进阶工作管理';

$text['job_id'] = '工作ID';
$text['job_name'] = '工作名称';

$text['total_seq'] = '工序总数';

$text['total_seq'] = '总工序数';

//job sequence management
//$text['normal_seq_management'] = '快速工作 - 工序管理';
//$text['advanced_seq_management'] = '进阶工作 - 工序管理';

$text['target_type'] = '工序目标';

$text['total_step'] = '总步骤数';

$text['torque_unit'] = '扭力单位';

//normalstep

$text['rpm'] = '转速';

$text['High_Angle'] = '角度上限(度)'; //High Angle( &#870 )

//step

$text['step_id'] = '步骤ID';

$text['total_step'] = '总步骤数';

//operation

$text['final_torque'] = '扭力(牛顿.米)';//TORQUE (Nm)
$text['final_torque'] = '扭力';//TORQUE (Nm)
$text['final_angle'] = '角度(度)';//ANGLE (Deg)
$text['final_result'] = '结果';//RESULT
$text['final_message'] = '信息';//MESSAGE

// Input/Output
$text['input'] = '输入'; //I/O INPUT
$text['output'] = '输出'; //I/O OUTPUT
$text['select'] = '选择'; //Select
$text['event'] = '事件'; //Select
$text['job_select'] = '工作选择'; //Job Select

$text['UDEFINE1'] = '自定义1';
$text['UDEFINE2'] = '自定义2';

$text['UDEFINE1'] = '自定义1';
$text['UDEFINE2'] = '自定义2';

$text['system_barcode'] = '条码停止';

// Data

//Customize 
// zh-cn（简体中文）

$text['job_id'] = '锁附工作编号';
$text['job_name'] = '锁附工作名称';

$text['step_id'] = '锁附步骤编号';
$text['torque_unit'] = '锁附扭力单位';
$text['target_type'] = '锁附目标类型';

$text['fasten_direction'] = '锁附起子转向';
$text['rpm'] = '锁附转速';

//fasten_status

//Tool

//Setting
$text['controller_setting'] = '控制器设定';
$text['system_setting'] = '系统设定';

$text['system_barcode'] = '条码';
$text['system_password_notice'] = 'Barcode';

$text['system_password_notice'] = '更改密码后须重新登入';

$text['system_db_exchange_D2C_t'] = 'Barcode';
$text['system_db_exchange_D2C_m'] = 'Barcode';
$text['system_db_exchange_C2D_t'] = 'Barcode';
$text['system_db_exchange_C2D_m'] = 'Barcode';
$text['system_db_exchange_D2C_t'] = "同步iDas的DB到控制器";
$text['system_db_exchange_D2C_m'] = "同步后目前控制器上的资料将被覆盖，确认是否同步";
$text['system_db_exchange_C2D_t'] = "同步控制器的DB到iDas";
$text['system_db_exchange_C2D_m'] = "同步后目前iDas上的资料将被覆盖，确认是否同步";

//barcode setting
$text['system_barcode_setting'] = '条码设定';

$text['system_barcode_mode_1'] = '条码停止';//BS
$text['system_barcode_mode_2'] = '条码停止(可跳工序)';//BS (free)
$text['system_barcode_mode_3'] = '工作任务/工序切换';//Switch Job / Seq
$text['system_barcode_select_job_m'] = '请选择工作任务';//Switch Job / Seq
$text['system_barcode_select_seq_m'] = '请选择工序';//Switch Job / Seq

$text['system_barcode_notice_1'] = '请选择工作任务';//Please Select Job
$text['system_barcode_notice_2'] = '条码有效起始位超出范围 1-54';//Match From Error
$text['system_barcode_notice_3'] = '条码有效位数超出范围';//Match To Error
$text['system_barcode_notice_4'] = '请扫描条码';//Match To Error

$text['barcode_job_already_exists'] = 'JOB %d 已经有条码，同一个 JOB 只能新增一个条码';

//admin setting
$text['system_connect_setting'] = '连线设定';
$text['network_setting'] = '网络设置';
$text['network_mode'] = '网络模式';
$text['network_dynamic'] = '动态';
$text['network_static'] = '静态';
$text['network_current_ip'] = '网络 IP';
$text['network_static_ip'] = '静态 IP';
$text['network_subnet_mask'] = '子网掩码';
$text['network_gateway_ip'] = '网关 IP';
$text['network_server_port'] = '服务器通讯端口';
$text['network_reboot_note'] = '变更后控制器将自动重新启动';
$text['network_saving'] = '保存中...';
$text['network_save_success_reboot'] = '网络设置已保存，控制器将在 5 秒后自动重新启动。';
$text['network_no_change'] = '网络设置没有变更。';
$text['network_save_failed'] = '网络设置保存失败。';
$text['network_invalid_request'] = '无效的请求。';
$text['network_invalid_mode'] = '网络模式设置错误。';
$text['network_invalid_static_ip'] = '静态 IP 格式错误。';
$text['network_invalid_mask'] = '子网掩码格式错误。';
$text['network_invalid_gateway'] = '网关 IP 格式错误。';
$text['network_invalid_port'] = '服务器通讯端口必须为 1～65535。';
$text['network_new_ip_hint'] = '重新启动后，请使用新的 IP 重新连接 iDAS：';

//commmand

//agent

//main 主畫面image url
$text['img_job'] = '../public/img/home_job_cn.png';
$text['img_job_hover'] = '../public/img/home_job_m_cn.png';
$text['img_io_input'] = '../public/img/home_input_cn.png';
$text['img_io_input_hover'] = '../public/img/home_m_input_cn.png';
$text['img_io_output'] = '../public/img/home_output_cn.png';
$text['img_io_output_hover'] = '../public/img/home_m_output_cn.png';
$text['img_operation'] = '../public/img/home_operation_cn.png';
$text['img_operation_hover'] = '../public/img/home_m_operation_cn.png';
$text['img_data'] = '../public/img/home_data_cn.png';
$text['img_data_hover'] = '../public/img/home_m_data_cn.png';
$text['img_tool'] = '../public/img/home_tool_cn.png';
$text['img_tool_hover'] = '../public/img/home_m_tool_cn.png';
$text['img_setting'] = '../public/img/home_setting_cn.png';
$text['img_setting_hover'] = '../public/img/home_m_setting_cn.png';
$text['img_load'] = '../public/img/home_load_cn.png';
$text['img_load_hover'] = '../public/img/home_load_m_cn.png';
$text['img_save'] = '../public/img/home_save_cn.png';
$text['img_save_hover'] = '../public/img/home_save_m_cn.png';
$text['img_agent'] = '../public/img/home_agent_cn.png';
$text['img_agent_hover'] = '../public/img/home_m_agent_cn.png';
$text['img_remote'] = '../public/img/home_command_cn.png';
$text['img_remote_hover'] = '../public/img/home_m_command_cn.png';

if(ICONMODE  === 5){
	$text['img_job'] = '../public/img/Sumake_icon/home_job_cn.png';
	$text['img_job_hover'] = '../public/img/Sumake_icon/home_m_job_cn.png';
	$text['img_io_input'] = '../public/img/Sumake_icon/home_input_cn.png';
	$text['img_io_input_hover'] = '../public/img/Sumake_icon/home_m_input_cn.png';
	$text['img_io_output'] = '../public/img/Sumake_icon/home_output_cn.png';
	$text['img_io_output_hover'] = '../public/img/Sumake_icon/home_m_output_cn.png';
	$text['img_operation'] = '../public/img/Sumake_icon/home_operation_cn.png';
	$text['img_operation_hover'] = '../public/img/Sumake_icon/home_m_operation_cn.png';
	$text['img_data'] = '../public/img/Sumake_icon/home_data_cn.png';
	$text['img_data_hover'] = '../public/img/Sumake_icon/home_m_data_cn.png';
	$text['img_tool'] = '../public/img/Sumake_icon/home_tool_cn.png';
	$text['img_tool_hover'] = '../public/img/Sumake_icon/home_m_tool_cn.png';
	$text['img_setting'] = '../public/img/Sumake_icon/home_setting_cn.png';
	$text['img_setting_hover'] = '../public/img/Sumake_icon/home_m_setting_cn.png';
	$text['img_load'] = '../public/img/Sumake_icon/home_load_cn.png';
	$text['img_load_hover'] = '../public/img/Sumake_icon/home_m_load_cn.png';
	$text['img_save'] = '../public/img/Sumake_icon/home_save_cn.png';
	$text['img_save_hover'] = '../public/img/Sumake_icon/home_m_save_cn.png';
	$text['img_agent'] = '../public/img/Sumake_icon/home_agent_cn.png';
	$text['img_agent_hover'] = '../public/img/Sumake_icon/home_m_agent_cn.png';
	$text['img_remote'] = '../public/img/Sumake_icon/home_command_cn.png';
	$text['img_remote_hover'] = '../public/img/Sumake_icon/home_m_command_cn.png';

}

//--------------------------------------------------------------------------------------------------------------

$error_message = array();

//------job manage
$error_message['job_id'] = '工作ID超出范围 1 - 50';
$error_message['job_name'] = '工作名称输入有误';
$error_message['unfasten_RPM'] = '超出范围';
$error_message['unfasten_force'] = '超出范围';

$error_message['copy_to_id'] = '工作ID输入有误 1 - 50';
$error_message['copy_to_name'] = '工作名称输入有误';

if(isset($data['tool_info'])){
	$error_message['unfasten_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['unfasten_force'] = '超出范围 1 - 110';
}

if(isset($data['job_type'])){
	if($data['job_type'] == 'normal'){
		$error_message['job_id'] = '工作ID超出范围 1 - 99';
		$error_message['copy_to_id'] = '工作ID超出范围 1 - 99';
	}
	if($data['job_type'] == 'advanced'){
		$error_message['job_id'] = '工作ID超出范围 101 - 170';
		$error_message['copy_to_id'] = '工作ID超出范围 101 - 170';
	}
}

//------sequence manage

$error_message['sequence_name'] = '工序名称输入有误';
$error_message['tightening_repeat'] = '超出范围 1 - 99';
$error_message['timeout'] = '超出范围 0.1 - 60.0';
$error_message['to_seq_name'] = '工序名称输入有误';
$error_message['jon_val'] = "字段不能为空";

//normal step

$error_message['Hi_Torque'] = '超出范围 需大于 '.$text['Target_Torque'].' 且 小于 Tool Max Torque ';
$error_message['Low_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Threshold_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Threshold_Torque_A'] = '超出范围 需小于 '.$text['High_Torque'];
$error_message['Threshold_Angle'] = '超出范围 需小于 '.$text['Target_Angle'];
$error_message['Threshold_Angle_A'] = '超出范围 需小于 '.$text['Target_Angle'];
$error_message['Target_Torque'] = '超出范围 需大于 Tool Min Torque 且 小于 Tool Max Torque';
$error_message['Joint_OffSet'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Run_Down_Speed'] = '超出范围 60 - 1100';
$error_message['Downshift_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Downshift_Torque_A'] = '超出范围 需小于 '.$text['High_Torque'];
$error_message['Downshift_Speed'] = '超出范围 需小于 '.$text['Run_Down_Speed'];
$error_message['High_Angle'] = '超出范围 1 - 30600';
$error_message['Low_Angle'] = '超出范围 ';//范围交给js
$error_message['Pre_Run_RPM'] = '超出范围 60 - tool_maxrpm';
$error_message['Pre_Run_Angle'] = '超出范围 1 - 30600';
$error_message['Target_Angle'] = '超出范围 1 - 30600';
if(isset($data['tool_info'])){
	// $error_message['Hi_Torque'] = '需大于 Target_Torque 且小于'.$data['tool_info']['tool_maxtorque'];
	$error_message['Hi_Torque'] = '超出范围 ';//range交给javascript
	// $error_message['Low_Torque'] = '需小于 Target Torque 或 High Torque';
	$error_message['Low_Torque'] = '超出范围 ';
	$error_message['Target_Torque'] = '超出范围 '.$data['tool_info']['tool_mintorque'].' - '.$data['tool_info']['tool_maxtorque'];
	$error_message['Run_Down_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Pre_Run_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];

	$error_message['Run_Down_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Joint_OffSet'] = '超出范围 ';
	$error_message['Downshift_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - 小于'.$text['Run_Down_Speed'];
}

//advanced step
$error_message['step_name'] = $text['step_name'].'输入有误';
$error_message['RPM'] = '超出范围 60 - 1100';
$error_message['Torque_Window_Add'] = '超出范围 1 - 5';
$error_message['Torque_Window_Subtraction'] = '';
$error_message['Angle_Window_Add'] = '超出范围 1 - 30600';
$error_message['Angle_Window_Subtraction'] = '超出范围 0 - 30599';
$error_message['Delay_Time'] = '超出范围 0.0 - 10.0';
$error_message['ok_time'] = '超出范围 0.0 - 9.9';
$error_message['k_value'] = '超出范围 30 - 300';
$error_message['joint_offset_val'] = '超出范围 -254 - 254';

$error_message['angle_error'] = '最大角度 不可低于 最小角度';
$error_message['torque_error'] = '最大扭力 不可低于 最小扭力';

if(isset($data['tool_info'])){
	$error_message['Pre_Run_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Torque_Window_Add'] = '超出范围 '.$data['tool_info']['tool_mintorque'].' - '.round($data['tool_info']['tool_maxtorque']*1.1,4) ;
	$error_message['Torque_Window_Subtraction'] = '超出范围 '.$text['Hi-Lo'].'需大于等于';
}

//operation error message
$error_message['NO_ERROR'] = '';
$error_message['ERR_CONT_TEMP'] = 'ERR-CONT-TEMP';
$error_message['ERR_MOT_TEMP'] = 'ERR_MOT_TEMP';
$error_message['ERR_MOT_CURR'] = '马达电流过高';
$error_message['ERR_MOT_PEAK_CURR'] = '马达峰值电流过高';
$error_message['ERR_HIGH_TORQUE'] = '扭力超出上限';
$error_message['ERR_DEADLOCK'] = '马达运转异常';
$error_message['ERR_PROC_MINTIME'] = '锁附时间低于下限';
$error_message['ERR_PROC_MAXTIME'] = '超时锁附';
$error_message['ERR_ENCODER'] = '编码器无脉波';
$error_message['ERR_HALL'] = '霍尔传感器无脉波';
$error_message['ERR_BUSVOLT_HIGH'] = '母线电压过高';
$error_message['ERR_BUSVOLT_LOW'] = '母线电压过低';
$error_message['ERR_PROC_NA'] = '设定工序异常';
$error_message['ERR_STEP_NA'] = '设定步骤异常';
$error_message['ERR_DMS_COMM'] = '起子控制器通讯异常';
$error_message['ERR_FLASH'] = '控制器Flash验证码错误';
$error_message['ERR_FRAM'] = '控制器Fram验证码错误';
$error_message['ERR_HIGH_ANGLE'] = '角度超出上限';
$error_message['ERR_PROTECT_CIRCUIT'] = '硬体保护异常';
$error_message['ERR_SWITCH_CONFIG'] = '启动开关设置错误';
$error_message['ERR_STEP_NOT_REC'] = '步骤数设置不一致';
$error_message['ERR_TMD_FRAM'] = '起子基板FRAM异常';
$error_message['ERR_LOW_TORQUE'] = '扭力低于下限';
$error_message['ERR_LOW_ANGLE'] = '角度低于下限';
$error_message['ERR_PROC_NOT_FINISH'] = '操作未完成';
$error_message['SEQ_COMPLETED'] = '工序完成按确认';
$error_message['JOB_COMPLETED'] = '工作完成按确认';
$error_message['WORKPIECE_RECOVERY'] = '工件复归按确认';
$error_message['target_torque_empty'] = '目标扭力为必填栏位';

$error_message['ERR_0'] = '';
$error_message['ERR_1'] =  'NO-ERR';
$error_message['ERR_2'] =  '温度异常';
$error_message['ERR_3'] =  '堵转保护';
$error_message['ERR_4'] =  '电流保护';
$error_message['ERR_5'] =  '与TMD通讯异常';
$error_message['ERR_6'] =  '传感器异常';
$error_message['ERR_7'] =  '超时失败';
$error_message['ERR_8'] =  '超时失败';
$error_message['ERR_9'] =  '中断警报';
$error_message['ERR_10'] = '步骤角度上限错误';
$error_message['ERR_11'] = '步骤角度下限错误';
$error_message['ERR_12'] = '扭力上限错误';
$error_message['ERR_13'] = '扭力下限错误';
$error_message['ERR_14'] = '总角度上限错误';
$error_message['ERR_15'] = '总角度下限错误';

//$error_message['ERR_14'] = '设定步骤异常';
//$error_message['ERR_15'] = '起子控制器通讯异常';
$error_message['ERR_16'] = '控制器Flash验证码错误';
$error_message['ERR_17'] = '控制器Fram验证码错误';
$error_message['ERR_18'] = '角度超出上限';
$error_message['ERR_19'] = '硬体保护异常';
$error_message['ERR_20'] = '启动开关设置错误';
$error_message['ERR_21'] = '步骤数设置不一致';
$error_message['ERR_22'] = '起子基板FRAM异常';
$error_message['ERR_23'] = '扭力低于下限';
$error_message['ERR_24'] = '角度低于下限';
$error_message['ERR_25'] = '操作未完成';
$error_message['ERR_26'] = '工序完成按确认';
$error_message['ERR_27'] = '工作完成按确认';
$error_message['ERR_28'] = '工件复归按确认';
$error_message['ERR_29'] = '目标扭力为必填栏位';
$error_message['ERR_30'] = '控制器尚未登出';

$text['network_save_success_auto_reboot'] = '网络设置已保存，控制器将在 5 秒后自动重新启动。';
$text['network_save_success_reboot_failed'] = '网络设置已保存，但无法安排自动重新启动，请手动重新启动控制器。';
} else {

//login page

//job management
//$text['normal_job_management'] = '快速工作管理';
//$text['advanced_job_management'] = '进阶工作管理';

$text['job_id'] = '工作ID';
$text['job_name'] = '工作名称';

$text['total_seq'] = '工序总数';

$text['total_seq'] = '总工序数';

//job sequence management
//$text['normal_seq_management'] = '快速工作 - 工序管理';
//$text['advanced_seq_management'] = '进阶工作 - 工序管理';

$text['target_type'] = '工序目标';

$text['total_step'] = '总步骤数';

$text['torque_unit'] = '扭力单位';

//normalstep

$text['rpm'] = '转速';

$text['High_Angle'] = '角度上限(度)'; //High Angle( &#870 )

//step

$text['step_id'] = '步骤ID';

$text['total_step'] = '总步骤数';

//operation

$text['final_torque'] = '扭力(牛顿.米)';//TORQUE (Nm)
$text['final_torque'] = '扭力';//TORQUE (Nm)
$text['final_angle'] = '角度(度)';//ANGLE (Deg)
$text['final_result'] = '结果';//RESULT
$text['final_message'] = '信息';//MESSAGE

// Input/Output
$text['input'] = '输入'; //I/O INPUT
$text['output'] = '输出'; //I/O OUTPUT
$text['select'] = '选择'; //Select
$text['event'] = '事件'; //Select
$text['job_select'] = '工作选择'; //Job Select

$text['UDEFINE1'] = '自定义1';
$text['UDEFINE2'] = '自定义2';

$text['UDEFINE1'] = '自定义1';
$text['UDEFINE2'] = '自定义2';

$text['system_barcode'] = '条码停止';

// Data

$text['tor_line_chart'] = '扭力折线图';
$text['latest_25_fastening_data'] = '最新 25 笔锁附资料';
$text['auto_refresh_25_records'] = '自动更新 / 25 笔资料';
$text['user_id'] = '使用者 ID';
$text['job_cycle_time'] = '工作节拍时间';

//Customize 
// zh-cn（简体中文）

$text['job_id'] = '锁附工作编号';
$text['job_name'] = '锁附工作名称';

$text['step_id'] = '锁附步骤编号';
$text['torque_unit'] = '锁附扭力单位';
$text['target_type'] = '锁附目标类型';

$text['rpm'] = '锁附转速';

//fasten_status

//Tool

//Setting
$text['controller_setting'] = '控制器';
$text['system_setting'] = '系统';

$text['system_barcode'] = '条码';
$text['system_password_notice'] = 'Barcode';

$text['system_password_notice'] = '更改密码后须重新登入';

$text['system_db_exchange_D2C_t'] = 'Barcode';
$text['system_db_exchange_D2C_m'] = 'Barcode';
$text['system_db_exchange_C2D_t'] = 'Barcode';
$text['system_db_exchange_C2D_m'] = 'Barcode';
$text['system_db_exchange_D2C_t'] = "同步iDas的DB到控制器";
$text['system_db_exchange_D2C_m'] = "同步后目前控制器上的资料将被覆盖，确认是否同步";
$text['system_db_exchange_C2D_t'] = "同步控制器的DB到iDas";
$text['system_db_exchange_C2D_m'] = "同步后目前iDas上的资料将被覆盖，确认是否同步";

//barcode setting
$text['system_barcode_setting'] = '条码';

$text['system_barcode_mode_1'] = '条码停止';//BS
$text['system_barcode_mode_2'] = '条码停止(可跳工序)';//BS (free)
$text['system_barcode_mode_3'] = '工作任务/工序切换';//Switch Job / Seq
$text['system_barcode_select_job_m'] = '请选择工作任务';//Switch Job / Seq
$text['system_barcode_select_seq_m'] = '请选择工序';//Switch Job / Seq

$text['system_barcode_notice_1'] = '请选择工作任务';//Please Select Job
$text['system_barcode_notice_2'] = '条码有效起始位超出范围 1-54';//Match From Error
$text['system_barcode_notice_3'] = '条码有效位数超出范围';//Match To Error
$text['system_barcode_notice_4'] = '请扫描条码';//Match To Error

$text['barcode_job_conflict'] = '工作（Job）%d 已经有条码，请先编辑或删除原本的条码。';
$text['barcode_value_conflict'] = '条码“%s”已经指派给其他工作（Job）。';

//admin setting
$text['system_connect_setting'] = '连线';

//commmand

//agent

//main 主畫面image url
$text['img_job'] = '../public/img/home_job_cn.png';
$text['img_job_hover'] = '../public/img/home_job_m_cn.png';
$text['img_io_input'] = '../public/img/home_input_cn.png';
$text['img_io_input_hover'] = '../public/img/home_m_input_cn.png';
$text['img_io_output'] = '../public/img/home_output_cn.png';
$text['img_io_output_hover'] = '../public/img/home_m_output_cn.png';
$text['img_operation'] = '../public/img/home_operation_cn.png';
$text['img_operation_hover'] = '../public/img/home_m_operation_cn.png';
$text['img_data'] = '../public/img/home_data_cn.png';
$text['img_data_hover'] = '../public/img/home_m_data_cn.png';
$text['img_tool'] = '../public/img/home_tool_cn.png';
$text['img_tool_hover'] = '../public/img/home_m_tool_cn.png';
$text['img_setting'] = '../public/img/home_setting_cn.png';
$text['img_setting_hover'] = '../public/img/home_m_setting_cn.png';
$text['img_load'] = '../public/img/home_load_cn.png';
$text['img_load_hover'] = '../public/img/home_load_m_cn.png';
$text['img_save'] = '../public/img/home_save_cn.png';
$text['img_save_hover'] = '../public/img/home_save_m_cn.png';
$text['img_agent'] = '../public/img/home_agent_cn.png';
$text['img_agent_hover'] = '../public/img/home_m_agent_cn.png';
$text['img_remote'] = '../public/img/home_command_cn.png';
$text['img_remote_hover'] = '../public/img/home_m_command_cn.png';

if(ICONMODE  === 5){
	$text['img_job'] = '../public/img/Sumake_icon/home_job_cn.png';
	$text['img_job_hover'] = '../public/img/Sumake_icon/home_m_job_cn.png';
	$text['img_io_input'] = '../public/img/Sumake_icon/home_input_cn.png';
	$text['img_io_input_hover'] = '../public/img/Sumake_icon/home_m_input_cn.png';
	$text['img_io_output'] = '../public/img/Sumake_icon/home_output_cn.png';
	$text['img_io_output_hover'] = '../public/img/Sumake_icon/home_m_output_cn.png';
	$text['img_operation'] = '../public/img/Sumake_icon/home_operation_cn.png';
	$text['img_operation_hover'] = '../public/img/Sumake_icon/home_m_operation_cn.png';
	$text['img_data'] = '../public/img/Sumake_icon/home_data_cn.png';
	$text['img_data_hover'] = '../public/img/Sumake_icon/home_m_data_cn.png';
	$text['img_tool'] = '../public/img/Sumake_icon/home_tool_cn.png';
	$text['img_tool_hover'] = '../public/img/Sumake_icon/home_m_tool_cn.png';
	$text['img_setting'] = '../public/img/Sumake_icon/home_setting_cn.png';
	$text['img_setting_hover'] = '../public/img/Sumake_icon/home_m_setting_cn.png';
	$text['img_load'] = '../public/img/Sumake_icon/home_load_cn.png';
	$text['img_load_hover'] = '../public/img/Sumake_icon/home_m_load_cn.png';
	$text['img_save'] = '../public/img/Sumake_icon/home_save_cn.png';
	$text['img_save_hover'] = '../public/img/Sumake_icon/home_m_save_cn.png';
	$text['img_agent'] = '../public/img/Sumake_icon/home_agent_cn.png';
	$text['img_agent_hover'] = '../public/img/Sumake_icon/home_m_agent_cn.png';
	$text['img_remote'] = '../public/img/Sumake_icon/home_command_cn.png';
	$text['img_remote_hover'] = '../public/img/Sumake_icon/home_m_command_cn.png';

}

//--------------------------------------------------------------------------------------------------------------

$error_message = array();

//------job manage
$error_message['job_id'] = '工作ID超出范围 1 - 50';
$error_message['job_name'] = '工作名称输入有误';
$error_message['unfasten_RPM'] = '超出范围';
$error_message['unfasten_force'] = '超出范围';

$error_message['copy_to_id'] = '工作ID输入有误 1 - 50';
$error_message['copy_to_name'] = '工作名称输入有误';

if(isset($data['tool_info'])){
	$error_message['unfasten_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['unfasten_force'] = '超出范围 1 - 110';
}

if(isset($data['job_type'])){
	if($data['job_type'] == 'normal'){
		$error_message['job_id'] = '工作ID超出范围 1 - 99';
		$error_message['copy_to_id'] = '工作ID超出范围 1 - 99';
	}
	if($data['job_type'] == 'advanced'){
		$error_message['job_id'] = '工作ID超出范围 101 - 170';
		$error_message['copy_to_id'] = '工作ID超出范围 101 - 170';
	}
}

//------sequence manage

$error_message['sequence_name'] = '工序名称输入有误';
$error_message['tightening_repeat'] = '超出范围 1 - 99';
$error_message['timeout'] = '超出范围 0.1 - 60.0';
$error_message['to_seq_name'] = '工序名称输入有误';
$error_message['jon_val'] = "字段不能为空";

//normal step

$error_message['Hi_Torque'] = '超出范围 需大于 '.$text['Target_Torque'].' 且 小于 Tool Max Torque ';
$error_message['Low_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Threshold_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Threshold_Torque_A'] = '超出范围 需小于 '.$text['High_Torque'];
$error_message['Threshold_Angle'] = '超出范围 需小于 '.$text['Target_Angle'];
$error_message['Threshold_Angle_A'] = '超出范围 需小于 '.$text['Target_Angle'];
$error_message['Target_Torque'] = '超出范围 需大于 Tool Min Torque 且 小于 Tool Max Torque';
$error_message['Joint_OffSet'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Run_Down_Speed'] = '超出范围 60 - 1100';
$error_message['Downshift_Torque'] = '超出范围 需小于 '.$text['Target_Torque'];
$error_message['Downshift_Torque_A'] = '超出范围 需小于 '.$text['High_Torque'];
$error_message['Downshift_Speed'] = '超出范围 需小于 '.$text['Run_Down_Speed'];
$error_message['High_Angle'] = '超出范围 1 - 30600';
$error_message['Low_Angle'] = '超出范围 ';//范围交给js
$error_message['Pre_Run_RPM'] = '超出范围 60 - tool_maxrpm';
$error_message['Pre_Run_Angle'] = '超出范围 1 - 30600';
$error_message['Target_Angle'] = '超出范围 1 - 30600';
if(isset($data['tool_info'])){
	// $error_message['Hi_Torque'] = '需大于 Target_Torque 且小于'.$data['tool_info']['tool_maxtorque'];
	$error_message['Hi_Torque'] = '超出范围 ';//range交给javascript
	// $error_message['Low_Torque'] = '需小于 Target Torque 或 High Torque';
	$error_message['Low_Torque'] = '超出范围 ';
	$error_message['Target_Torque'] = '超出范围 '.$data['tool_info']['tool_mintorque'].' - '.$data['tool_info']['tool_maxtorque'];
	$error_message['Run_Down_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Pre_Run_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];

	$error_message['Run_Down_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Joint_OffSet'] = '超出范围 ';
	$error_message['Downshift_Speed'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - 小于'.$text['Run_Down_Speed'];
}

//advanced step
$error_message['step_name'] = $text['step_name'].'输入有误';
$error_message['RPM'] = '超出范围 60 - 1100';
$error_message['Torque_Window_Add'] = '超出范围 1 - 5';
$error_message['Torque_Window_Subtraction'] = '';
$error_message['Angle_Window_Add'] = '超出范围 1 - 30600';
$error_message['Angle_Window_Subtraction'] = '超出范围 0 - 30599';
$error_message['Delay_Time'] = '超出范围 0.0 - 10.0';
$error_message['ok_time'] = '超出范围 0.0 - 9.9';
$error_message['k_value'] = '超出范围 30 - 300';
$error_message['joint_offset_val'] = '超出范围 -254 - 254';

$error_message['angle_error'] = '最大角度 不可低于 最小角度';
$error_message['torque_error'] = '最大扭力 不可低于 最小扭力';

if(isset($data['tool_info'])){
	$error_message['Pre_Run_RPM'] = '超出范围 '.$data['tool_info']['tool_minrpm'].' - '.$data['tool_info']['tool_maxrpm'];
	$error_message['Torque_Window_Add'] = '超出范围 '.$data['tool_info']['tool_mintorque'].' - '.round($data['tool_info']['tool_maxtorque']*1.1,4) ;
	$error_message['Torque_Window_Subtraction'] = '超出范围 '.$text['Hi-Lo'].'需大于等于';
}

//operation error message
$error_message['NO_ERROR'] = '';
$error_message['ERR_CONT_TEMP'] = 'ERR-CONT-TEMP';
$error_message['ERR_MOT_TEMP'] = 'ERR_MOT_TEMP';
$error_message['ERR_MOT_CURR'] = '马达电流过高';
$error_message['ERR_MOT_PEAK_CURR'] = '马达峰值电流过高';
$error_message['ERR_HIGH_TORQUE'] = '扭力超出上限';
$error_message['ERR_DEADLOCK'] = '马达运转异常';
$error_message['ERR_PROC_MINTIME'] = '锁附时间低于下限';
$error_message['ERR_PROC_MAXTIME'] = '超时锁附';
$error_message['ERR_ENCODER'] = '编码器无脉波';
$error_message['ERR_HALL'] = '霍尔传感器无脉波';
$error_message['ERR_BUSVOLT_HIGH'] = '母线电压过高';
$error_message['ERR_BUSVOLT_LOW'] = '母线电压过低';
$error_message['ERR_PROC_NA'] = '设定工序异常';
$error_message['ERR_STEP_NA'] = '设定步骤异常';
$error_message['ERR_DMS_COMM'] = '起子控制器通讯异常';
$error_message['ERR_FLASH'] = '控制器Flash验证码错误';
$error_message['ERR_FRAM'] = '控制器Fram验证码错误';
$error_message['ERR_HIGH_ANGLE'] = '角度超出上限';
$error_message['ERR_PROTECT_CIRCUIT'] = '硬体保护异常';
$error_message['ERR_SWITCH_CONFIG'] = '启动开关设置错误';
$error_message['ERR_STEP_NOT_REC'] = '步骤数设置不一致';
$error_message['ERR_TMD_FRAM'] = '起子基板FRAM异常';
$error_message['ERR_LOW_TORQUE'] = '扭力低于下限';
$error_message['ERR_LOW_ANGLE'] = '角度低于下限';
$error_message['ERR_PROC_NOT_FINISH'] = '操作未完成';
$error_message['SEQ_COMPLETED'] = '工序完成按确认';
$error_message['JOB_COMPLETED'] = '工作完成按确认';
$error_message['WORKPIECE_RECOVERY'] = '工件复归按确认';
$error_message['target_torque_empty'] = '目标扭力为必填栏位';

$error_message['ERR_0'] = '';
$error_message['ERR_1'] =  'NO-ERR';
$error_message['ERR_2'] =  '温度异常';
$error_message['ERR_3'] =  '堵转保护';
$error_message['ERR_4'] =  '电流保护';
$error_message['ERR_5'] =  '与TMD通讯异常';
$error_message['ERR_6'] =  '传感器异常';
$error_message['ERR_7'] =  '超时失败';
$error_message['ERR_8'] =  '超时失败';
$error_message['ERR_9'] =  '中断警报';
$error_message['ERR_10'] = '步骤角度上限错误';
$error_message['ERR_11'] = '步骤角度下限错误';
$error_message['ERR_12'] = '扭力上限错误';
$error_message['ERR_13'] = '扭力下限错误';
$error_message['ERR_14'] = '总角度上限错误';
$error_message['ERR_15'] = '总角度下限错误';

//$error_message['ERR_14'] = '设定步骤异常';
//$error_message['ERR_15'] = '起子控制器通讯异常';
$error_message['ERR_16'] = '控制器Flash验证码错误';
$error_message['ERR_17'] = '控制器Fram验证码错误';
$error_message['ERR_18'] = '角度超出上限';
$error_message['ERR_19'] = '硬体保护异常';
$error_message['ERR_20'] = '启动开关设置错误';
$error_message['ERR_21'] = '步骤数设置不一致';
$error_message['ERR_22'] = '起子基板FRAM异常';
$error_message['ERR_23'] = '扭力低于下限';
$error_message['ERR_24'] = '角度低于下限';
$error_message['ERR_25'] = '操作未完成';
$error_message['ERR_26'] = '工序完成按确认';
$error_message['ERR_27'] = '工作完成按确认';
$error_message['ERR_28'] = '工件复归按确认';
$error_message['ERR_29'] = '目标扭力为必填栏位';
$error_message['ERR_30'] = '控制器尚未登出';

// Account QRCode force patch i18n
$text['account_qrcode'] = 'QRCode';
$text['account_qrcode_download'] = '下载';

$text['account_no'] = '编号';

$text['account_user_name'] = '使用者名称';

$text['account_select_tab'] = '请选择账号分页';

$text['account_username'] = '使用者名称';

$text['account_password'] = '密码';

$text['account_confirm_password'] = '确认密码';

$text['account_import'] = '导入';

$text['account_export'] = '导出';

$text['account_upload'] = '上传';

$text['account_new_title'] = '新增账号';

$text['account_edit_title'] = '编辑账号';

$text['account_show_password'] = '显示密码';

$text['account_hide_password'] = '隐藏密码';

$text['audit_button'] = '操作纪录';

$text['audit_realtime_monitor'] = '即时监控';

$text['audit_running'] = '监控中';

$text['audit_paused'] = '已暂停';

$text['audit_pause'] = '暂停';

$text['audit_start'] = '开始';

$text['audit_standby'] = '待命';

$text['audit_error'] = '错误';

$text['audit_monitor'] = '监控来源';

$text['audit_updated'] = '更新时间';

$text['audit_records'] = '笔数';

$text['audit_no'] = '编号';

$text['audit_time'] = '时间';

$text['audit_user'] = '使用者';

$text['audit_module'] = '模块';

$text['audit_action'] = '动作';

$text['audit_target'] = '目标';

$text['audit_status'] = '状态';

$text['audit_message'] = '信息';

$text['audit_select_tab'] = '请选择操作纪录分页';

$text['audit_no_data'] = '无资料';

$text['audit_loading'] = '载入中...';

$text['account_text'] = '账号';
}
