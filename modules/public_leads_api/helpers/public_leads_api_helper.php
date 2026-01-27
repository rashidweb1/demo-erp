<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Default lead column list used for direct mapping.
 */
function public_leads_api_lead_fields(): array
{
    return [
        'name', 'title', 'company', 'email', 'website', 'phonenumber',
        'address', 'city', 'state', 'zip', 'country', 'description',
        'assigned', 'status', 'source', 'lead_value', 'tags', 'default_language',
    ];
}

/**
 * Numeric fields that should fall back to 0 instead of "-".
 */
function public_leads_api_numeric_fields(): array
{
    return ['assigned', 'status', 'source', 'country', 'lead_value'];
}

/**
 * Return sanitized string (arrays are JSON encoded).
 */
function public_leads_api_sanitize($value)
{
    if (is_array($value) || is_object($value)) {
        return json_encode($value);
    }

    $value = trim((string) $value);
    $value = strip_tags($value);

    return $value === '' ? '-' : $value;
}

/**
 * Provide safe default for empty values.
 */
function public_leads_api_default($field, $value)
{
    $value = is_null($value) ? '' : $value;

    if ($value === '') {
        return in_array($field, public_leads_api_numeric_fields(), true) ? 0 : '-';
    }

    return public_leads_api_sanitize($value);
}

/**
 * Convert a snake/slug key to Title Case label.
 */
function public_leads_api_label_from_key(string $key): string
{
    $key = str_replace(['_', '-'], ' ', $key);
    $key = preg_replace('/\s+/', ' ', $key);

    return ucwords(trim($key));
}

/**
 * Normalize incoming payload into associative array.
 */
function public_leads_api_read_payload(): array
{
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    $raw         = file_get_contents('php://input');
    $data        = [];

    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode($raw, true) ?: [];
    } else {
        $data = $_POST;
        if (!$data && !empty($raw)) {
            // Fallback: try JSON anyway
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }

    return is_array($data) ? $data : [];
}

/**
 * Build a compact preview string to store in logs.
 */
function public_leads_api_compact_payload(array $data, int $max = 500): string
{
    $json = json_encode($data);
    if (strlen($json) <= $max) {
        return $json;
    }

    return substr($json, 0, $max) . '...';
}
