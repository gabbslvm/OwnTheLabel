$(function () {
    $("#show-register").click(function (e) {
        e.preventDefault();
        $("#login-form").addClass("d-none");
        $("#register-form").removeClass("d-none");
        $("#auth-title").text("Register");
    });

    $("#show-login").click(function (e) {
        e.preventDefault();
        $("#register-form").addClass("d-none");
        $("#login-form").removeClass("d-none");
        $("#auth-title").text("Login");
    });

    function showError(box, msg) {
        box.text(msg).removeClass("d-none");
    }

    function redirectByRole(role) {
        location.href = role === "admin" ? "../admin/dashboard.php" : "../shop/shop.html";
    }

    function send(url, data, errorBox) {
        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, body: body }; });
            })
            .then(function (result) {
                if (result.ok) {
                    redirectByRole(result.body.role);
                } else {
                    showError(errorBox, result.body.error || "Something went wrong.");
                }
            })
            .catch(function () {
                showError(errorBox, "Cannot reach the server. Is XAMPP running?");
            });
    }

    $("#login-form").submit(function (e) {
        e.preventDefault();
        var errorBox = $("#login-error").addClass("d-none");
        send("../api/login.php", {
            email: $("#login-email").val(),
            password: $("#login-password").val()
        }, errorBox);
    });

    $("#register-form").submit(function (e) {
        e.preventDefault();
        var errorBox = $("#register-error").addClass("d-none");

        if ($("#reg-password").val() !== $("#reg-confirm").val()) {
            showError(errorBox, "Passwords do not match.");
            return;
        }
        send("../api/register.php", {
            name: $("#reg-name").val(),
            email: $("#reg-email").val(),
            password: $("#reg-password").val()
        }, errorBox);
    });
});