<?php
// Reuse the NTCS sequence editor's input_check_seq() in an isolated iframe.
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
require_once dirname(__DIR__) . '/app/config/config.php';
if (idas_is_icontroller()) { http_response_code(409); exit('NTCS validator required'); }
$data = ['type'=>'edit','torque_unit_code'=>1,'sequences'=>['Thread_Calcu'=>31]];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"></head><body>
<script>
window.getCookie = function (name) {const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));return m ? decodeURIComponent(m[1]) : '';};
window.__seqValidationMessage = '';
window.alertify = {defaults:{glossary:{}},alert:function (title,message) {window.__seqValidationMessage = String(message);return {set:function () {return this;}};}};
</script>
<?php require dirname(__DIR__) . '/app/views/sequences/add_seq_share.php'; ?>
<script>
(function () {
  const keys = ['SEQname','seq_repeat','timeout','dt_time','tt_time','ng_stop','unscrew_rpm','unscrew_torque_threshold','unscrew_angle_threshold','unscrew_force','total_angle_limit','total_angle_lower','tool_max_torque','tool_min_torque','tool_max_rpm','tool_min_rpm','seq_unit_code'];
  keys.forEach(function (key) {const input=document.createElement('input');input.id=key;document.body.appendChild(input);});
  ['unscrew_mode_auto','unscrew_mode_custom','unscrew_forcemode_on','unscrew_forcemode_unlimit','unscrew_forcemode_off'].forEach(function (key) {const input=document.createElement('input');input.id=key;input.type='radio';document.body.appendChild(input);});
  window.validateImportedNtcsSeq = function (row,tool) {
    keys.forEach(function (key) {const el=document.getElementById(key);el.value=row[key] == null ? '' : String(row[key]);el.classList.remove('is-invalid');});
    const range=tool && tool.ranges && tool.ranges[String(row.seq_unit)];
    if (!range) return {valid:false,errors:['seq_unit'],details:{}};
    document.getElementById('seq_unit_code').value=String(row.seq_unit);
    document.getElementById('tool_min_torque').value=range.min;
    document.getElementById('tool_max_torque').value=range.max;
    document.getElementById('tool_min_rpm').value=tool.min_rpm;
    document.getElementById('tool_max_rpm').value=tool.max_rpm;
    document.getElementById('unscrew_mode_auto').checked=Number(row.unscrew_mode)!==0;
    document.getElementById('unscrew_mode_custom').checked=Number(row.unscrew_mode)===0;
    document.getElementById('unscrew_forcemode_on').checked=Number(row.unscrew_force)>0 && Number(row.unscrew_force)<101;
    document.getElementById('unscrew_forcemode_unlimit').checked=Number(row.unscrew_force)>=101;
    document.getElementById('unscrew_forcemode_off').checked=Number(row.unscrew_force)===0;
    window.__seqValidationMessage='';
    if (typeof input_check_seq !== 'function') return {valid:false,errors:['SEQ'],details:{}};
    const valid=input_check_seq();
    const errors=keys.filter(function (key) {return document.getElementById(key).classList.contains('is-invalid');});
    const details={};
    if (errors.length && window.__seqValidationMessage) details[errors[0]]=window.__seqValidationMessage;
    return {valid:valid===true,errors:errors.length ? errors : valid===true ? [] : ['SEQ'],details:details};
  };
})();
</script></body></html>
