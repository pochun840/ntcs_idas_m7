<?php

declare(strict_types=1);

/** HuaRong MES API 2: one payload per completed product. */
final class HuarongTighteningReportService
{
    public function fetchProductRows(PDO $db, array $context): array
    {
        // Compare the controller's recipe name, not the MES process_name.
        // API 1 saves job_name from data.job.job_name after a successful Job Switch.
        $jobName = trim((string)($context['job_name'] ?? ''));
        if ($jobName === '') {
            throw new InvalidArgumentException('MES Context 缺少 job_name，請重新執行接口 1 掃碼以建立新版 Context');
        }
        $afterId = max((int)($context['baseline_result_id'] ?? 0), (int)($context['last_reported_result_id'] ?? 0));
        $stmt = $db->prepare('SELECT * FROM ntcs_data WHERE id > :after_id AND job_name = :job_name ORDER BY id ASC');
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->bindValue(':job_name', $jobName, PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sn = trim((string)($context['SN'] ?? ''));
        $batch = [];
        foreach ($rows as $row) {
            // Barcode can be blank in controller result rows; a different nonblank
            // barcode must never be included in this product's report.
            $barcode = trim((string)($row['barcode'] ?? ''));
            if ($barcode !== '' && $barcode !== $sn) {
                break;
            }
            $batch[] = $row;
            if ((int)($row['fasten_status'] ?? -1) === 6) { // OK-JOB
                break;
            }
        }
        return $batch;
    }

    public function isProductComplete(array $rows): bool
    {
        return $rows !== [] && (int)($rows[count($rows) - 1]['fasten_status'] ?? -1) === 6;
    }

    public function buildPayload(array $rows, array $context, bool $allowIncomplete = false): array
    {
        if ($rows === [] && !$allowIncomplete) {
            throw new InvalidArgumentException('沒有可上報的鎖附結果');
        }
        if (!$allowIncomplete && !$this->isProductComplete($rows)) {
            throw new InvalidArgumentException('產品尚未完成（等待 fasten_status=6）');
        }
        $sn = trim((string)($context['SN'] ?? ''));
        if ($sn === '') {
            throw new InvalidArgumentException('SN 不能為空');
        }
        foreach (['item', 'process_name', 'station'] as $field) {
            if (!isset($context[$field]) || trim((string)$context[$field]) === '') {
                throw new InvalidArgumentException('MES Context 缺少 ' . $field);
            }
        }
        $runParams = [];
        foreach ($rows as $row) {
            $ic = $this->mapResultCode($row);
            if ($ic === null) {
                // status 3 without tightening data and other undocumented statuses
                // are not tightening actions; don't invent MES result codes.
                continue;
            }
            $torque = $this->number($row['final_fasten_torque'] ?? 0);
            $runParams[] = [
                'Sp' => $torque,
                'Rv' => $this->number($row['rpm'] ?? 0),
                'Ep' => $this->number($row['final_fasten_angle'] ?? 0),
                'Rl' => $torque,
                'RlD' => $this->number($row['lo_torque'] ?? 0),
                'RlU' => $this->number($row['hi_torque'] ?? 0),
                'ii' => 0,
                'ic' => $ic,
                'ie' => 1,
            ];
        }
        if ($runParams === [] && !$allowIncomplete) {
            throw new InvalidArgumentException('沒有可上報的有效鎖附紀錄');
        }
        $screws = $context['screw_inf'] ?? [];
        if (!is_array($screws) || $screws === []) {
            throw new InvalidArgumentException('MES Context 缺少 screw_inf');
        }
        $last = $rows === [] ? [] : $rows[count($rows) - 1];
        $installed = (int)($last['total_screw_count'] ?? 0);
        $screwInfo = [];
        foreach ($screws as $screw) {
            if (!is_array($screw) || trim((string)($screw['screw_item'] ?? '')) === '') {
                throw new InvalidArgumentException('MES Context screw_item 不完整');
            }
            $screwInfo[] = [
                'screw_item' => (string)$screw['screw_item'],
                'screw_quantity' => (int)($screw['screw_quantity'] ?? 0),
                // NTCS total_screw_count is product-wide. Multiple screw items
                // cannot be split reliably without per-item controller data.
                'screw_installed' => $installed,
            ];
        }
        if (count($screwInfo) > 1) {
            throw new InvalidArgumentException('多種 screw_item 無法從單一 total_screw_count 判斷各自完成數量');
        }
        return [
            'message_class' => 'post_screw_inf',
            'item' => (string)$context['item'],
            'SN' => $sn,
            'process_name' => (string)$context['process_name'],
            'station' => is_numeric($context['station']) ? (int)$context['station'] : $context['station'],
            'complete_staus' => $this->isProductComplete($rows) ? 1 : 0,
            'personal_num' => '',
            'screw_inf' => $screwInfo,
            'runParam_inf' => $runParams,
        ];
    }

    private function mapResultCode(array $row): ?int
    {
        $status = (int)($row['fasten_status'] ?? -1);
        if (in_array($status, [4, 5, 6], true)) return 4;
        if (in_array($status, [7, 8], true)) return 0;
        if ($status === 3 && ((float)($row['step0_last_angle'] ?? 0) > 0 || (float)($row['step0_last_torque'] ?? 0) > 0)) return 3;
        return null;
    }

    private function number($value)
    {
        if (!is_numeric($value)) return 0;
        $f = (float)$value;
        return floor($f) == $f ? (int)$f : $f;
    }
}
