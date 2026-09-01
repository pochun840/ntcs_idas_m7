<?php

final class DataExportService
{
    //'zh-tw' => ['fasten_direction' => '鎖附起子轉向', 'user_id' => '使用者 ID', 'job_cycle_time' => '工作週期時間'],
    //'zh-cn' => ['fasten_direction' => '锁附起子转向', 'user_id' => '使用者 ID', 'job_cycle_time' => '工作节拍时间'],
    //'en-us' => ['fasten_direction' => 'Screwdriver Direction', 'user_id' => 'User ID', 'job_cycle_time' => 'Job cycle time'],

    private const HEADER_FALLBACKS = [
        'zh-tw' => ['fasten_direction' => '鎖附起子轉向'],
        'zh-cn' => ['fasten_direction' => '锁附起子转向'],
        'en-us' => ['fasten_direction' => 'Screwdriver Direction'],
    ];

    public static function localizedHeaders(array $keys, array $text): array
    {
        $language = LocalizationService::currentLanguage();
        $result = [];
        foreach ($keys as $key) {
            $result[] = $text[$key]
                ?? self::HEADER_FALLBACKS[$language][$key]
                ?? (($key === 'fasten_direction' && isset($text['info_fasten_direction'])) ? $text['info_fasten_direction'] : $key);
        }
        return $result;
    }

    public static function orderedRow(array $row, array $keys, array $unitMap, array $statusMap, callable $unitText): array
    {
        $ordered = [];
        foreach ($keys as $key) {
            if ($key === 'torque_unit') {
                $unitKey = $unitMap[(int)($row[$key] ?? -1)] ?? '';
                $ordered[] = $unitText($unitKey);
            } elseif ($key === 'fasten_status') {
                $code = (int)($row[$key] ?? -1);
                $ordered[] = $statusMap[$code] ?? $code;
            } else {
                $ordered[] = $row[$key] ?? '';
            }
        }
        return array_map([self::class, 'safeCsvCell'], $ordered);
    }

    public static function safeCsvCell($value)
    {
        if (!is_string($value)) return $value;
        // Prevent spreadsheet applications from evaluating exported data as a formula.
        return preg_match('/^[=+\-@]/u', ltrim($value)) ? "'" . $value : $value;
    }
}
