<div id="Upload_Setting" class="divMode" style="display:none;background-color:none; overflow: visible">
    <div class="col t1" style="padding-left: 1%;font-weight: bold; padding-top: 1%;">
        <?php echo $text['upload_image_setting']; ?>
    </div>

    <div class="table-container" id="ImageTable_Setting">
        <div class="toolbar">
            <label>
                <input type="checkbox" id="selectAll">
            </label>
            <button type="button" id="addBtn"><?php echo $text['add_img']; ?></button>
            <button type="button" id="deleteBtn"><?php echo $text['Delete']; ?></button>
        </div>

        <div id="ImageContainer">
            <table id="imageTable" class="image-table">
                <thead>
                    <tr>
                        <th><?php echo $text['select']; ?></th>
                        <th><?php echo $text['img_name']; ?></th>
                        <th><?php echo $text['prev_name']; ?></th>
                    </tr>
                </thead>
                <tbody id="imageTableBody">
                    <!-- 由 JS 動態載入 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Image -->
    <div class="Upload-Display" id="Upload_Display" style="display:none;">
        <div class="col t1" style="padding-left: 4%; padding-top: 1%">
            <?php echo $text['upload_image']; ?>:
        </div>

        <div class="Upload-container">
            <p><?php echo $text['img_note']; ?></p>

            <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
                <div class="dropzone" id="dropzone">
                    <p><?php echo $text['click_text']; ?></p>
                    <input type="file" id="inputFiles" name="images[]" multiple accept="image/*" hidden>
                </div>

                <div id="preview-wrapper">
                    <p class="preview-title"><?php echo $text['prev_name']; ?> :</p>
                    <div id="preview"></div>
                </div>

                <div style="text-align: center;margin-top: 30px; margin-bottom:10px">
                    <button
                        type="submit"
                        class="all-btn w3-button w3-border w3-round-large"
                        style="margin-right:20px;margin-top: 20px;"
                    >
                        <?php echo $text['upload']; ?>
                    </button>

                    <button
                        type="button"
                        class="all-btn w3-button w3-border w3-round-large"
                        style="margin-top: 20px;"
                        onclick="closeUpload()"
                    >
                        <?php echo $text['cancel']; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // DOM 物件
    // =========================
    const selectAll = document.getElementById('selectAll');
    const addBtn = document.getElementById('addBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const uploadDisplay = document.getElementById('Upload_Display');
    const imageTableSetting = document.getElementById('ImageTable_Setting');
    const imageTableBody = document.getElementById('imageTableBody');

    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('inputFiles');
    const preview = document.getElementById('preview');
    const uploadForm = document.getElementById('uploadForm');

    // =========================
    // API 路徑
    // =========================
    const listUrl   = '/idas/public/?url=Settings/get_seq_images';
    const showUrl   = '/idas/public/?url=Settings/show_seq_image&name=';
    const uploadUrl = '/idas/public/?url=Settings/upload_seq_images';
    const deleteUrl = '/idas/public/?url=Settings/delete_seq_images';

    // =========================
    // 設定
    // =========================
    const maxFiles = 6;
    const maxTotalImages = 300;
    let currentImageCount = 0;
    let selectedFiles = [];

    // =========================
    // 多語系 / 預設文案
    // =========================
    const msg = {
        noImage: '<?php echo addslashes($text["img_no_image"] ?? "No image"); ?>',
        loadFailed: '<?php echo addslashes($text["img_load_failed"] ?? "Load failed"); ?>',
        noImageSelected: '<?php echo addslashes($text["img_no_image_selected"] ?? "No image selected"); ?>',
        deleteConfirm: '<?php echo addslashes($text["img_delete_confirm"] ?? "Delete selected images?"); ?>',
        deleteDone: '<?php echo addslashes($text["img_delete_done"] ?? "Delete done"); ?>',
        deleteFailed: '<?php echo addslashes($text["img_delete_failed"] ?? "Delete failed"); ?>',
        pleaseSelectImage: '<?php echo addslashes($text["img_please_select"] ?? "Please select image"); ?>',
        uploadDone: '<?php echo addslashes($text["img_upload_done"] ?? "Upload done"); ?>',
        uploadFailed: '<?php echo addslashes($text["img_upload_failed"] ?? "Upload failed"); ?>',
        imageLimitReached: '<?php echo addslashes($text["img_limit_reached"] ?? ""); ?>',
        imageLimitWillExceed: '<?php echo addslashes($text["img_limit_will_exceed"] ?? ""); ?>',
        ok: '<?php echo addslashes($text["OK"] ?? "OK"); ?>',
        cancel: '<?php echo addslashes($text["cancel"] ?? "Cancel"); ?>'
    };

    function getCookieValue(name) {
        const parts = document.cookie ? document.cookie.split(';') : [];
        const prefix = name + '=';
        for (let i = 0; i < parts.length; i++) {
            const item = parts[i].trim();
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

    function getUiLang() {
        const lang = String(getCookieValue('language') || getCookieValue('lang') || 'en-us')
            .toLowerCase()
            .replace('_', '-');

        if (lang === 'zh-tw' || lang === 'tw' || lang === 'zh-hant' || lang.indexOf('hant') !== -1) {
            return 'zh-tw';
        }
        if (lang === 'zh-cn' || lang === 'cn' || lang === 'zh-hans' || lang.indexOf('hans') !== -1) {
            return 'zh-cn';
        }
        return 'en-us';
    }

    const fallbackMsg = {
        'en-us': {
            imageLimitReached: 'The maximum number of images is 300. You cannot upload more images.',
            imageLimitWillExceed: 'The maximum number of images is 300. Please reduce the selected files.'
        },
        'zh-tw': {
            imageLimitReached: '圖片已達 300 張上限，無法再上傳。',
            imageLimitWillExceed: '圖片最多只能上傳 300 張，請減少選取的檔案。'
        },
        'zh-cn': {
            imageLimitReached: '图片已达 300 张上限，无法再上传。',
            imageLimitWillExceed: '图片最多只能上传 300 张，请减少选择的文件。'
        }
    };

    function getMsg(key) {
        if (msg[key]) return msg[key];
        const lang = getUiLang();
        return (fallbackMsg[lang] && fallbackMsg[lang][key]) ||
               (fallbackMsg['en-us'] && fallbackMsg['en-us'][key]) ||
               key;
    }

    function updateAddButtonState() {
        if (!addBtn) return;
        const disabled = currentImageCount >= maxTotalImages;
        addBtn.disabled = disabled;
        addBtn.style.opacity = disabled ? '0.5' : '1';
        addBtn.style.cursor = disabled ? 'not-allowed' : 'pointer';
        addBtn.title = disabled ? getMsg('imageLimitReached') : '';
    }

    // =========================
    // 工具：字串跳脫，避免 HTML 注入
    // =========================
    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[m];
        });
    }

    // =========================
    // 統一彈窗：優先 alertify
    // =========================
    function showInfo(message) {
        if (window.alertify) {
            alertify.alert(message);
        } else {
            alert(message);
        }
    }

    function showConfirm(message, onOk) {
        if (window.alertify) {
            alertify.confirm(
                message,
                function () {
                    if (typeof onOk === 'function') {
                        onOk();
                    }
                },
                function () {}
            ).set('labels', {
                ok: msg.ok,
                cancel: msg.cancel
            });
        } else {
            if (confirm(message)) {
                if (typeof onOk === 'function') {
                    onOk();
                }
            }
        }
    }

    // =========================
    // 關閉上傳區
    // =========================
    window.closeUpload = function () {
        uploadDisplay.style.display = 'none';
        imageTableSetting.style.display = 'block';

        selectedFiles = [];
        preview.innerHTML = '';
        input.value = '';
    };

    // =========================
    // 載入圖片清單
    // =========================
    function loadImageList() {
        fetch(listUrl, {
            method: 'GET'
        })
        .then(res => res.json())
        .then(data => {
            imageTableBody.innerHTML = '';
            selectAll.checked = false;

            currentImageCount = (data.result && Array.isArray(data.files)) ? data.files.length : 0;
            updateAddButtonState();

            if (!data.result || !Array.isArray(data.files) || data.files.length === 0) {
                imageTableBody.innerHTML = `
                    <tr>
                        <td colspan="3" style="text-align:center; padding: 20px;">${escapeHtml(msg.noImage)}</td>
                    </tr>
                `;
                return;
            }

            data.files.forEach(file => {
                const fileName = file.name || '';
                const fileUrl = file.url || (showUrl + encodeURIComponent(fileName));

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <input type="checkbox" class="imgCheck" value="${escapeHtml(fileName)}">
                    </td>
                    <td>${escapeHtml(fileName)}</td>
                    <td>
                        <img
                            src="${fileUrl}"
                            alt="${escapeHtml(fileName)}"
                            class="preview-img"
                            style="max-width:60px; max-height:60px; object-fit:contain;"
                        >
                    </td>
                `;
                imageTableBody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('載入圖片清單失敗:', err);
            imageTableBody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align:center; padding: 20px;">${escapeHtml(msg.loadFailed)}</td>
                </tr>
            `;
        });
    }

    // =========================
    // 全選 / 取消全選
    // =========================
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.imgCheck').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // =========================
    // 按下新增：切換到上傳區
    // =========================
    addBtn.addEventListener('click', function () {
        if (currentImageCount >= maxTotalImages) {
            showInfo(getMsg('imageLimitReached'));
            return;
        }

        uploadDisplay.style.display = 'block';
        imageTableSetting.style.display = 'none';
    });

    // =========================
    // 按下刪除：刪除勾選圖片
    // =========================
    deleteBtn.addEventListener('click', function () {
        const selected = Array.from(document.querySelectorAll('.imgCheck:checked')).map(cb => cb.value);

        if (selected.length === 0) {
            showInfo(msg.noImageSelected);
            return;
        }

        showConfirm(msg.deleteConfirm, function () {
            const formData = new FormData();
            selected.forEach(name => {
                formData.append('names[]', name);
            });

            fetch(deleteUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                showInfo(data.msg || msg.deleteDone);
                loadImageList();
            })
            .catch(err => {
                console.error('刪除圖片失敗:', err);
                showInfo(msg.deleteFailed);
            });
        });
    });

    // =========================
    // 顯示預覽
    // =========================
    function showPreview(files) {
        preview.innerHTML = '';

        const remainingSlots = Math.max(0, maxTotalImages - currentImageCount);
        if (remainingSlots <= 0) {
            selectedFiles = [];
            input.value = '';
            showInfo(getMsg('imageLimitReached'));
            return;
        }

        const allFiles = Array.from(files);
        const allowedCount = Math.min(maxFiles, remainingSlots);
        selectedFiles = allFiles.slice(0, allowedCount);

        if (allFiles.length > allowedCount) {
            showInfo(getMsg('imageLimitWillExceed'));
        }

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function (evt) {
                const div = document.createElement('div');
                div.className = 'item';

                div.innerHTML = `
                    <img src="${evt.target.result}" style="max-width:100px; max-height:100px; object-fit:contain;">
                    <button type="button" class="remove-btn">X</button>
                `;

                div.querySelector('.remove-btn').onclick = () => {
                    selectedFiles.splice(index, 1);
                    showPreview(selectedFiles);
                };

                preview.appendChild(div);
            };

            reader.readAsDataURL(file);
        });
    }

    // =========================
    // 點 dropzone 時開啟檔案選擇
    // =========================
    dropzone.addEventListener('click', function () {
        input.click();
    });

    // =========================
    // 選檔後預覽
    // =========================
    input.addEventListener('change', function () {
        showPreview(input.files);
    });

    // =========================
    // 拖曳進入
    // =========================
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
    });

    // =========================
    // 放開拖曳檔案
    // =========================
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();

        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            showPreview(e.dataTransfer.files);
        }
    });

    // =========================
    // 送出上傳
    // =========================
    uploadForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (selectedFiles.length === 0) {
            showInfo(msg.pleaseSelectImage);
            return;
        }

        if (currentImageCount >= maxTotalImages) {
            showInfo(getMsg('imageLimitReached'));
            return;
        }

        if (currentImageCount + selectedFiles.length > maxTotalImages) {
            showInfo(getMsg('imageLimitWillExceed'));
            return;
        }

        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('images[]', file);
        });

        fetch(uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            showInfo(data.msg || msg.uploadDone);

            if (data.result) {
                selectedFiles = [];
                preview.innerHTML = '';
                input.value = '';
                closeUpload();
                loadImageList();
            }
        })
        .catch(err => {
            console.error('上傳圖片失敗:', err);
            showInfo(msg.uploadFailed);
        });
    });

    // =========================
    // 初始化
    // =========================
    loadImageList();
    alertify.defaults.glossary.title = '';
});
</script>