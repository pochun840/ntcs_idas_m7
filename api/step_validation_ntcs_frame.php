<?php
// Render the existing NTCS STEP editor solely as an off-screen validation host.
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
require_once dirname(__DIR__) . '/app/config/config.php';
if (idas_is_icontroller()) { http_response_code(409); exit('NTCS validator required'); }
// This view only renders fields; its save handler is never called.
$_SESSION = ['privilege' => 'admin'];
$data = [
    'type' => 'edit', 'mode' => 0, 'JOBID' => 1, 'SEQID' => 1, 'StepSelect' => 1,
    'next_step_id' => 1, 'step_torque_unit' => 1, 'torque_unit' => 'N.m',
    'step' => [],
    'tools_info' => array_fill_keys(['max_torque','tool_high_torque','min_torque','tool_low_torque','max_rpm','min_rpm','check_target_tor_lo','check_target_tor_hi','check_hi_tor_before','check_hi_tor_after','check_lo_tor_before','check_lo_tor_after','check_lo_rpm','check_hi_rpm'], 0),
];
foreach (['STEPname','StepOption','StepTorque','StepAngle','StepHiTorque','StepLoTorque','StepHiAngle','StepLoAngle','StepRPM','StepMoniByWin','StepLimiHi','StepLimiLo','StepTorqueTS','StepTorqueDownShift','StepRPMDownShift','StepTorqueOffset','StepEnableThreshold','StepEnableDownShift','StepEnableTorqueOffset','StepDirection'] as $field) $data['step'][$field] = 0;
$viewSource = (string)file_get_contents(dirname(__DIR__) . '/app/views/step/add_step.php');
preg_match_all('/\\$text\\[\\x27([^\\x27]+)\\x27\\]/', $viewSource, $found);
preg_match_all('/\$data\[\x27step\x27\]\[\x27([^\x27]+)\x27\]/', $viewSource, $stepFields);
foreach ($stepFields[1] as $key) if (!array_key_exists($key, $data['step'])) $data['step'][$key] = 0;
$text = [];
foreach ($found[1] as $key) $text[$key] = $key;
foreach (['N.m','kgf.cm','Lbf.in','kgf.m','cN.m'] as $unit) $text[$unit] = $unit;
$base = preg_replace('#/api/[^/]+$#', '', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/step_validation_ntcs_frame.php')) ?: '/idas';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><style>body{margin:0}</style>
<script src="<?php echo htmlspecialchars($base, ENT_QUOTES); ?>/public/js/jquery-3.7.1.min.js"></script>
<script src="<?php echo htmlspecialchars($base, ENT_QUOTES); ?>/public/js/alertify_min.js"></script>
<script>window.getCookie = window.getCookie || function (name) { var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : ''; };</script>
</head><body>
<?php require dirname(__DIR__) . '/app/views/step/add_step.php'; ?>
<script>
window.validateImportedNtcsStep = function (row, tool) {
  document.querySelectorAll('.is-invalid').forEach(function (el) {el.classList.remove('is-invalid');delete el.dataset.keepInvalid;});
  function set(id, value) {const el = document.getElementById(id);if (el) el.value = value == null ? '' : String(value);}
  const limits = tool.ranges && tool.ranges[row.step_unit];
  if (!limits) return {valid:false,errors:['step_unit']};
  const precision = limits.precision;
  const min = limits.min;
  const max = limits.max;
  const round = function (n) {return Number(n.toFixed(precision));};
  const values = {
    step_torque_unit:row.step_unit,check_target_tor_lo:round(min),check_target_tor_hi:round(max),
    check_hi_tor_before:round(max),check_hi_tor_after:limits.high,
    check_lo_tor_before:0,check_lo_tor_after:round(Number(row.StepTorque) - Math.pow(10,-precision)),
    check_lo_rpm:tool.min_rpm,check_hi_rpm:tool.max_rpm,
    tool_min_torque:round(min),tool_max_torque:round(max),tool_max_torque_diff:limits.high,
    tool_low_torque:0,tool_high_torque:limits.high,tool_min_rpm:tool.min_rpm,tool_max_rpm:tool.max_rpm
  };
  Object.keys(values).forEach(function (key) {set(key,values[key]);});
  Object.keys(row).forEach(function (key) {set(key,row[key]);});
  set('step_limit_hi_tor',row.StepLimiHi);set('step_limit_lo_tor',row.StepLimiLo);
  set('step_limit_hi_ang',row.StepLimiHi);set('step_limit_lo_ang',row.StepLimiLo);
  [['StepEnableThreshold',row.StepEnableThreshold],['StepEnableDownShift',row.StepEnableDownShift],['StepEnableTorqueOffset',row.StepEnableTorqueOffset]].forEach(function (entry) {
    document.querySelectorAll('input[name="' + entry[0] + '"]').forEach(function (el) {el.checked = el.value === String(entry[1]);});
  });
  const torqueWin = document.getElementById('StepMoniByWin_0'), angleWin = document.getElementById('StepMoniByWin_1');
  if (torqueWin) torqueWin.checked = Number(row.StepMoniByWin) === 2;
  if (angleWin) angleWin.checked = Number(row.StepMoniByWin) === 1;
  const minus = document.getElementById('join_offset_minus'), plus = document.getElementById('join_offset_plus');
  if (minus) minus.checked = Number(row.StepTorqueOffsetSign) === 45;
  if (plus) plus.checked = Number(row.StepTorqueOffsetSign) !== 45;
  if (typeof window.updateLabel === 'function') window.updateLabel();
  if (typeof window.input_check !== 'function') return {valid:false,errors:['NTCS input_check unavailable']};
  const result = window.input_check();
  const checked = result && typeof result === 'object' ? result : {valid:result === true,errors:result === true ? [] : ['STEP']};
  checked.details = {};
  (checked.errors || []).forEach(function (key) {
    const field = document.getElementById(key);
    const note = field && (field.parentElement?.querySelector('.invalid-feedback') || field.nextElementSibling);
    if (note && note.textContent.trim()) checked.details[key] = note.textContent.trim();
  });
  return checked;
};
</script>
</body></html>
