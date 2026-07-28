/**
 * Saint Porphyrius - shared admin-ajax response reader.
 *
 * Loaded in the HEAD, not the footer, so the inline <script> blocks the app screens
 * print in the body can rely on it being defined by the time they execute.
 */

/**
 * Shared admin-ajax response reader.
 *
 * Screens used to read responses with a bare `r.json()` and no status check, then read
 * `resp.data.message` in the failure branch. That collapsed five unrelated failures into
 * one message:
 *
 *   - admin-ajax answers the bare string "0" when it never reached a handler, which
 *     happens both when the login has lapsed and when PHP threw away an oversized $_POST.
 *     JSON.parse("0") *succeeds* and returns the number 0, so reading .data.message off it
 *     threw a TypeError -- inside the .then, so it landed in the .catch;
 *   - "-1" for a failed referer check, same path;
 *   - HTML from a PHP fatal or a 502/504 gateway page, which rejects in r.json();
 *   - a real transport failure;
 *   - a success-shaped body whose payload was null.
 *
 * Every one of them surfaced as "حدث خطأ في الاتصال", which is why none of them were ever
 * diagnosable. This names each one. Ported out of the admin lesson wizard (6.4.6), where
 * it lived as a local copy.
 */
(function() {
    'use strict';

    // spAjaxStrings ships with THIS script, so it is always defined by the time an
    // inline body script can call in. spApp is localized onto main.js, which loads in
    // the footer -- fine as a fallback, but not something to depend on.
    function str(key, fallback) {
        var strings = window.spAjaxStrings || (window.spApp && window.spApp.strings) || {};
        return strings[key] || fallback;
    }

    window.spReadJson = function(response) {
        if (!response.ok) {
            if (response.status === 413) {
                throw new Error(str('requestTooBig', 'حجم البيانات كبير جدًا على الخادم'));
            }
            if (response.status === 401 || response.status === 403) {
                throw new Error(str('sessionLapsed', 'انتهت الجلسة، يرجى تحديث الصفحة'));
            }
            throw new Error(str('serverError', 'الخادم رد بخطأ') + ' (' + response.status + ')');
        }

        return response.text().then(function(text) {
            var body = (text || '').trim();

            if (body === '0') {
                throw new Error(str('notDelivered', 'لم يصل الطلب إلى الخادم'));
            }

            if (body === '-1') {
                throw new Error(str('sessionLapsed', 'انتهت الجلسة، يرجى تحديث الصفحة'));
            }

            try {
                return JSON.parse(body);
            } catch (e) {
                throw new Error(str('badResponse', 'رد غير متوقع من الخادم'));
            }
        });
    };

    /**
     * The server's own message when it sent one, otherwise the caller's fallback.
     * Tolerates a payload of 0/null, which is exactly what used to throw here.
     */
    window.spErrorMessage = function(payload, fallback) {
        if (payload && payload.data && payload.data.message) {
            return payload.data.message;
        }
        return fallback;
    };

    /**
     * fetch() with a deadline. Without one a stalled request hangs the UI forever --
     * there was no timeout anywhere in this codebase.
     */
    window.spFetch = function(url, options, timeoutMs) {
        options = options || {};

        if (typeof AbortController === 'undefined') {
            return fetch(url, options);
        }

        var controller = new AbortController();
        var timer = setTimeout(function() { controller.abort(); }, timeoutMs || 30000);

        options.signal = controller.signal;

        return fetch(url, options).then(function(response) {
            clearTimeout(timer);
            return response;
        }, function(error) {
            clearTimeout(timer);
            if (error && error.name === 'AbortError') {
                throw new Error(str('timedOut', 'استغرق الطلب وقتاً طويلاً'));
            }
            // A rejected fetch() is the only genuine "connection error" of the lot.
            throw new Error(str('offline', 'تعذّر الاتصال بالخادم'));
        });
    };
})();

