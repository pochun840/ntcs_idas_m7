<?php

final class ApiResponseService
{
    public static function payload(string $type, string $message, ?string $code = null, array $extra = []): array
    {
        $normalizedType = strtolower(trim($type));
        $failureTypes = ['error', 'fail', 'failed', 'ng', 'false', '0'];
        $success = !in_array($normalizedType, $failureTypes, true);
        return array_merge([
            'success' => $success,
            'code' => $code ?: ($success ? 'SUCCESS' : 'ERROR'),
            'message' => $message,
            // Legacy keys retained for existing JavaScript.
            'res_type' => $success ? 'Success' : 'Error',
            'res_msg' => $message,
        ], $extra);
    }

    public static function send(
        string $type,
        string $message,
        ?string $code = null,
        array $extra = [],
        bool $setJsonHeader = false
    ): void
    {
        // Legacy jQuery callbacks call JSON.parse(response).  Do not force the
        // JSON content type here or jQuery will pre-parse it into an object.
        if ($setJsonHeader && !headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(self::payload($type, $message, $code, $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
