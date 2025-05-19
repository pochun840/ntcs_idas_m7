function exportData() {
    var radioButtons = document.querySelectorAll('input[name="export-option"]');
    var isChecked = false;
    var expert_val = '';

    for (var i = 0; i < radioButtons.length; i++) {
        if (radioButtons[i].checked) {
            isChecked = true;
            expert_val = radioButtons[i].value;
            break;
        }
    }

    if (!isChecked) {
        alertify.alert("請選擇一個選項");
        return;
    }

    var start_date = document.getElementById('start_date').value;
    var end_date = document.getElementById('end_date').value;

    var valid_flag = true;

    if (start_date === '' || end_date === '') {
        alertify.alert("請選擇開始日期與結束日期");
        valid_flag = false;
    }

    if (start_date > end_date) {
        alertify.alert("開始日期必須小於結束日期");
        valid_flag = false;
    }

    if (valid_flag) {
        $.ajax({
            url: "?url=Data/exportData",
            method: "POST",
            data: {
                start_date: start_date,
                end_date: end_date,
                expert_val: expert_val
            },
            xhrFields: {
                responseType: 'blob' 
            },
            success: function(response, status, xhr) {
                // 如果 response 返回错误信息
                if (response.error) {
                    alertify.alert(response.error); // 使用 alertify 弹窗显示错误
                    return;
                }

                var contentType = xhr.getResponseHeader('Content-Type');
                var filename = expert_val === "1" ? 'exported_data.zip' : 'data.csv';  
                var blob = new Blob([response], { type: contentType });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.setAttribute('download', filename);

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            error: function(xhr, status, error) {
                console.error("AJAX 請求失敗:", status, error);
                alertify.alert("發生錯誤，無法導出資料");
            }
        });
    }
}


function OpenButton(ButtonMode){

    if (ButtonMode == "History")
    {
        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:block");
    }
    else if (ButtonMode == "Exportdata")
    {
        document.getElementById('ExportdataDisplay').setAttribute("style","display:block;");
        document.getElementById('HistoryDisplay').setAttribute("style", "display:none");
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:none");
    }
    else
    {
        alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}