var G_InitialCntFlg = 0;
function init(){
    if (G_InitialCntFlg == 0){
        G_ButtonMode = 1;
        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
    }

    G_InitialCntFlg ++;

}

function OpenButton(ButtonMode){

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
    }else if(ButtonMode == "Customize"){
        const url = "?url=Customize";
        const win = window.open(url, "_blank", "noopener,noreferrer");
        if (!win) {
            // 若被彈窗阻擋，改用隱形 <a> 觸發
            const a = document.createElement("a");
            a.href = url;
            a.target = "_blank";
            a.rel = "noopener noreferrer";
            document.body.appendChild(a);
            a.click();
            a.remove();
        }
        return; // 避免走到後面的 alert
    }else{
        alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}






