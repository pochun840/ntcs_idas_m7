/*
 * Single-codebase JavaScript (no eval)
 * /home/kls/upgrade/icontroller = 1 -> i-controller
 * otherwise -> NTCS
 */
if (window.IS_ICONTROLLER) {
window.addEventListener('storage', function (event) {
    if (event.key === 'idas_force_logout') {
        try {
            localStorage.clear();
            sessionStorage.clear();

            document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
        } catch (e) {
            console.warn('storage logout failed:', e);
        }

        window.location.replace('/idas/public/?url=In');
    }
});


function hideElementById(elementId) {
    var element = document.getElementById(elementId);
    if (element){
        element.style.display = 'none';
    }
}

//讀取localstorge
function readFromLocalStorage(key) {
    return localStorage.getItem(key);
}

function setRadioButtonValue(radioButtons, value) {
    for (var i = 0; i < radioButtons.length; i++) {
        if (radioButtons[i].value === value) {
            radioButtons[i].checked = true;
            break;
        }
    }
}

function setLocalStorage(key, value) {
    localStorage.setItem(key, value);
}


function highlight_row(tableId)
{
    var table = document.getElementById(tableId);
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        rows[i].onclick = function () {
            for (var j = 1; j < rows.length; j++) {
                rows[j].classList.remove('selected');
            }
            this.classList.add('selected');
        }
    }
}

var input_event = '';
function highlight_row_input(tableId) {
    var table = document.getElementById(tableId);

    // 使用事件委托处理点击事件
    table.addEventListener('click', function(event) {
        var target = event.target;
        if (target.tagName === 'TD' && target.parentNode.tagName === 'TR' && target.parentNode.getAttribute('data-event')) {
            // 先移除其他行的 'selected' 类
            var rows = table.getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                rows[i].classList.remove('selected');
            }

            // 添加 'selected' 类到点击的行
            var clickedRow = target.parentNode;
            clickedRow.classList.add('selected');

            // 获取并处理事件值
            input_event  = clickedRow.getAttribute('data-event');

        } else {
           //console.log(target); 
        }
    });
}

var output_event = '';
function highlight_row_output(tableId) {
    var table = document.getElementById(tableId);

    // 使用事件委托处理点击事件
    table.addEventListener('click', function(event) {
        var target = event.target;
        if (target.tagName === 'TD' && target.parentNode.tagName === 'TR' && target.parentNode.getAttribute('data-event')) {
            // 先移除其他行的 'selected' 类
            var rows = table.getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                rows[i].classList.remove('selected');
            }

            // 添加 'selected' 类到点击的行
            var clickedRow = target.parentNode;
            clickedRow.classList.add('selected');

            // 获取并处理事件值
            output_event  = clickedRow.getAttribute('data-event');

        } else {
           //console.log(target); 
        }
    });
}


function MoveUp(button) {
    var row = button.parentNode.parentNode;
    var prevRow = row.previousElementSibling;
    if (prevRow) {
        swap_row(row, prevRow);
       
        var index1 = Array.from(row.parentNode.children).indexOf(row);
        var index2 = Array.from(row.parentNode.children).indexOf(prevRow);
        var temp = rowInfoArray[index1];
        rowInfoArray[index1] = rowInfoArray[index2];
        rowInfoArray[index2] = temp;

        console.log(rowInfoArray);
        sendRowInfoArray();
    }
}

function MoveDown(button) {
    var row = button.parentNode.parentNode;
    var nextRow = row.nextElementSibling;
    if (nextRow) {
        swap_row(nextRow, row);
     
        var index1 = Array.from(row.parentNode.children).indexOf(row);
        var index2 = Array.from(row.parentNode.children).indexOf(nextRow);
        var temp = rowInfoArray[index1];
        rowInfoArray[index1] = rowInfoArray[index2];
        rowInfoArray[index2] = temp;

        console.log(rowInfoArray);
        sendRowInfoArray();
    }
}


function swap_row(row1, row2) {
    var parent = row1.parentNode;
    var nextSibling = row2.nextSibling;
    parent.insertBefore(row2, row1);
    parent.insertBefore(row1, nextSibling);
}



function removeElements(elementIds) {
    elementIds.forEach(function(id) {
        var element = document.getElementById(id);
        if (element) {
            element.parentNode.removeChild(element);
        }
    });
}


function enableButton() {
    var button = document.getElementById('Button_Select');
    if (button.disabled) {
        button.disabled = false;
    }
}

function resetBackgroundColor() {
    var jobInput = document.getElementById('job_id');
    if (jobInput.style.backgroundColor === 'yellow') {
        jobInput.style.backgroundColor = '';
    }
}

function cleanResponse(response) {
    var responseJSON = JSON.stringify(response);
    var cleanString = responseJSON.replace(/Array|\\n/g, '');
    var cleanString = cleanString.substring(2, cleanString.length - 2);
    return cleanString ? JSON.parse(cleanString) : null;
}


function getCookie(cookieName) {
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim(); 
        if (cookie.startsWith(cookieName + '=')) {
            return cookie.substring(cookieName.length + 1); 
        }
    }
    return '';
}



function language_change(language) {
    if( language){
        $.ajax({
            url: "?url=Dashboards/change_language",
            method: "POST",
            data:{ 
                language: language

            },
            success: function(response) {
     
            },
            error: function(xhr, status, error) {
                
            }
        });
    }
}

function forceLogoutAllTabs(redirectUrl) {
    try {
        // 清 storage
        localStorage.clear();
        sessionStorage.clear();

        // 清常見 cookie（前端可刪的）
        document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;

        // 通知其他分頁
        localStorage.setItem('idas_force_logout', String(Date.now()));
    } catch (e) {
        console.warn('force logout failed:', e);
    }

    window.location.replace(redirectUrl || '/idas/public/?url=In');
}


function back() {
    window.location.href = '?url=In';
}

function logout() {
    deleteCookie('username');
    deleteCookie('auth_token');
    window.location.href = '?url=In';
}

function deleteCookie(name) {
    document.cookie = `${name}=; expires=${new Date(0).toUTCString()}; path=/;`;
}

