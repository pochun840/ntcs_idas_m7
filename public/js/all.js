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

function success_response(response, spinnerId = 'spinner') {
    const responseData = JSON.parse(response);

    setTimeout(() => {
        document.getElementById(spinnerId).style.display = 'none';

        alertify.alert(responseData.res_type, responseData.res_msg, function () {
            alertify.closeAll();
            history.go(0);
        });

        setTimeout(() => {
            alertify.closeAll();
            history.go(0);
        }, 3000);
    }, 1000);
}


function success_response_seq(response, spinnerId = 'spinner', redirectUrl = null) {
    const responseData = JSON.parse(response);

    setTimeout(() => {
        document.getElementById(spinnerId).style.display = 'none';

        alertify.alert(responseData.res_type, responseData.res_msg, function () {
            alertify.closeAll();
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                history.go(0);
            }
        });

        setTimeout(() => {
            alertify.closeAll();
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                history.go(0);
            }
        }, 3000);
    }, 1000);
}


function handleAjaxResponseWithSpinner(response, spinnerId = 'spinner') {
    const responseData = JSON.parse(response);

    // 顯示 Spinner
    document.getElementById(spinnerId).style.display = 'block';

    setTimeout(() => {
        // 隱藏 Spinner
        document.getElementById(spinnerId).style.display = 'none';

        // 顯示 alertify 並在關閉時刷新
        alertify.alert(responseData.res_type, responseData.res_msg, function () {
            alertify.closeAll();
            history.go(0);
        });

        // 自動關閉 alertify 並刷新
        setTimeout(() => {
            alertify.closeAll();
            history.go(0);
        }, 3000);
    }, 1000); // 延遲 1 秒
}



function hideElementById(id) {
    document.getElementById(id).style.display = 'none';
    document.querySelector(".main-content").classList.remove("overlay-active");
    document.getElementById("spinner").style.display = 'none'; // 關閉 spinner（如果有）
}

function closebutton(elementId) {
    // 確保傳入的 elementId 有效，並且元素存在
    document.getElementById(elementId).style.display = 'none';
   
    // 確保 .main-content 元素存在
    var mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.classList.remove("overlay-active");
    }
}
