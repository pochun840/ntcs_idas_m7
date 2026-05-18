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
    var username = '';
    try {
        username = getCookie('username') || '';
    } catch (e) {
        username = '';
    }

    var redirect = function() {
        deleteCookie('username');
        deleteCookie('auth_token');
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