function closebutton(elementId) {
    const modal = document.getElementById(elementId);
    if (!modal) return;

    // 只允許 newinput 在顯示時被關閉（避免誤關閉）
    if (elementId === 'newinput') {
        const isVisible = getComputedStyle(modal).display !== 'none';
        if (!isVisible) return; // 已關閉就不重複處理
    }

    modal.style.display = 'none';

    const mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.classList.remove("overlay-active");
    }

    const overlay = document.getElementById("modal-overlay");
    if (overlay) {
        overlay.style.display = "none";
    }
}




function closebutton_io(elementId) {
    // 確保傳入的 elementId 有效，並且元素存在
    document.getElementById(elementId).style.display = 'none';
   
    // 確保 .main-content 元素存在
    var mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.classList.remove("overlay-active");
    }
}


function success_response(response, spinnerId = 'spinner', autoClose = false) {
    const responseData = JSON.parse(response);

    setTimeout(() => {
        document.getElementById(spinnerId).style.display = 'none';

        if (autoClose) {
            alertify.alert(responseData.res_type, responseData.res_msg);
            setTimeout(() => {
                alertify.closeAll();
                history.go(0);
            }, 3000);
        } else {
            alertify.alert(responseData.res_type, responseData.res_msg, function () {
                alertify.closeAll();
                history.go(0);
            });
        }
    }, 1000);
}


function checkAuthToken() {
    // 取得指定 cookie
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    // 語系處理
    const language = getCookie('language');
    const messages = {
        "zh-cn": "閒置超過時間，请重新登录。",
        "zh-tw": "閒置超過時間，請重新登入。",
        "default": "Session timeout. Please log in again."
    };
    const title = {
        "zh-cn": "登錄超時",
        "zh-tw": "登入逾時",
        "default": "Login Timeout"
    };

    const msg = messages[language] || messages["default"];
    const titleText = title[language] || title["default"];

    // 檢查 auth_token 是否存在
    const authToken = getCookie('auth_token');
    if (!authToken) {
        alert(titleText + "\n\n" + msg);
        window.location.href = "/login";
    }
}

// 呼叫檢查
//checkAuthToken();


function success_response_seq(response, spinnerId = 'spinner', redirectUrl = null) {
  // 解析回傳
  let data;
  try {
    data = (typeof response === 'string') ? JSON.parse(response) : response;
  } catch (e) {
    data = { res_type: 'Info', res_msg: String(response || 'Done') };
  }
  const title = data?.res_type || 'Info';
  const msg   = data?.res_msg  || '';

  // 取得語系 OK/CANCEL 文案
  const cookieLang = (typeof getCookie === 'function' && getCookie('language')) || 'en-us';
  const labels = (typeof getAlertifyLabels === 'function')
    ? getAlertifyLabels(cookieLang)
    : (function (raw) {
        const l = String(raw).toLowerCase();
        if (l === 'zh-tw' || l === 'zh-hk' || l.includes('hant')) return { ok: '確定', cancel: '取消' };
        if (l === 'zh-cn' || l === 'zh-sg' || l.includes('hans')) return { ok: '确定', cancel: '取消' };
        return { ok: 'OK', cancel: 'Cancel' };
      })(cookieLang);

  // 1 秒後關掉 spinner 並跳出提示
  setTimeout(() => {
    const sp = document.getElementById(spinnerId);
    if (sp) sp.style.display = 'none';

    let navigated = false;
    const navigateOnce = function () {
      if (navigated) return;
      navigated = true;
      try { alertify.closeAll(); } catch (e) {}
      document.querySelector('.main-content')?.classList.remove('overlay-active');
      if (redirectUrl) window.location.href = redirectUrl;
      else history.go(0);
    };

    // 保險地設定 OK 文案
    if (window.alertify && alertify.defaults && alertify.defaults.glossary) {
      try { alertify.defaults.glossary.ok = labels.ok; } catch (e) {}
    }

    // ====== 兼容處理：偵測是否支援 (title, message) 兩參數 ======
    const supportsTitleParam = typeof alertify.alert === 'function' && alertify.alert.length >= 2;

    let dlg;
    if (supportsTitleParam) {
      // v1.x 正規 API：alert(title, message)
      dlg = alertify
        .alert(title, msg, function () {
          clearTimeout(autoTimer);
          navigateOnce();
        })
        .set('labels', { ok: labels.ok });
    } else {
      // 舊版相容：先建 alert，再設定 title
      dlg = alertify
        .alert(msg, function () {
          clearTimeout(autoTimer);
          navigateOnce();
        });

      // 有些舊版用 set('title', ...)；若不支援則退而求其次用 set('header', ...)
      try {
        if (typeof dlg.set === 'function') {
          dlg.set('title', title);
        } else if (typeof dlg.setting === 'function') {
          dlg.setting('title', title);
        }
      } catch (e) {
        try { dlg.set('header', title); } catch (e2) {}
      }

      // 設置按鈕文字（舊版：全域設定；新版：labels）
      try {
        if (dlg && typeof dlg.set === 'function') {
          dlg.set('labels', { ok: labels.ok });
        } else if (alertify && alertify.set) {
          alertify.set({ labels: { ok: labels.ok } });
        }
      } catch (e) {}
    }
    // ====== end 兼容處理 ======

    // 3 秒後自動關閉並導頁（避免雙重導頁）
    const autoTimer = setTimeout(navigateOnce, 3000);

  }, 1000);
}





function handleAjaxResponse(responseData) {
    try {
        const res = typeof responseData === "string" ? JSON.parse(responseData) : responseData;

        setTimeout(function () {
            document.getElementById('spinner').style.display = 'none';

            alertify.alert(res.res_type, res.res_msg, function () {
                history.go(0);
            });

            setTimeout(function () {
                alertify.closeAll();
            }, 3000);
        }, 1000);
    } catch (e) {
        console.error("JSON parse error:", e);
        alert("回傳格式錯誤");
    }
}
} else {
window.addEventListener('storage', function (event) {
    if (event.key === 'idas_force_logout') {
        try {
            localStorage.clear();
            sessionStorage.clear();

            document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            document.cookie = 'user_law=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
            document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
            document.cookie = 'user_law=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
        } catch (e) {
            console.warn('storage logout failed:', e);
        }

        window.location.replace('/idas/public/?url=In');
    }
});


function hideElementById(elementId) {
    var element = document.getElementById(elementId);
    if (element){
        element.style.display = 'none';
    }
}

//讀取localstorge
function readFromLocalStorage(key) {
    return localStorage.getItem(key);
}

function setRadioButtonValue(radioButtons, value) {
    for (var i = 0; i < radioButtons.length; i++) {
        if (radioButtons[i].value === value) {
            radioButtons[i].checked = true;
            break;
        }
    }
}

function setLocalStorage(key, value) {
    localStorage.setItem(key, value);
}


function highlight_row(tableId)
{
    var table = document.getElementById(tableId);
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        rows[i].onclick = function () {
            for (var j = 1; j < rows.length; j++) {
                rows[j].classList.remove('selected');
            }
            this.classList.add('selected');
        }
    }
}

