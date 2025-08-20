Q: "工作/步驟管理/編輯", 使用起子 (302F, 2.4Nm), 下列欄位設定異常
vv1.(欄位限制小數位可輸入個數＋四捨五入), 剩下 "延遲時間 (秒)" & "降速點扭力(牛頓.米)" 未修正, 且降速點扭力該欄位目前無法輸入小數點
vv 2.目標扭力(牛頓.米), 目前輸入過小無警示但無法儲存, 過大時警示卻是出現在 HQ 欄位後方

vv3.扭力下限(牛頓.米), LQ = TQ 警示 (扭力下限要小於扭力上限), LQ > HQ 警示 (必須小於 StepTorque), 上述警示內容異常皆需要修正
4.vv角度下限(度), 當 LA > HA 警示 (Must be less than StepHiAngle), 簡/繁中時需中文顯示


vv 5.目標扭力 (監控扭力視窗上/下限) / 目標角度 (監控角度視窗上/下限) 與控制器同步預設值一律填入 "30", 防止目標切換時相互影響, 造成有機會清空而無法儲存


6. vv 目標扭力時, 門檻扭力 > TQ 警示 (Must be less than StepTorque), 簡/繁中時需中文顯示
                       降速扭力 > TQ 警示 (Must be less than StepTorque)



   目標角度時, 門檻扭力 > HQ 警示 (必須小於 StepHiTorque), 修正與降速警示相同
                       降速扭力 > HQ 警示 (必須小於扭力上限)


7.目標角度時
VV扭力下限 (牛頓.米) > HQ 警示 (Must be less than StepHiTorque), 簡/繁中時需中文顯示  
VV角度下限 (度) > HA 警示 (Must be less than StepAngle), 簡/繁中時需中文顯示


交叉驗證 
驗證 id="StepOption" == 2  時
如果  id="StepTorque  等於  id="StepLoTorque" 要顯示 目標扭力超出範圍

 function delete_barcode_item() {
        const del_barcode_id = [];
        const checkboxes = document.querySelectorAll('input[name="barcode_check"]:checked');

        checkboxes.forEach(checkbox => del_barcode_id.push(checkbox.value));

        if (del_barcode_id.length === 0) return;

        document.getElementById('spinner').style.display = 'block';

        alert(del_barcode_id);
        

        $.ajax({
            url: "?url=Settings/delete_barcodes",
            method: "POST",
            data: { del_barcode_id: del_barcode_id },
            dataType: 'json', // ✅ 強制回傳格式為 JSON，避免 JSON.parse 錯誤
            success: function(response) {
                const { res_type, res_msg } = response;

                setTimeout(function () {
                    document.getElementById('spinner').style.display = 'none';

                    alertify.alert(res_type, res_msg, function () {
                        sessionStorage.setItem('Barcode_Setting', 'block');
                        sessionStorage.setItem('Controller_Setting', 'none');
                        //history.go(0); // 頁面重載
                    });

                    setTimeout(function () {
                        alertify.closeAll();

                        // ✅ 刷新條碼列表區塊
                        $.ajax({
                            url: "?url=Settings/show_Barcodes",
                            method: "GET",
                            success: function (html) {
                                $('#total_barcodes').html(html);
                            },
                            error: function (xhr, status, error) {
                                console.error("刷新條碼失敗:", error);
                            }
                        });
                    }, 3000);
                }, 1000);
            },
            error: function(xhr, status, error) {
                document.getElementById('spinner').style.display = 'none';
                console.error("刪除時發生錯誤:", error);
                alertify.alert("Error", "無法刪除條碼，請稍後再試。");
            }
        });
    }