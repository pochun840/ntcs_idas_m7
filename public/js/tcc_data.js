var G_InitialCntFlg = 0;

function init()
{
    if (G_InitialCntFlg == 0)
    {
        G_ButtonMode = 1;

        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
    }

    G_InitialCntFlg ++;

}

function OpenButton(ButtonMode)
{

    if (ButtonMode == "History"){
        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:block");


    }else if (ButtonMode == "Exportdata"){
        document.getElementById('ExportdataDisplay').setAttribute("style","display:block;");
        document.getElementById('HistoryDisplay').setAttribute("style", "display:none");
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:none");
    }else if(ButtonMode == "Export_Data_download"){
        downloadCSVZip();
    }else{
        alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}


function downloadCSVZip() {
  const url = "?url=Data/download_file"; 
  const $btn = document.getElementById('bnt2') || null;
  if ($btn) $btn.disabled = true;

  $.ajax({
    url: url,
    method: "GET",
    xhrFields: { responseType: 'blob' }, // 重要：接收二進位
    success: function (data, textStatus, jqXHR) {
      try {
        const contentType = jqXHR.getResponseHeader('Content-Type') || '';

        // 如果伺服器回傳 JSON (表示錯誤)
        if (contentType.indexOf('application/json') !== -1) {
          const reader = new FileReader();
          reader.onload = function () {
            try {
              const json = JSON.parse(reader.result || '{}');
              const msg = (json && json.res_msg) ? json.res_msg : '下載失敗（未知錯誤）。';
              //alertify.alert('錯誤', msg);
            } catch (e) {
              //alertify.alert('錯誤', '下載失敗：伺服器回傳非預期 JSON。');
            } finally {
              if ($btn) $btn.disabled = false;
            }
          };
          reader.readAsText(data);
          return;
        }

        // 取得檔名
        let filename = 'csv_bundle.zip';
        const cd = jqXHR.getResponseHeader('Content-Disposition') || '';
        const match = cd.match(/filename\*?=(?:UTF-8'')?("?)([^";]+)\1/i);
        if (match && match[2]) filename = decodeURIComponent(match[2]);

        // 建立下載連結
        const blobUrl = URL.createObjectURL(data);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        URL.revokeObjectURL(blobUrl);
        a.remove();

        //alertify.success('檔案下載完成：' + filename);
      } finally {
        if ($btn) $btn.disabled = false;
      }
    },
    error: function (xhr) {
      const reader = new FileReader();
      reader.onload = function () {
        try {
          const json = JSON.parse(reader.result || '{}');
          const msg = (json && json.res_msg) ? json.res_msg : '下載發生錯誤。';
          alertify.alert('錯誤', msg);
        } catch (_) {
          alertify.alert('錯誤', '下載發生錯誤。');
        } finally {
          if ($btn) $btn.disabled = false;
        }
      };
      reader.readAsText(xhr.response);
    }
  });
}



