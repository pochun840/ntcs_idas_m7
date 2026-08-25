<?php

require_once dirname(__DIR__) . '/app/config/paths.php';

final class LocalizationService
{
    public const DEFAULT_LANGUAGE = 'en-us';
    public const SUPPORTED_LANGUAGES = ['en-us', 'zh-tw', 'zh-cn'];

    public static function currentLanguage(): string
    {
        $language = strtolower((string)($_COOKIE['language'] ?? ($_SESSION['language'] ?? self::DEFAULT_LANGUAGE)));
        $language = preg_replace('/[^a-z0-9_-]/', '', $language);
        if ($language === 'en') $language = 'en-us';
        return in_array($language, self::SUPPORTED_LANGUAGES, true) ? $language : self::DEFAULT_LANGUAGE;
    }

    public static function languageFile(?string $language = null): string
    {
        $language = $language === null ? self::currentLanguage() : strtolower($language);
        if ($language === 'en') $language = 'en-us';
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) $language = self::DEFAULT_LANGUAGE;
        return IDAS_PATH_IDAS_ROOT . '/app/language/' . $language . '.php';
    }

    public static function load(): array
    {
        $text = [];
        $file = self::languageFile();
        if (is_file($file) && is_readable($file)) include $file;
        return is_array($text) ? $text : [];
    }
}
