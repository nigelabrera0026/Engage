document.addEventListener("DOMContentLoaded", function () {
    // Refresh Captcha
    window.refreshCaptcha = function () {
        var img = document.images['captcha_image'];
        img.src = img.src.substring(0, img.src.lastIndexOf("?")) + "?rand=" + Math.random() * 1000;
    }
});