var input_event = '';
function highlight_row_input(tableId) {
    var table = document.getElementById(tableId);

    // 使用事件委托处理点击事件
    table.addEventListener('click', function(event) {
        var target = event.target;
        if (target.tagName === 'TD' && target.parentNode.tagName === 'TR' && target.parentNode.getAttribute('data-event')) {
            // 先移除其他行的 'selected' 类
            var rows = table.getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                rows[i].classList.remove('selected');
            }

            // 添加 'selected' 类到点击的行
            var clickedRow = target.parentNode;
            clickedRow.classList.add('selected');

            // 获取并处理事件值
            input_event  = clickedRow.getAttribute('data-event');

        } else {
           //console.log(target); 
        }
    });
}

var output_event = '';
function highlight_row_output(tableId) {
    var table = document.getElementById(tableId);

    // 使用事件委托处理点击事件
    table.addEventListener('click', function(event) {
        var target = event.target;
        if (target.tagName === 'TD' && target.parentNode.tagName === 'TR' && target.parentNode.getAttribute('data-event')) {
            // 先移除其他行的 'selected' 类
            var rows = table.getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                rows[i].classList.remove('selected');
            }

            // 添加 'selected' 类到点击的行
            var clickedRow = target.parentNode;
            clickedRow.classList.add('selected');

            // 获取并处理事件值
            output_event  = clickedRow.getAttribute('data-event');

        } else {
           //console.log(target); 
        }
    });
}


function MoveUp(button) {
    var row = button.parentNode.parentNode;
    var prevRow = row.previousElementSibling;
    if (prevRow) {
        swap_row(row, prevRow);
       
        var index1 = Array.from(row.parentNode.children).indexOf(row);
        var index2 = Array.from(row.parentNode.children).indexOf(prevRow);
        var temp = rowInfoArray[index1];
        rowInfoArray[index1] = rowInfoArray[index2];
        rowInfoArray[index2] = temp;

        console.log(rowInfoArray);
        sendRowInfoArray();
    }
}

function MoveDown(button) {
    var row = button.parentNode.parentNode;
    var nextRow = row.nextElementSibling;
    if (nextRow) {
        swap_row(nextRow, row);
     
        var index1 = Array.from(row.parentNode.children).indexOf(row);
        var index2 = Array.from(row.parentNode.children).indexOf(nextRow);
        var temp = rowInfoArray[index1];
        rowInfoArray[index1] = rowInfoArray[index2];
        rowInfoArray[index2] = temp;

        console.log(rowInfoArray);
        sendRowInfoArray();
    }
}


function swap_row(row1, row2) {
    var parent = row1.parentNode;
    var nextSibling = row2.nextSibling;
    parent.insertBefore(row2, row1);
    parent.insertBefore(row1, nextSibling);
}



function removeElements(elementIds) {
    elementIds.forEach(function(id) {
        var element = document.getElementById(id);
        if (element) {
            element.parentNode.removeChild(element);
        }
    });
}


function enableButton() {
    var button = document.getElementById('Button_Select');
    if (button.disabled) {
        button.disabled = false;
    }
}

function resetBackgroundColor() {
    var jobInput = document.getElementById('job_id');
    if (jobInput.style.backgroundColor === 'yellow') {
        jobInput.style.backgroundColor = '';
    }
}

function cleanResponse(response) {
    var responseJSON = JSON.stringify(response);
    var cleanString = responseJSON.replace(/Array|\\n/g, '');
    var cleanString = cleanString.substring(2, cleanString.length - 2);
    return cleanString ? JSON.parse(cleanString) : null;
}


function getCookie(cookieName) {
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim(); 
        if (cookie.startsWith(cookieName + '=')) {
            return cookie.substring(cookieName.length + 1); 
        }
    }
    return '';
}



function language_change(language) {
    if( language){
        $.ajax({
            url: "?url=Dashboards/change_language",
            method: "POST",
            data:{ 
                language: language

            },
            success: function(response) {
     
            },
            error: function(xhr, status, error) {
                
            }
        });
    }
}

function forceLogoutAllTabs(redirectUrl) {
    try {
        // 清 storage
        localStorage.clear();
        sessionStorage.clear();

        // 清常見 cookie（前端可刪的）
        document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'user_law=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        document.cookie = 'username=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
        document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;
        document.cookie = 'user_law=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=' + location.hostname;

        // 通知其他分頁
        localStorage.setItem('idas_force_logout', String(Date.now()));
    } catch (e) {
        console.warn('force logout failed:', e);
    }

    window.location.replace(redirectUrl || '/idas/public/?url=In');
}


function back() {
    window.location.href = '?url=In';
}

function logout() {
    var username = '';
    try {
        username = getCookie('username') || '';
    } catch (e) {
        username = '';
    }

    var redirect = function() {
        deleteCookie('username');
        deleteCookie('auth_token');
        deleteCookie('user_law');
        window.location.href = '?url=In';
    };

    var body = new URLSearchParams();
    body.append('logout_audit', '1');
    body.append('username', username);

    // Use In/logout because this project enters login through ?url=In.
    // Logins.php also handles this inside index() before normal auth checks.
    if (window.fetch) {
        fetch('?url=In/logout', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            }
        }).catch(function() {
            // If audit logging fails, still logout the user.
        }).finally(function() {
            redirect();
        });
        return;
    }

    // Older browser fallback.
    if (navigator.sendBeacon) {
        navigator.sendBeacon('?url=In/logout', body);
        setTimeout(redirect, 300);
        return;
    }

    redirect();
}

