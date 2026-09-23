(function () {
'use strict';
function formUtf8Bytes(str) {
    var bytes = [];
    str = String(str || '');

    for (var i = 0; i < str.length; i++) {
        var c = str.charCodeAt(i);

        if (c < 0x80) {
            bytes.push(c);
        } else if (c < 0x800) {
            bytes.push(0xC0 | (c >> 6));
            bytes.push(0x80 | (c & 0x3F));
        } else if (c >= 0xD800 && c <= 0xDBFF) {
            i++;
            var c2 = str.charCodeAt(i);
            var code = 0x10000 + (((c & 0x3FF) << 10) | (c2 & 0x3FF));
            bytes.push(0xF0 | (code >> 18));
            bytes.push(0x80 | ((code >> 12) & 0x3F));
            bytes.push(0x80 | ((code >> 6) & 0x3F));
            bytes.push(0x80 | (code & 0x3F));
        } else {
            bytes.push(0xE0 | (c >> 12));
            bytes.push(0x80 | ((c >> 6) & 0x3F));
            bytes.push(0x80 | (c & 0x3F));
        }
    }

    return bytes;
}

function formQrBitLength(value) {
    var len = 0;
    while (value !== 0) {
        len++;
        value >>>= 1;
    }
    return len;
}

function formQrCreateData(payload) {
    var dataCodewords = 55; // QR Version 3-L
    var bytes = formUtf8Bytes(payload);

    if (bytes.length > 53) {
        throw new Error('QR URL is too long');
    }

    var bits = [];

    function put(num, length) {
        for (var i = length - 1; i >= 0; i--) {
            bits.push(((num >>> i) & 1) === 1);
        }
    }

    // Byte mode
    put(0x4, 4);
    put(bytes.length, 8);

    for (var b = 0; b < bytes.length; b++) {
        put(bytes[b], 8);
    }

    // Terminator
    var capacityBits = dataCodewords * 8;
    var remaining = capacityBits - bits.length;
    put(0, Math.min(4, remaining));

    while (bits.length % 8 !== 0) {
        bits.push(false);
    }

    var data = [];
    for (var i = 0; i < bits.length; i += 8) {
        var val = 0;
        for (var j = 0; j < 8; j++) {
            val = (val << 1) | (bits[i + j] ? 1 : 0);
        }
        data.push(val);
    }

    var padBytes = [0xEC, 0x11];
    var padIndex = 0;

    while (data.length < dataCodewords) {
        data.push(padBytes[padIndex % 2]);
        padIndex++;
    }

    return data;
}

function formQrRsEcc(data) {
    var ecCount = 15; // QR Version 3-L, one block
    var exp = new Array(512);
    var log = new Array(256);

    var x = 1;
    for (var i = 0; i < 255; i++) {
        exp[i] = x;
        log[x] = i;
        x <<= 1;
        if (x & 0x100) x ^= 0x11D;
    }

    for (var i2 = 255; i2 < 512; i2++) {
        exp[i2] = exp[i2 - 255];
    }

    function mul(a, b) {
        if (a === 0 || b === 0) return 0;
        return exp[log[a] + log[b]];
    }

    var gen = [1];
    for (var g = 0; g < ecCount; g++) {
        var next = new Array(gen.length + 1).fill(0);

        for (var n = 0; n < gen.length; n++) {
            next[n] ^= gen[n];
            next[n + 1] ^= mul(gen[n], exp[g]);
        }

        gen = next;
    }

    var msg = data.slice();
    for (var z = 0; z < ecCount; z++) msg.push(0);

    for (var m = 0; m < data.length; m++) {
        var coef = msg[m];
        if (coef !== 0) {
            for (var k = 0; k < gen.length; k++) {
                msg[m + k] ^= mul(gen[k], coef);
            }
        }
    }

    return msg.slice(data.length);
}

function formQrMakeMatrix(payload) {
    var version = 3;
    var size = 29;
    var data = formQrCreateData(payload);
    var ecc = formQrRsEcc(data);
    var codewords = data.concat(ecc);

    function makeBase() {
        var modules = [];
        var reserved = [];

        for (var r = 0; r < size; r++) {
            modules[r] = [];
            reserved[r] = [];

            for (var c = 0; c < size; c++) {
                modules[r][c] = false;
                reserved[r][c] = false;
            }
        }

        function setModule(r, c, dark, res) {
            if (r < 0 || c < 0 || r >= size || c >= size) return;
            modules[r][c] = !!dark;
            if (res) reserved[r][c] = true;
        }

        function finder(row, col) {
            for (var dr = -1; dr <= 7; dr++) {
                for (var dc = -1; dc <= 7; dc++) {
                    var rr = row + dr;
                    var cc = col + dc;

                    if (rr < 0 || cc < 0 || rr >= size || cc >= size) continue;

                    var inFinder = dr >= 0 && dr <= 6 && dc >= 0 && dc <= 6;
                    var dark = false;

                    if (inFinder) {
                        dark = dr === 0 || dr === 6 || dc === 0 || dc === 6 ||
                            (dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4);
                    }

                    setModule(rr, cc, dark, true);
                }
            }
        }

        finder(0, 0);
        finder(size - 7, 0);
        finder(0, size - 7);

        // Alignment pattern for version 3 at (22,22)
        var ar = 22;
        var ac = 22;
        for (var adr = -2; adr <= 2; adr++) {
            for (var adc = -2; adc <= 2; adc++) {
                var dist = Math.max(Math.abs(adr), Math.abs(adc));
                setModule(ar + adr, ac + adc, dist !== 1, true);
            }
        }

        // Timing patterns
        for (var i = 8; i < size - 8; i++) {
            var darkTiming = i % 2 === 0;
            setModule(6, i, darkTiming, true);
            setModule(i, 6, darkTiming, true);
        }

        // Dark module
        setModule(4 * version + 9, 8, true, true);

        // Reserve format info areas
        for (var f = 0; f < 9; f++) {
            if (f !== 6) {
                reserved[8][f] = true;
                reserved[f][8] = true;
            }
        }

        for (var f2 = 0; f2 < 8; f2++) {
            reserved[8][size - 1 - f2] = true;
            reserved[size - 1 - f2][8] = true;
        }

        return {
            modules: modules,
            reserved: reserved
        };
    }

    function putData(base) {
        var modules = base.modules;
        var reserved = base.reserved;
        var bits = [];

        for (var i = 0; i < codewords.length; i++) {
            for (var b = 7; b >= 0; b--) {
                bits.push(((codewords[i] >>> b) & 1) === 1);
            }
        }

        var bitIndex = 0;
        var upward = true;

        for (var col = size - 1; col > 0; col -= 2) {
            if (col === 6) col--;

            for (var rowOffset = 0; rowOffset < size; rowOffset++) {
                var row = upward ? size - 1 - rowOffset : rowOffset;

                for (var c = 0; c < 2; c++) {
                    var cc = col - c;

                    if (!reserved[row][cc]) {
                        modules[row][cc] = bitIndex < bits.length ? bits[bitIndex] : false;
                        bitIndex++;
                    }
                }
            }

            upward = !upward;
        }
    }

    function maskBit(mask, r, c) {
        switch (mask) {
            case 0: return (r + c) % 2 === 0;
            case 1: return r % 2 === 0;
            case 2: return c % 3 === 0;
            case 3: return (r + c) % 3 === 0;
            case 4: return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0;
            case 5: return ((r * c) % 2 + (r * c) % 3) === 0;
            case 6: return (((r * c) % 2 + (r * c) % 3) % 2) === 0;
            case 7: return (((r + c) % 2 + (r * c) % 3) % 2) === 0;
            default: return false;
        }
    }

    function applyMask(base, mask) {
        var modules = base.modules;
        var reserved = base.reserved;

        for (var r = 0; r < size; r++) {
            for (var c = 0; c < size; c++) {
                if (!reserved[r][c] && maskBit(mask, r, c)) {
                    modules[r][c] = !modules[r][c];
                }
            }
        }
    }

    function bchFormat(data) {
        var value = data << 10;
        var generator = 0x537;

        while (formQrBitLength(value) - formQrBitLength(generator) >= 0) {
            value ^= generator << (formQrBitLength(value) - formQrBitLength(generator));
        }

        return ((data << 10) | value) ^ 0x5412;
    }

    function putFormat(base, mask) {
        var modules = base.modules;
        var dataBits = (1 << 3) | mask; // Error correction L = 01
        var bits = bchFormat(dataBits);

        for (var i = 0; i < 15; i++) {
            var dark = ((bits >> i) & 1) === 1;

            if (i < 6) modules[i][8] = dark;
            else if (i < 8) modules[i + 1][8] = dark;
            else modules[size - 15 + i][8] = dark;

            if (i < 8) modules[8][size - i - 1] = dark;
            else if (i < 9) modules[8][15 - i] = dark;
            else modules[8][15 - i - 1] = dark;
        }

        modules[size - 8][8] = true;
    }

    function penalty(modules) {
        var p = 0;

        // N1
        for (var r = 0; r < size; r++) {
            var runColor = modules[r][0];
            var runLen = 1;

            for (var c = 1; c < size; c++) {
                if (modules[r][c] === runColor) {
                    runLen++;
                } else {
                    if (runLen >= 5) p += 3 + (runLen - 5);
                    runColor = modules[r][c];
                    runLen = 1;
                }
            }
            if (runLen >= 5) p += 3 + (runLen - 5);
        }

        for (var c2 = 0; c2 < size; c2++) {
            var runColor2 = modules[0][c2];
            var runLen2 = 1;

            for (var r2 = 1; r2 < size; r2++) {
                if (modules[r2][c2] === runColor2) {
                    runLen2++;
                } else {
                    if (runLen2 >= 5) p += 3 + (runLen2 - 5);
                    runColor2 = modules[r2][c2];
                    runLen2 = 1;
                }
            }
            if (runLen2 >= 5) p += 3 + (runLen2 - 5);
        }

        // N2
        for (var r3 = 0; r3 < size - 1; r3++) {
            for (var c3 = 0; c3 < size - 1; c3++) {
                var count = 0;
                if (modules[r3][c3]) count++;
                if (modules[r3 + 1][c3]) count++;
                if (modules[r3][c3 + 1]) count++;
                if (modules[r3 + 1][c3 + 1]) count++;
                if (count === 0 || count === 4) p += 3;
            }
        }

        // N3
        var pattern = [true, false, true, true, true, false, true, false, false, false, false];

        function hasPatternLine(line, index) {
            for (var k = 0; k < pattern.length; k++) {
                if (line[index + k] !== pattern[k]) return false;
            }
            return true;
        }

        for (var r4 = 0; r4 < size; r4++) {
            for (var c4 = 0; c4 <= size - 11; c4++) {
                if (hasPatternLine(modules[r4], c4)) p += 40;
            }
        }

        for (var c5 = 0; c5 < size; c5++) {
            var line = [];
            for (var r5 = 0; r5 < size; r5++) line.push(modules[r5][c5]);

            for (var i5 = 0; i5 <= size - 11; i5++) {
                if (hasPatternLine(line, i5)) p += 40;
            }
        }

        // N4
        var darkCount = 0;
        for (var rr = 0; rr < size; rr++) {
            for (var cc = 0; cc < size; cc++) {
                if (modules[rr][cc]) darkCount++;
            }
        }

        var ratio = Math.abs((darkCount * 100 / (size * size)) - 50);
        p += Math.floor(ratio / 5) * 10;

        return p;
    }

    var best = null;
    var bestPenalty = Infinity;

    for (var mask = 0; mask < 8; mask++) {
        var base = makeBase();
        putData(base);
        applyMask(base, mask);
        putFormat(base, mask);

        var score = penalty(base.modules);
        if (score < bestPenalty) {
            bestPenalty = score;
            best = base.modules;
        }
    }

    return best;
}

function formQrCanvas(payload) {
    var modules = formQrMakeMatrix(payload);
    var size = modules.length;
    var quiet = 4;
    var scale = 8;
    var canvasSize = (size + quiet * 2) * scale;

    var canvas = document.createElement('canvas');
    canvas.width = canvasSize;
    canvas.height = canvasSize;

    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvasSize, canvasSize);

    ctx.fillStyle = '#000000';
    for (var r = 0; r < size; r++) {
        for (var c = 0; c < size; c++) {
            if (modules[r][c]) {
                ctx.fillRect((c + quiet) * scale, (r + quiet) * scale, scale, scale);
            }
        }
    }

    return canvas;
}

var box = document.getElementById('formQrCanvas');
var link = document.getElementById('formQrLink');
if (!box || !link) return;
try {
    var url = new URL(link.getAttribute('href'), location.href).href;
    link.href = url;
    link.textContent = url;
    var canvas = formQrCanvas(url);
    canvas.setAttribute('aria-label', url);
    box.appendChild(canvas);
} catch (error) {
    box.textContent = error.message;
}
})();
