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

/*
function DataMode(select)
{

    if (select.value == "all")
    {
        document.getElementById('fasten_log_all').setAttribute("style", "display:block");
        document.getElementById('fasten_log').setAttribute("style", "display:none");
        document.getElementById('error_fasten_log').setAttribute("style","display:none");
    }
    if (select.value == "ok")
    {
        document.getElementById('fasten_log_all').setAttribute("style", "display:none");
        document.getElementById('fasten_log').setAttribute("style", "display:block");
        document.getElementById('error_fasten_log').setAttribute("style","display:none");
    }
    if (select.value == "ng")
    {
        document.getElementById('fasten_log_all').setAttribute("style", "display:none");
        document.getElementById('fasten_log').setAttribute("style", "display:none");
        document.getElementById('error_fasten_log').setAttribute("style","display:block");
    }
}
*/