function deleteCookie(name) {
    document.cookie = `${name}=; expires=${new Date(0).toUTCString()}; path=/;`;
}

function closebutton(elementId) {
    const modal = document.getElementById(elementId);
    if (!modal) return;

    // 只允許 newinput 在顯示時被關閉（避免誤關閉）
    if (elementId === 'newinput') {
        const isVisible = getComputedStyle(modal).display !== 'none';
        if (!isVisible) return; // 已關閉就不重複處理
    }

    modal.style.display = 'none';

    const mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.classList.remove("overlay-active");
    }

    const overlay = document.getElementById("modal-overlay");
    if (overlay) {
        overlay.style.display = "none";
    }
}




function closebutton_io(elementId) {
    // 確保傳入的 elementId 有效，並且元素存在
    document.getElementById(elementId).style.display = 'none';
   
    // 確保 .main-content 元素存在
    var mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.classList.remove("overlay-active");
    }
}


function success_response(response, spinnerId = 'spinner', autoClose = false) {
    const responseData = JSON.parse(response);

    setTimeout(() => {
        document.getElementById(spinnerId).style.display = 'none';

        if (autoClose) {
            alertify.alert(responseData.res_type, responseData.res_msg);
            setTimeout(() => {
                alertify.closeAll();
                history.go(0);
            }, 3000);
        } else {
            alertify.alert(responseData.res_type, responseData.res_msg, function () {
                alertify.closeAll();
                history.go(0);
            });
        }
    }, 1000);
}


function checkAuthToken() {
    // 取得指定 cookie
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    // 語系處理
    const language = getCookie('language');
    const messages = {
        "zh-cn": "閒置超過時間，请重新登录。",
        "zh-tw": "閒置超過時間，請重新登入。",
        "default": "Session timeout. Please log in again."
    };
    const title = {
        "zh-cn": "登錄超時",
        "zh-tw": "登入逾時",
        "default": "Login Timeout"
    };

    const msg = messages[language] || messages["default"];
    const titleText = title[language] || title["default"];

    // 檢查 auth_token 是否存在
    const authToken = getCookie('auth_token');
    if (!authToken) {
        alert(titleText + "\n\n" + msg);
        window.location.href = "/login";
    }
}

// 呼叫檢查
//checkAuthToken();


function success_response_seq(response, spinnerId = 'spinner', redirectUrl = null) {
  // 解析回傳
  let data;
  try {
    data = (typeof response === 'string') ? JSON.parse(response) : response;
  } catch (e) {
    data = { res_type: 'Info', res_msg: String(response || 'Done') };
  }
  const title = data?.res_type || 'Info';
  const msg   = data?.res_msg  || '';

  // 取得語系 OK/CANCEL 文案
  const cookieLang = (typeof getCookie === 'function' && getCookie('language')) || 'en-us';
  const labels = (typeof getAlertifyLabels === 'function')
    ? getAlertifyLabels(cookieLang)
    : (function (raw) {
        const l = String(raw).toLowerCase();
        if (l === 'zh-tw' || l === 'zh-hk' || l.includes('hant')) return { ok: '確定', cancel: '取消' };
        if (l === 'zh-cn' || l === 'zh-sg' || l.includes('hans')) return { ok: '确定', cancel: '取消' };
        return { ok: 'OK', cancel: 'Cancel' };
      })(cookieLang);

  // 1 秒後關掉 spinner 並跳出提示
  setTimeout(() => {
    const sp = document.getElementById(spinnerId);
    if (sp) sp.style.display = 'none';

    let navigated = false;
    const navigateOnce = function () {
      if (navigated) return;
      navigated = true;
      try { alertify.closeAll(); } catch (e) {}
      document.querySelector('.main-content')?.classList.remove('overlay-active');
      if (redirectUrl) window.location.href = redirectUrl;
      else history.go(0);
    };

    // 保險地設定 OK 文案
    if (window.alertify && alertify.defaults && alertify.defaults.glossary) {
      try { alertify.defaults.glossary.ok = labels.ok; } catch (e) {}
    }

    // ====== 兼容處理：偵測是否支援 (title, message) 兩參數 ======
    const supportsTitleParam = typeof alertify.alert === 'function' && alertify.alert.length >= 2;

    let dlg;
    if (supportsTitleParam) {
      // v1.x 正規 API：alert(title, message)
      dlg = alertify
        .alert(title, msg, function () {
          clearTimeout(autoTimer);
          navigateOnce();
        })
        .set('labels', { ok: labels.ok });
    } else {
      // 舊版相容：先建 alert，再設定 title
      dlg = alertify
        .alert(msg, function () {
          clearTimeout(autoTimer);
          navigateOnce();
        });

      // 有些舊版用 set('title', ...)；若不支援則退而求其次用 set('header', ...)
      try {
        if (typeof dlg.set === 'function') {
          dlg.set('title', title);
        } else if (typeof dlg.setting === 'function') {
          dlg.setting('title', title);
        }
      } catch (e) {
        try { dlg.set('header', title); } catch (e2) {}
      }

      // 設置按鈕文字（舊版：全域設定；新版：labels）
      try {
        if (dlg && typeof dlg.set === 'function') {
          dlg.set('labels', { ok: labels.ok });
        } else if (alertify && alertify.set) {
          alertify.set({ labels: { ok: labels.ok } });
        }
      } catch (e) {}
    }
    // ====== end 兼容處理 ======

    // 3 秒後自動關閉並導頁（避免雙重導頁）
    const autoTimer = setTimeout(navigateOnce, 3000);

  }, 1000);
}





function handleAjaxResponse(responseData) {
    try {
        const res = typeof responseData === "string" ? JSON.parse(responseData) : responseData;

        setTimeout(function () {
            document.getElementById('spinner').style.display = 'none';

            alertify.alert(res.res_type, res.res_msg, function () {
                history.go(0);
            });

            setTimeout(function () {
                alertify.closeAll();
            }, 3000);
        }, 1000);
    } catch (e) {
        console.error("JSON parse error:", e);
        alert("回傳格式錯誤");
    }
}

