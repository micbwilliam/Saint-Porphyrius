<?php
/**
 * Saint Porphyrius - Form Guard
 *
 * Protects the inline-POST admin screens from creating the same record twice.
 *
 * Those screens handle their POST at the top of the template and then fall
 * through and re-render, so the browser is left sitting on the POST result:
 * a refresh, a PWA pull-to-refresh, or Back->Forward replays the same body.
 * WP nonces do not help -- wp_verify_nonce() accepts the same nonce for 12-24h.
 *
 * Two layers, the same pair SP_Points uses:
 *
 *   1. A single-use token minted per form render. It is handed to the write as
 *      a dedupe key, so the UNIQUE index on the target table -- not a
 *      check-then-act guard -- is what rejects the replay. That also closes the
 *      double-tap race, where two requests are in flight at once.
 *   2. redirect() after a *successful* write, so there is no POST result to
 *      replay in the first place. Failures deliberately keep rendering inline:
 *      redirecting there would throw away everything the admin typed.
 *
 * @package Saint_Porphyrius
 */

if (!defined('ABSPATH')) {
    exit;
}

class SP_Form_Guard {

    const FIELD = 'sp_form_token';

    /**
     * Hidden field carrying a fresh single-use token. Render it inside the form.
     */
    public static function token_field() {
        printf(
            '<input type="hidden" name="%s" value="%s">',
            esc_attr(self::FIELD),
            esc_attr(wp_generate_uuid4())
        );
    }

    /**
     * The token that came back with the current POST, or '' if there was none.
     */
    public static function submitted_token() {
        return sanitize_key($_POST[self::FIELD] ?? '');
    }

    /**
     * Dedupe key for this submission, or null when the form carried no token
     * (an older cached page). A null key never dedupes, which keeps the write
     * working rather than silently dropping it.
     */
    public static function dedupe_key($scope) {
        $token = self::submitted_token();

        return $token ? SP_Points::make_dedupe_key($scope, $token) : null;
    }

    /**
     * Redirect after a successful write so the POST cannot be replayed.
     * $notice is a short key the destination maps back to a message via notice().
     */
    public static function redirect($url, $notice = '', $type = 'success') {
        if ($notice) {
            $url = add_query_arg(
                array('sp_notice' => $notice, 'sp_notice_type' => $type),
                $url
            );
        }

        // The app screens are included by app-wrapper.php *after* it has printed the page
        // head, so there is always buffered output to throw away before we can send a
        // Location header. handle_app_routes() opens the buffer that makes this possible.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }

        // Last resort: something flushed before we got here. Still get the browser off the
        // POST result -- leaving it there is what lets a refresh replay the submission.
        $safe = wp_sanitize_redirect($url);
        printf(
            '<!DOCTYPE html><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=%s"><script>location.replace(%s);</script>',
            esc_attr($safe),
            wp_json_encode($safe)
        );
        exit;
    }

    /**
     * Read back a notice left by redirect(). $messages maps key => translated text,
     * so nothing user-facing ever travels through the URL.
     *
     * Returns array('message' => ..., 'type' => ...) or null.
     */
    public static function notice(array $messages) {
        $key = sanitize_key($_GET['sp_notice'] ?? '');

        if (!$key || !isset($messages[$key])) {
            return null;
        }

        $type = sanitize_key($_GET['sp_notice_type'] ?? 'success');

        return array(
            'message' => $messages[$key],
            'type'    => in_array($type, array('success', 'error', 'warning'), true) ? $type : 'success',
        );
    }
}
