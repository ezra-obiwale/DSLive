// Automatically cancel unfinished ajax requests 
// when the user navigates elsewhere.
(function ($) {
    var xhrPool = [];
    $(document).ajaxSend(function (e, jqXHR, options) {
        xhrPool.push(jqXHR);
    });
    $(document).ajaxComplete(function (e, jqXHR, options) {
        xhrPool = $.grep(xhrPool, function (x) {
            return x != jqXHR;
        });
    });
    abortAjax = function () {
        $.each(xhrPool, function (idx, jqXHR) {
            jqXHR.abort();
        });
    };

    var oldbeforeunload = window.onbeforeunload;
    window.onbeforeunload = function () {
        var r = oldbeforeunload ? oldbeforeunload() : undefined;
        if (r == undefined) {
            // only cancel requests if there is no prompt to stay on the page
            // if there is a prompt, it will likely give the requests enough time to finish
            abort();
        }
        return r;
    };
})(jQuery);

