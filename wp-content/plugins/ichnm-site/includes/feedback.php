<?php
/**
 * Feedback delivery seam (ticket 30).
 *
 * Validate POST → journal on local Docker contour; wp_mail on PHP host.
 * Recipient: constant ICHNM_FEEDBACK_TO, else option ichnm_feedback_to, else default.
 * Pure helpers mirror src/core/feedback.py (pytest without Docker).
 */

if (!defined('ABSPATH')) {
    exit;
}

const ICHNM_FEEDBACK_DEFAULT_TO = 'ichnm@ichnm.by';
const ICHNM_FEEDBACK_MAIL_SUBJECT = 'Обратная связь с сайта ИХНМ';

/**
 * Soft email check aligned with the Python seam (does not require WP is_email).
 */
function ichnm_feedback_looks_like_email(string $email): bool
{
    if ($email === '' || strlen($email) > 180) {
        return false;
    }
    return (bool) preg_match(
        "/^[A-Za-z0-9.!#$%&'*+\/=?^_`{|}~-]+@"
        . "[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?"
        . "(?:\\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)+$/",
        $email
    );
}

/**
 * @param array{name?:string,email?:string,message?:string} $raw
 * @return array{ok:true,name:string,email:string,message:string}|array{ok:false,error:string}
 */
function ichnm_feedback_validate(array $raw): array
{
    $name = isset($raw['name']) ? trim((string) $raw['name']) : '';
    $email = isset($raw['email']) ? strtolower(trim((string) $raw['email'])) : '';
    $message = isset($raw['message']) ? trim((string) $raw['message']) : '';

    if ($name === '' || strlen($name) > 120) {
        return ['ok' => false, 'error' => 'fields'];
    }
    if ($message === '' || strlen($message) > 5000) {
        return ['ok' => false, 'error' => 'fields'];
    }
    if (!ichnm_feedback_looks_like_email($email)) {
        return ['ok' => false, 'error' => 'fields'];
    }
    if (function_exists('is_email') && !is_email($email)) {
        return ['ok' => false, 'error' => 'fields'];
    }

    return [
        'ok' => true,
        'name' => $name,
        'email' => $email,
        'message' => $message,
    ];
}

/**
 * @param array{override?:string,environment?:string,home_url?:string} $ctx
 */
function ichnm_feedback_delivery_mode(array $ctx = []): string
{
    if (defined('ICHNM_FEEDBACK_DELIVERY')) {
        $override = strtolower(trim((string) ICHNM_FEEDBACK_DELIVERY));
        if ($override === 'journal' || $override === 'mail') {
            return $override;
        }
    }
    if (!empty($ctx['override'])) {
        $override = strtolower(trim((string) $ctx['override']));
        if ($override === 'journal' || $override === 'mail') {
            return $override;
        }
    }

    // Localhost home wins over WP's default "production" (typical on Docker).
    $home = isset($ctx['home_url'])
        ? strtolower((string) $ctx['home_url'])
        : (function_exists('home_url') ? strtolower((string) home_url('/')) : '');
    if ($home !== '' && (str_contains($home, 'localhost') || str_contains($home, '127.0.0.1'))) {
        return 'journal';
    }

    $env = isset($ctx['environment'])
        ? strtolower(trim((string) $ctx['environment']))
        : (function_exists('wp_get_environment_type') ? strtolower((string) wp_get_environment_type()) : '');
    if (in_array($env, ['local', 'development'], true)) {
        return 'journal';
    }
    if (in_array($env, ['production', 'staging'], true)) {
        return 'mail';
    }

    return 'mail';
}

/**
 * @param array{constant?:string,option?:string} $ctx
 */
