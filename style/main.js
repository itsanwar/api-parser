/**
 * API Parser - Core UI Library
 * 
 * Provides shared utilities used across all pages:
 * - HTTP AJAX calls
 * - Toast/notification system (simple-notify)
 * - Loader animation
 */

window.onload = () => {
    loader();
};

// ============================================================
// AJAX HTTP Call
// ============================================================

let reqsArr = [];

/**
 * Perform an AJAX HTTP call.
 * 
 * @param {string}          link      URL to request
 * @param {string|object}   data      Request body data
 * @param {Function}        callback  Success callback function
 * @param {string|number}   method    HTTP method (default: 'get'). Pass 0 or '' for default.
 * @param {string}          datatype  Expected response type (default: 'html')
 * @param {object}          header    Custom headers object
 */
function http_call(link, data, callback, method = 'get', datatype = 'html', header = {}) {
    // Normalize method — allow passing 0 or '' to default to 'get'
    if (!method || method === 0) method = 'get';

    const request = $.ajax({
        url: link,
        type: method,
        data: data,
        headers: header,
        dataType: datatype,
        success: function (res) {
            reqsArr = reqsArr.filter(req => req !== request);
            callback(res);
        },
        error: function (xhr, status, error) {
            reqsArr = reqsArr.filter(req => req !== request);
            console.error(`HTTP ${method.toUpperCase()} ${link} failed:`, status, error);
            if (typeof sontam !== 'undefined') {
                sontam("error", "Request failed", error || status);
            }
        }
    });
    reqsArr.push(request);
}

// ============================================================
// Loader
// ============================================================

function loader() {
    window.scrollTo(0, 0);
    const loaderEl = document.querySelector('.loader');
    if (loaderEl) {
        loaderEl.classList.toggle('show');
        document.body.classList.toggle('stop');
    }
}

// ============================================================
// Notification (simple-notify)
// ============================================================

function sontam(type, title, msg, pos) {
    if (typeof Notify === 'undefined') {
        console.warn('[sontam]', type, title, msg);
        return;
    }
    new Notify({
        status: type,
        title: title,
        text: msg || "",
        effect: "slide",
        speed: 300,
        customClass: "",
        customIcon: "",
        showIcon: true,
        showCloseButton: true,
        autoclose: true,
        autotimeout: 2000,
        gap: 10,
        distance: 20,
        type: 1,
        position: pos || "right top"
    });
}