<?php

declare(strict_types=1);

/**
 * Maps existing ntcs_data tightening rows to 华荣接口2 payload.
 *
 * Fields that do not exist in ntcs_data (item/process_name/station/personal_num/
 * screw material & required quantity) must be supplied by the barcode/MES context.
 * No values are invented here.
 */
final class HuarongTighteningReportService
{
    public function buildPayload(array $rows, array $context = []): array
    {
        if ($rows === []) {
            throw new InvalidArgumentException('沒有可上報的鎖附結果');
        }

        $first = $rows[0];
        $sn = trim((string)($context['SN'] ?? $first['barcode'] ?? ''));

        if ($sn === '') {
            throw new InvalidArgumentException('SN 不能為空');
        }

        $runParams = [];
        foreach (array_reverse($rows) as $row) {
            $runParams[] = $this->mapRunParam($row);
        }

        $screwInf = $context['screw_inf'] ?? [];
        if (!is_array($screwInf)) {
            $screwInf = [];
        }

        return [
            'message_class' => 'post_screw_inf',
            'item' => (string)($context['item'] ?? ''),
            'SN' => $sn,
            'process_name' => (string)($context['process_name'] ?? ''),
            'station' => $context['station'] ?? '',
            'complete_staus' => isset($context['complete_staus'])
                ? (int)$context['complete_staus']
                : $this->deriveCompleteStatus($rows),
            'personal_num' => (string)($context['personal_num'] ?? ''),
            'screw_inf' => $screwInf,
            'runParam_inf' => $runParams,
        ];
    }

    private function mapRunParam(array $row): array
    {
        $targetTorque = $this->number($row['target_torque'] ?? 0);
        $finalTorque = $this->number($row['final_fasten_torque'] ?? 0);

        return [
            // Existing ntcs_data fields are used directly; no undocumented unit scaling.
            'Sp' => $finalTorque,
            'Rv' => $this->number($row['rpm'] ?? 0),
            'Ep' => $this->number($row['final_fasten_angle'] ?? 0),
            // No separate peak-torque field exists in the supplied API.
            // Use final torque as the available torque result instead of inventing a value.
            'Rl' => $finalTorque,
            'RlD' => $this->number($row['lo_torque'] ?? 0),
            'RlU' => $this->number($row['hi_torque'] ?? 0),
            'Er' => $targetTorque != 0
                ? round($finalTorque / $targetTorque, 6)
                : 0,
            'Tr' => $targetTorque,
            'ii' => 0,
            'ic' => $this->mapResultCode($row['fasten_status'] ?? null),
            'ie' => (int)($row['step_id'] ?? 1),
        ];
    }

    private function deriveCompleteStatus(array $rows): int
    {
        foreach ($rows as $row) {
            if ($this->mapResultCode($row['fasten_status'] ?? null) !== 4) {
                return 0;
            }
        }
        return 1;
    }

    private function mapResultCode($status): int
    {
        // Customer contract: 4=OK, 0=NG, 3=special.
        // Preserve these values if ntcs_data already stores them.
        if (is_numeric($status)) {
            $n = (int)$status;
            if (in_array($n, [0, 3, 4], true)) {
                return $n;
            }
            // Common controller convention: 1=OK.
            if ($n === 1) {
                return 4;
            }
            return 0;
        }

        $s = strtoupper(trim((string)$status));
        if (in_array($s, ['OK', 'PASS', 'SUCCESS'], true)) {
            return 4;
        }
        if (in_array($s, ['SPECIAL', 'BYPASS'], true)) {
            return 3;
        }
        return 0;
    }

    private function number($value)
    {
        if (!is_numeric($value)) {
            return 0;
        }

        $f = (float)$value;
        return floor($f) == $f ? (int)$f : $f;
    }
}