/* Operator law=3 Job/Seq/Step CRUD Button Lock V2
 * law=3 operator 可瀏覽 Job / Seq / Step。
 * Job Management：Edit 按鈕可開啟視窗，但 Edit 視窗內 Save(updatejob) 不可執行。
 * Job：Edit 可開啟，Edit Save(updatejob) 不可執行。
 * Seq：Edit 可開啟；New / Copy / Delete、Up / Down / Enable、Edit Save(save_sequence/edit_sequence) 不可執行。
 * Step：Edit 可開啟；New / Copy / Delete、Up / Down、Edit Save(save_or_edit_step) 不可執行。
 * Remote：讀取工作可執行；切換工作 / Change Job Save 不可執行。
 * Logout 會清除 user_law cookie，重新登入 law=1 時功能恢復。
 */
(function () {
    function idasGetCookieSafe(name) {
        var parts = document.cookie ? document.cookie.split(';') : [];
        var prefix = name + '=';
        for (var i = 0; i < parts.length; i++) {
            var item = parts[i].trim();
            if (item.indexOf(prefix) === 0) {
                try {
                    return decodeURIComponent(item.substring(prefix.length));
                } catch (e) {
                    return item.substring(prefix.length);
                }
            }
        }
        return '';
    }

    function isOperatorLaw3() {
        return String(idasGetCookieSafe('user_law') || '').trim() === '3';
    }

    function insertOperatorCrudStyle() {
        if (document.getElementById('idasOperatorCrudStyle')) return;
        var style = document.createElement('style');
        style.id = 'idasOperatorCrudStyle';
        style.textContent = [
            '.idas-operator-crud-disabled,',
            '.operator-disabled,',
            '.operator-seq-disabled,',
            '.operator-seq-save-disabled,',
            '.operator-setting-locked,',
            '.idas-operator-step-save-disabled,',
            '.download-restricted {',
            '  opacity: 0.45 !important;',
            '  cursor: not-allowed !important;',
            '  filter: grayscale(1) !important;',
            '  pointer-events: auto !important;',
            '}',
            '.idas-operator-crud-disabled:hover,',
            '.operator-disabled:hover,',
            '.operator-seq-disabled:hover,',
            '.operator-seq-save-disabled:hover,',
            '.operator-setting-locked:hover,',
            '.idas-operator-step-save-disabled:hover,',
            '.download-restricted:hover {',
            '  transform: none !important;',
            '}',
            '.idas-operator-forbidden-badge {',
            '  display: inline-flex;',
            '  align-items: center;',
            '  justify-content: center;',
            '  margin-right: 0.25em;',
            '  font-size: 1em;',
            '  line-height: 1;',
            '  pointer-events: none;',
            '}',
            'img.idas-operator-crud-disabled + .idas-operator-forbidden-badge,',
            'img.operator-seq-disabled + .idas-operator-forbidden-badge,',
            'img.operator-disabled + .idas-operator-forbidden-badge {',
            '  margin-left: 4px;',
            '  margin-right: 0;',
            '  vertical-align: middle;',
            '}',
            '.menu-item.operator-disabled {',
            '  position: relative !important;',
            '}',
            '.menu-item.operator-disabled .idas-operator-forbidden-badge {',
            '  position: absolute;',
            '  top: 5px;',
            '  right: 6px;',
            '  margin: 0;',
            '  font-size: 24px;',
            '  z-index: 3;',
            '}',
            '.buttonbox input.idas-operator-crud-disabled,',
            '.buttonbox input.operator-seq-disabled,',
            '.buttonbox input.download-restricted,',
            'button.idas-operator-crud-disabled,',
            'button.operator-seq-save-disabled,',
            'button.idas-operator-step-save-disabled,',
            'button.download-restricted {',
            '  cursor: not-allowed !important;',
            '}',
            '',
            '/* Operator disabled layout fix: keep 🚫 and label in one line. */',
            '.modal-footer {',
            '  display: flex !important;',
            '  align-items: center !important;',
            '  justify-content: center !important;',
            '  gap: 10px !important;',
            '  overflow: visible !important;',
            '}',
            'button.idas-operator-crud-disabled:not(.menu-item),',
            'button.operator-disabled:not(.menu-item),',
            'button.operator-seq-save-disabled,',
            'button.idas-operator-step-save-disabled,',
            'button.download-restricted,',
            '.button-modal.idas-operator-crud-disabled,',
            '.button-modal.operator-disabled {',
            '  display: inline-flex !important;',
            '  align-items: center !important;',
            '  justify-content: center !important;',
            '  flex-wrap: nowrap !important;',
            '  gap: 4px !important;',
            '  white-space: nowrap !important;',
            '  min-width: 78px !important;',
            '  width: auto !important;',
            '  height: 35px !important;',
            '  line-height: 1 !important;',
            '  padding: 0 12px !important;',
            '  box-sizing: border-box !important;',
            '  overflow: hidden !important;',
            '  vertical-align: middle !important;',
            '}',
            'input[type="button"].idas-operator-crud-disabled,',
            'input[type="submit"].idas-operator-crud-disabled,',
            'input[type="button"].operator-disabled,',
            'input[type="submit"].operator-disabled,',
            'input[type="button"].download-restricted,',
            'input[type="submit"].download-restricted {',
            '  white-space: nowrap !important;',
            '  min-width: 78px !important;',
            '  width: auto !important;',
            '  height: 35px !important;',
            '  line-height: 1 !important;',
            '  padding: 0 10px !important;',
            '  box-sizing: border-box !important;',
            '}',
            'button.idas-operator-crud-disabled:not(.menu-item) .idas-operator-forbidden-badge,',
            'button.operator-disabled:not(.menu-item) .idas-operator-forbidden-badge,',
            'button.operator-seq-save-disabled .idas-operator-forbidden-badge,',
            'button.idas-operator-step-save-disabled .idas-operator-forbidden-badge,',
            'button.download-restricted .idas-operator-forbidden-badge,',
            '.button-modal.idas-operator-crud-disabled .idas-operator-forbidden-badge,',
            '.button-modal.operator-disabled .idas-operator-forbidden-badge {',
            '  flex: 0 0 auto !important;',
            '  margin: 0 !important;',
            '  line-height: 1 !important;',
            '}',
            '.modal-footer button.idas-operator-crud-disabled + button,',
            '.modal-footer button.operator-disabled + button {',
            '  margin-left: 8px !important;',
            '}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function getOnclickText(el) {
        return String((el && el.getAttribute) ? (el.getAttribute('onclick') || '') : '');
    }

    function getOperatorDeniedTitle() {
        return 'Operator / law = 3';
    }

    function decorateForbiddenElement(el) {
        if (!el || el.nodeType !== 1 || el.dataset.operatorForbiddenDecorated === '1') return;

        var tag = String(el.tagName || '').toUpperCase();
        el.dataset.operatorForbiddenDecorated = '1';
        if (!el.getAttribute('title')) {
            el.setAttribute('title', getOperatorDeniedTitle());
        }

        // input[type=button/submit] 無法使用 ::before，直接把 value 加上禁止圖示。
        if (tag === 'INPUT') {
            var type = String(el.getAttribute('type') || '').toLowerCase();
            if (type === 'button' || type === 'submit' || type === 'reset') {
                var value = String(el.value || '');
                el.dataset.operatorOriginalValue = value;
                if (value && value.indexOf('🚫') !== 0) {
                    el.value = '🚫 ' + value;
                }
            }
            return;
        }

        // 圖片按鈕（例如新增工序 + / 上下移）在旁邊補禁止圖示。
        if (tag === 'IMG') {
            if (el.nextSibling && el.nextSibling.nodeType === 1 && el.nextSibling.classList && el.nextSibling.classList.contains('idas-operator-forbidden-badge')) return;
            var imgBadge = document.createElement('span');
            imgBadge.className = 'idas-operator-forbidden-badge';
            imgBadge.setAttribute('aria-hidden', 'true');
            imgBadge.textContent = '🚫';
            if (el.parentNode) {
                el.parentNode.insertBefore(imgBadge, el.nextSibling);
            }
            return;
        }

        // 主選單的大圖示按鈕使用右上角 badge，避免破壞原本圖片文字。
        if (el.classList && el.classList.contains('menu-item')) {
            if (!el.querySelector('.idas-operator-forbidden-badge')) {
                var menuBadge = document.createElement('span');
                menuBadge.className = 'idas-operator-forbidden-badge';
                menuBadge.setAttribute('aria-hidden', 'true');
                menuBadge.textContent = '🚫';
                el.appendChild(menuBadge);
            }
            return;
        }

        // 一般 button / a：文字前補禁止圖示。
        if (tag === 'BUTTON' || tag === 'A') {
            if (!el.querySelector('.idas-operator-forbidden-badge')) {
                var badge = document.createElement('span');
                badge.className = 'idas-operator-forbidden-badge';
                badge.setAttribute('aria-hidden', 'true');
                badge.textContent = '🚫';
                el.insertBefore(badge, el.firstChild);
            }
            return;
        }
    }

    function undecorateForbiddenElement(el) {
        if (!el || el.nodeType !== 1) return;
        var tag = String(el.tagName || '').toUpperCase();

        if (tag === 'INPUT' && typeof el.dataset.operatorOriginalValue !== 'undefined') {
            el.value = el.dataset.operatorOriginalValue;
            delete el.dataset.operatorOriginalValue;
        }

        if (tag === 'IMG') {
            var next = el.nextSibling;
            if (next && next.nodeType === 1 && next.classList && next.classList.contains('idas-operator-forbidden-badge')) {
                next.parentNode.removeChild(next);
            }
        } else {
            var badges = el.querySelectorAll ? el.querySelectorAll('.idas-operator-forbidden-badge') : [];
            for (var i = badges.length - 1; i >= 0; i--) {
                badges[i].parentNode.removeChild(badges[i]);
            }
        }
        delete el.dataset.operatorForbiddenDecorated;
    }

    function isAlreadyOperatorLockedElement(el) {
        if (!el || el.nodeType !== 1 || !el.classList) return false;
        return el.classList.contains('idas-operator-crud-disabled')
            || el.classList.contains('operator-disabled')
            || el.classList.contains('operator-seq-disabled')
            || el.classList.contains('operator-seq-save-disabled')
            || el.classList.contains('operator-setting-locked')
            || el.classList.contains('idas-operator-step-save-disabled')
            || el.classList.contains('download-restricted')
            || String(el.getAttribute('aria-disabled') || '') === 'true' && String(el.dataset.operatorCrudLocked || '') === '1';
    }

    function decorateAllOperatorLockedButtons() {
        if (!isOperatorLaw3()) return;
        insertOperatorCrudStyle();
        var selectors = [
            '.idas-operator-crud-disabled',
            '.operator-disabled',
            '.operator-seq-disabled',
            '.operator-seq-save-disabled',
            '.operator-setting-locked',
            '.idas-operator-step-save-disabled',
            '.download-restricted'
        ];
        document.querySelectorAll(selectors.join(',')).forEach(function (el) {
            decorateForbiddenElement(el);
        });
    }

    function isJobEditOpenButton(el) {
        if (!el || el.nodeType !== 1) return false;

        var onclickText = getOnclickText(el);
        var nameText = String(el.getAttribute('name') || '');
        var idText = String(el.id || '');

        // Job 頁面的 Edit 按鈕：允許 operator 開啟 edit modal。
        if (/^Job_Manager_Submit$/i.test(nameText) && /^S6$/i.test(idText)) return true;
        if (/\bcound_job\s*\(\s*['"]edit['"]\s*\)/i.test(onclickText)) return true;

        return false;
    }

    function isSeqEditOpenButton(el) {
        if (!el || el.nodeType !== 1) return false;

        var onclickText = getOnclickText(el);
        var nameText = String(el.getAttribute('name') || '');
        var idText = String(el.id || '');

        // Seq 頁面的 Edit 按鈕：允許 operator 進入編輯頁；真正禁止的是編輯頁 Save。
        if (/^Seq_Manager_Submit$/i.test(nameText) && /^S6$/i.test(idText)) return true;
        if (/\bcound_seq\s*\(\s*['"]edit['"]\s*\)/i.test(onclickText)) return true;

        return false;
    }

    function isStepEditOpenButton(el) {
        if (!el || el.nodeType !== 1) return false;

        var onclickText = getOnclickText(el);
        var nameText = String(el.getAttribute('name') || '');
        var idText = String(el.id || '');

        // Step 頁面的 Edit 按鈕：允許 operator 進入編輯頁；真正禁止的是編輯頁 Save。
        if (/^Step_Manager_Submit$/i.test(nameText) && /^S6$/i.test(idText)) return true;
        if (/\bcound_step\s*\(\s*['"]edit['"]\s*\)/i.test(onclickText)) return true;

        return false;
    }


    function isIoEditOpenButton(el) {
        if (!el || el.nodeType !== 1) return false;

        var onclickText = getOnclickText(el);

        // Input / Output 頁面的 Edit 按鈕：允許 operator 開啟 edit modal；真正禁止的是 modal 裡的 Save。
        if (/\bcrud_job_event\s*\(\s*['"]edit['"]\s*\)/i.test(onclickText)) return true;

        return false;
    }

    function isCrudActionElement(el) {
        if (!el || el.nodeType !== 1) return false;

        // 例外：Job / Seq / Step / Input / Output 的 Edit 可以點，真正禁止的是 edit modal 裡的 Save。
        if (isJobEditOpenButton(el) || isSeqEditOpenButton(el) || isStepEditOpenButton(el) || isIoEditOpenButton(el)) return false;

        var onclickText = getOnclickText(el);
        var nameText = String(el.getAttribute('name') || '');
        var idText = String(el.id || '');

        if (/\bcound_(job|seq|step)\s*\(/i.test(onclickText)) return true;

        // operator 不允許 Input / Output 的 New / Copy / Delete / Align / Table。Edit 可進入，但 Save 不可執行。
        if (/\bcrud_job_event\s*\(/i.test(onclickText)) return true;
        if (/\btablesubmit\s*\(/i.test(onclickText)) return true;

        // operator 不允許 Seq / Step 排序或 Enable 狀態變更。
        // 這些按鈕多數是 img / checkbox，不一定會被 input button 的 selector 抓到。
        if (/\bMoveUp\s*\(/i.test(onclickText)) return true;
        if (/\bMoveDown\s*\(/i.test(onclickText)) return true;
        if (/\bupdateValue\s*\(/i.test(onclickText)) return true;

        // Remote / Command 頁：讀取工作(get_job)允許；切換工作會寫控制器，不允許 operator 執行。
        if (/\bswitch_job\s*\(/i.test(onclickText)) return true;

        // 後備：依照既有頁面按鈕 name 判斷 Job / Seq / Step 管理列的 New/Edit/Copy/Delete。
        if (/^(Job|Seq|Step)_Manager_Submit$/i.test(nameText) && /^S[3456]$/i.test(idText)) return true;

        // 後備：Seq enable checkbox。
        if (el.classList && el.classList.contains('seq_enable')) return true;

        return false;
    }

    function isOperatorSaveLockElement(el) {
        if (!el || el.nodeType !== 1) return false;

        var onclickText = getOnclickText(el);

        // Job New/Edit/Copy 視窗裡的 Save；operator 不允許真正寫入。
        if (/\b(savejob|updatejob|copy_job_by_id)\s*\(/i.test(onclickText)) return true;

        // Seq New/Edit/Copy 頁面的 Save；operator 不允許真正寫入。
        if (/\bsave_sequence\s*\(/i.test(onclickText)) return true;
        if (/\bedit_sequence\s*\(/i.test(onclickText)) return true;
        if (/\bcopy_seq_by_id\s*\(/i.test(onclickText)) return true;

        // Step New/Edit/Copy 頁面的 Save；operator 不允許真正寫入。
        if (/\bsave_or_edit_step\s*\(/i.test(onclickText)) return true;
        if (/\bcopy_step_by_id_ajax\s*\(/i.test(onclickText)) return true;

        // Input / Output 視窗裡的 Save：operator 可開啟 Edit 視窗，但不可真正寫入。
        if (/\b(create_input_id|edit_input_id|copy_input_id)\s*\(/i.test(onclickText)) return true;
        if (/\b(create_output_id|edit_output_id|copy_output_id)\s*\(/i.test(onclickText)) return true;

        // Remote / Command 切換工作視窗裡的 Save；operator 不允許改 Controller 目前工作。
        if (/\bchange_job\s*\(/i.test(onclickText)) return true;

        // 保留標記式寫法，未來其他頁面可直接加 data-operator-save-lock="1"。
        if (String(el.getAttribute('data-operator-save-lock') || '') === '1') return true;

        return false;
    }

    function disableCrudElement(el) {
        if (!el || el.dataset.operatorCrudLocked === '1') return;
        el.dataset.operatorOriginalOnclick = el.getAttribute('onclick') || '';
        el.removeAttribute('onclick');
        el.onclick = null;
        el.disabled = true;
        el.setAttribute('aria-disabled', 'true');
        el.classList.add('idas-operator-crud-disabled');
        el.dataset.operatorCrudLocked = '1';
        decorateForbiddenElement(el);
    }

    function restoreJobEditButtonIfNeeded(el) {
        if (!el || !isJobEditOpenButton(el)) return;

        el.disabled = false;
        el.removeAttribute('aria-disabled');
        el.classList.remove('idas-operator-crud-disabled');
        undecorateForbiddenElement(el);
        delete el.dataset.operatorCrudLocked;

        if (!el.getAttribute('onclick')) {
            el.setAttribute('onclick', "cound_job('edit')");
        }
    }

    function restoreSeqEditButtonIfNeeded(el) {
        if (!el || !isSeqEditOpenButton(el)) return;

        el.disabled = false;
        el.removeAttribute('aria-disabled');
        el.classList.remove('idas-operator-crud-disabled');
        undecorateForbiddenElement(el);
        delete el.dataset.operatorCrudLocked;

        if (!el.getAttribute('onclick')) {
            el.setAttribute('onclick', "cound_seq('edit')");
        }
    }

    function restoreStepEditButtonIfNeeded(el) {
        if (!el || !isStepEditOpenButton(el)) return;

        el.disabled = false;
        el.removeAttribute('aria-disabled');
        el.classList.remove('idas-operator-crud-disabled');
        undecorateForbiddenElement(el);
        delete el.dataset.operatorCrudLocked;

        if (!el.getAttribute('onclick')) {
            el.setAttribute('onclick', "cound_step('edit')");
        }
    }


    function restoreIoEditButtonIfNeeded(el) {
        if (!el || !isIoEditOpenButton(el)) return;

        el.disabled = false;
        el.removeAttribute('aria-disabled');
        el.classList.remove('idas-operator-crud-disabled');
        undecorateForbiddenElement(el);
        delete el.dataset.operatorCrudLocked;

        if (!el.getAttribute('onclick')) {
            el.setAttribute('onclick', "crud_job_event('edit')");
        }
    }

    function applyOperatorCrudLock() {
        if (!isOperatorLaw3()) return;
        insertOperatorCrudStyle();

        // 先確保 Job / Seq Edit 沒有被舊版或其他流程鎖住。
        document.querySelectorAll('input[name="Job_Manager_Submit"]#S6, input[type="button"][onclick*="cound_job("]').forEach(function (el) {
            restoreJobEditButtonIfNeeded(el);
        });
        document.querySelectorAll('input[name="Seq_Manager_Submit"]#S6, input[type="button"][onclick*="cound_seq("]').forEach(function (el) {
            restoreSeqEditButtonIfNeeded(el);
        });

        document.querySelectorAll('input[name="Step_Manager_Submit"]#S6, input[type="button"][onclick*="cound_step("]').forEach(function (el) {
            restoreStepEditButtonIfNeeded(el);
        });
        document.querySelectorAll('input[type="button"][onclick*="crud_job_event("]').forEach(function (el) {
            restoreIoEditButtonIfNeeded(el);
        });
        var selectors = [
            'input[type="button"][onclick*="cound_job("]',
            'input[type="button"][onclick*="cound_seq("]',
            'input[type="button"][onclick*="cound_step("]',
            'input[name="Job_Manager_Submit"]',
            'input[name="Seq_Manager_Submit"]',
            'input[name="Step_Manager_Submit"]',
            'button[onclick*="savejob("]',
            'button[onclick*="updatejob("]',
            'button[onclick*="copy_job_by_id("]',
            'button[onclick*="save_sequence("]',
            'button[onclick*="edit_sequence("]',
            'button[onclick*="copy_seq_by_id("]',
            'button[onclick*="save_or_edit_step("]',
            'button[onclick*="copy_step_by_id_ajax("]',
            'input[type="button"][onclick*="crud_job_event("]',
            'input[type="button"][onclick*="tablesubmit("]',
            'button[onclick*="create_input_id("]',
            'button[onclick*="edit_input_id("]',
            'button[onclick*="copy_input_id("]',
            'button[onclick*="create_output_id("]',
            'button[onclick*="edit_output_id("]',
            'button[onclick*="copy_output_id("]',
            'img[onclick*="MoveUp("]',
            'img[onclick*="MoveDown("]',
            'input[onclick*="updateValue("]',
            'button[onclick*="switch_job("]',
            'button[onclick*="change_job("]',
            '.seq_enable',
            '[data-operator-save-lock="1"]'
        ];

        document.querySelectorAll(selectors.join(',')).forEach(function (el) {
            if (isCrudActionElement(el) || isOperatorSaveLockElement(el)) {
                disableCrudElement(el);
            }
        });

        decorateAllOperatorLockedButtons();
    }

    document.addEventListener('DOMContentLoaded', applyOperatorCrudLock);
    window.addEventListener('load', applyOperatorCrudLock);

    // 捕捉還沒被 disable 的動態產生按鈕，避免 onclick 被觸發。
    document.addEventListener('click', function (event) {
        if (!isOperatorLaw3()) return;
        var target = event.target && event.target.closest ? event.target.closest('input, button, a, img') : event.target;
        var lockedTarget = event.target && event.target.closest ? event.target.closest('.idas-operator-crud-disabled, .operator-disabled, .operator-seq-disabled, .operator-seq-save-disabled, .operator-setting-locked, .idas-operator-step-save-disabled, .download-restricted') : null;
        if (lockedTarget && !isJobEditOpenButton(lockedTarget) && !isSeqEditOpenButton(lockedTarget) && !isStepEditOpenButton(lockedTarget) && !isIoEditOpenButton(lockedTarget)) {
            decorateForbiddenElement(lockedTarget);
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
            return false;
        }

        // Job / Seq Edit 允許打開視窗。
        if (isJobEditOpenButton(target)) {
            restoreJobEditButtonIfNeeded(target);
            return true;
        }
        if (isSeqEditOpenButton(target)) {
            restoreSeqEditButtonIfNeeded(target);
            return true;
        }

        if (isStepEditOpenButton(target)) {
            restoreStepEditButtonIfNeeded(target);
            return true;
        }
        if (isIoEditOpenButton(target)) {
            restoreIoEditButtonIfNeeded(target);
            return true;
        }

        if (isCrudActionElement(target) || isOperatorSaveLockElement(target)) {
            disableCrudElement(target);
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
    }, true);

    function installOperatorLockedIconObserver() {
        if (!isOperatorLaw3() || window.__idasOperatorLockedIconObserverInstalled) return;
        window.__idasOperatorLockedIconObserverInstalled = true;

        var run = function () {
            applyOperatorCrudLock();
            decorateAllOperatorLockedButtons();
        };

        var times = 0;
        var timer = setInterval(function () {
            run();
            times += 1;
            if (times >= 12) clearInterval(timer);
        }, 500);

        if (window.MutationObserver && document.body) {
            var observer = new MutationObserver(function () {
                clearTimeout(window.__idasOperatorLockedIconTimer);
                window.__idasOperatorLockedIconTimer = setTimeout(run, 80);
            });
            observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'disabled', 'aria-disabled'] });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        decorateAllOperatorLockedButtons();
        installOperatorLockedIconObserver();
    });
    window.addEventListener('load', function () {
        decorateAllOperatorLockedButtons();
        installOperatorLockedIconObserver();
    });

    window.idasApplyOperatorCrudLock = function () {
        applyOperatorCrudLock();
        decorateAllOperatorLockedButtons();
    };
})();
}
