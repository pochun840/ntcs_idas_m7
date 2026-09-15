<?php

declare(strict_types=1);

final class JobConfigErrorCodes
{
    public const READY = 'READY';
    public const WRITE_OK = 'WRITE_OK';
    public const PREVIEW_OK = 'PREVIEW_OK';
    public const SKIPPED_NOT_READY = 'SKIPPED_NOT_READY';

    public const METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    public const UNSUPPORTED_MEDIA_TYPE = 'UNSUPPORTED_MEDIA_TYPE';
    public const PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';
    public const EMPTY_BODY = 'EMPTY_BODY';
    public const INVALID_JSON = 'INVALID_JSON';
    public const UNSUPPORTED_API_VERSION = 'UNSUPPORTED_API_VERSION';
    public const INVALID_ACTION = 'INVALID_ACTION';
    public const TARGETS_REQUIRED = 'TARGETS_REQUIRED';
    public const DEFAULT_TARGET_UNAVAILABLE = 'DEFAULT_TARGET_UNAVAILABLE';
    public const INVALID_TARGETS = 'INVALID_TARGETS';
    public const CONFIG_REQUIRED = 'CONFIG_REQUIRED';
    public const NO_READY_TARGETS = 'NO_READY_TARGETS';

    public const NOT_SAME_SUBNET = 'NOT_SAME_SUBNET';
    public const REMOTE_NOT_SAME_SUBNET = 'REMOTE_NOT_SAME_SUBNET';
    public const TARGET_UNREACHABLE = 'TARGET_UNREACHABLE';
    public const NO_RESPONSE = 'NO_RESPONSE';
    public const NOT_NTCS7 = 'NOT_NTCS7';
    public const TARGET_NOT_READY = 'TARGET_NOT_READY';
    public const PROBE_INVALID_RESPONSE = 'PROBE_INVALID_RESPONSE';
    public const PREVIEW_FAILED = 'PREVIEW_FAILED';
    public const WRITE_INVALID_RESPONSE = 'WRITE_INVALID_RESPONSE';
    public const REMOTE_WRITE_FAILED = 'REMOTE_WRITE_FAILED';
    public const LOCAL_PREFLIGHT_FAILED = 'LOCAL_PREFLIGHT_FAILED';
    public const LOCAL_WRITE_FAILED = 'LOCAL_WRITE_FAILED';

    public const CONTROLLER_ROOT_NOT_FOUND = 'CONTROLLER_ROOT_NOT_FOUND';
    public const CONTROLLER_DATABASE_NOT_WRITABLE = 'CONTROLLER_DATABASE_NOT_WRITABLE';
    public const CONTROLLER_STATUS_UNAVAILABLE = 'CONTROLLER_STATUS_UNAVAILABLE';
    public const CONTROLLER_IN_USE = 'CONTROLLER_IN_USE';
    public const CONTROLLER_DATABASE_BUSY = 'CONTROLLER_DATABASE_BUSY';
    public const DATABASE_SCHEMA_MISMATCH = 'DATABASE_SCHEMA_MISMATCH';
    public const DATABASE_WRITE_REJECTED = 'DATABASE_WRITE_REJECTED';
    public const DATABASE_ERROR = 'DATABASE_ERROR';
    public const BACKUP_FAILED = 'BACKUP_FAILED';
    public const VERIFY_FAILED = 'VERIFY_FAILED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /** @return array<string,int> */
    public static function httpStatusMap(): array
    {
        return [
            self::METHOD_NOT_ALLOWED => 405,
            self::UNSUPPORTED_MEDIA_TYPE => 415,
            self::PAYLOAD_TOO_LARGE => 413,
            self::EMPTY_BODY => 400,
            self::INVALID_JSON => 400,
            self::UNSUPPORTED_API_VERSION => 400,
            self::INVALID_ACTION => 400,
            self::TARGETS_REQUIRED => 400,
            self::DEFAULT_TARGET_UNAVAILABLE => 409,
            self::INVALID_TARGETS => 400,
            self::CONFIG_REQUIRED => 400,
            self::NO_READY_TARGETS => 409,
            self::REMOTE_NOT_SAME_SUBNET => 403,
            self::CONTROLLER_IN_USE => 409,
            self::CONTROLLER_DATABASE_BUSY => 409,
            self::CONTROLLER_ROOT_NOT_FOUND => 503,
            self::CONTROLLER_DATABASE_NOT_WRITABLE => 503,
            self::CONTROLLER_STATUS_UNAVAILABLE => 502,
            self::DATABASE_SCHEMA_MISMATCH => 500,
            self::DATABASE_WRITE_REJECTED => 400,
            self::DATABASE_ERROR => 500,
            self::BACKUP_FAILED => 500,
            self::VERIFY_FAILED => 500,
            self::VALIDATION_ERROR => 400,
            self::INTERNAL_ERROR => 500,
        ];
    }
}