function ichnm_feedback_recipient(array $ctx = []): string
{
    $candidates = [];
    if (defined('ICHNM_FEEDBACK_TO')) {
        $candidates[] = (string) ICHNM_FEEDBACK_TO;
    }
    if (array_key_exists('constant', $ctx)) {
        $candidates[] = (string) $ctx['constant'];
    }
    if (array_key_exists('option', $ctx)) {
        $candidates[] = (string) $ctx['option'];
    } elseif (function_exists('get_option')) {
        $candidates[] = (string) get_option('ichnm_feedback_to', '');
    }
    $candidates[] = ICHNM_FEEDBACK_DEFAULT_TO;

    foreach ($candidates as $candidate) {
        $email = strtolower(trim($candidate));
        if ($email === '') {
            continue;
        }
        if (ichnm_feedback_looks_like_email($email)) {
            if (function_exists('is_email') && !is_email($email)) {
                continue;
            }
            return $email;
        }
    }

    return ICHNM_FEEDBACK_DEFAULT_TO;
}

function ichnm_feedback_mail_body(string $name, string $email, string $message): string
{
    return "Имя: {$name}\nEmail: {$email}\n\n{$message}\n";
}

/**
 * Pure plan after validation (no I/O).
 *
 * @param array{ok:bool,name?:string,email?:string,message?:string,error?:string} $validated
 * @return array{ok:true,mode:string,action:string,recipient:string,subject:string,body:string,name:string,email:string,message:string}|array{ok:false,error:string}
 */
function ichnm_feedback_plan(array $validated, string $mode, string $recipient): array
{
    if (empty($validated['ok'])) {
        return ['ok' => false, 'error' => (string) ($validated['error'] ?? 'fields')];
    }
    $name = (string) $validated['name'];
    $email = (string) $validated['email'];
    $message = (string) $validated['message'];
    $to = ichnm_feedback_recipient(['constant' => $recipient]);
    $mode = $mode === 'mail' ? 'mail' : 'journal';

    return [
        'ok' => true,
        'mode' => $mode,
        'action' => $mode,
        'recipient' => $to,
        'subject' => ICHNM_FEEDBACK_MAIL_SUBJECT,
        'body' => ichnm_feedback_mail_body($name, $email, $message),
        'name' => $name,
        'email' => $email,
        'message' => $message,
    ];
}

/**
 * Persist one feedback entry in the WordPress options journal (local honesty path).
 *
 * @param array{name:string,email:string,message:string} $payload
 */
function ichnm_feedback_write_journal(array $payload, bool $mail_sent = false): void
{
    $body = ichnm_feedback_mail_body($payload['name'], $payload['email'], $payload['message']);
    if (function_exists('error_log')) {
        error_log('[ichnm_feedback] ' . str_replace("\n", ' | ', $body));
    }
    if (function_exists('update_option')) {
        update_option('ichnm_last_feedback', [
            'time' => function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s'),
            'name' => $payload['name'],
            'email' => $payload['email'],
            'message' => $payload['message'],
            'mail_sent' => $mail_sent,
            'mode' => $mail_sent ? 'mail' : 'journal',
        ], false);
    }
}

/**
 * Execute a prepared plan: journal XOR wp_mail.
 *
 * @param array{ok:true,mode:string,action:string,recipient:string,subject:string,body:string,name:string,email:string,message:string} $plan
 * @return array{ok:true,mode:string}|array{ok:false,error:string}
 */
function ichnm_feedback_execute(array $plan): array
{
    $payload = [
        'name' => $plan['name'],
        'email' => $plan['email'],
        'message' => $plan['message'],
    ];

    if ($plan['action'] === 'journal') {
        ichnm_feedback_write_journal($payload, false);
        return ['ok' => true, 'mode' => 'journal'];
    }

    $headers = ['Reply-To: ' . $plan['email']];
    $sent = function_exists('wp_mail')
        ? (bool) wp_mail($plan['recipient'], $plan['subject'], $plan['body'], $headers)
        : false;

    if (!$sent) {
        return ['ok' => false, 'error' => 'mail'];
    }

    ichnm_feedback_write_journal($payload, true);
    return ['ok' => true, 'mode' => 'mail'];
}
