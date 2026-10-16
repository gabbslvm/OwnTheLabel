$(function () {
    var $form = $("#contact-form");
    var $success = $("#form-success");
    var $btn = $form.find("button[type=submit]");
    var btnHtml = $btn.html();

    $form.submit(function (e) {
        e.preventDefault();
        $success.addClass("d-none");

        var data = {
            first_name: $.trim($("#first-name").val()),
            last_name: $.trim($("#last-name").val()),
            email: $.trim($("#email").val()),
            message: $.trim($("#message").val())
        };

        if (!data.first_name || !data.last_name || !data.email || !data.message) {
            showToast("Please fill in all fields.");
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(data.email)) {
            showToast("Please enter a valid email address.");
            return;
        }

        $btn.prop("disabled", true).text("SENDING...");

        fetch("../api/contact.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, body: body }; });
            })
            .then(function (result) {
                if (result.ok) {
                    $success.removeClass("d-none");
                    $form[0].reset();
                } else {
                    showToast(result.body.error || "Something went wrong.");
                }
            })
            .catch(function () {
                showToast("Cannot reach the server. Is XAMPP running?");
            })
            .then(function () {
                $btn.prop("disabled", false).html(btnHtml);
            });
    });
});