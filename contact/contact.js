$(function () {
    var $form = $("#contact-form");
    var $success = $("#form-success");
    var $btn = $form.find("button[type=submit]");
    var btnHtml = $btn.html();

    function nl2br(text) {
        return esc(text).replace(/\n/g, "<br>");
    }

    function formatDate(s) {
        var d = new Date(String(s).replace(" ", "T"));
        return isNaN(d) ? s : d.toLocaleString("en-PH", { dateStyle: "medium", timeStyle: "short" });
    }

    function renderHistory(list) {
        if (!list.length) {
            $("#history-list").html('<div class="history-empty">You haven\'t sent any messages yet.</div>');
            return;
        }
        $("#history-list").html(list.map(function (m) {
            var replied = m.status === "Replied";
            var html = '<div class="history-card">' +
                '<div class="history-top">' +
                '<span class="history-id">MSG-' + m.id + '</span>' +
                '<span class="badge-topic' + (m.topic === "Order Cancellation" ? " is-cancel" : "") + '">' + esc(m.topic) + "</span>" +
                '<span class="badge-status ' + (replied ? "replied" : "pending") + '">' + esc(m.status) + "</span>" +
                '<span class="history-date">Sent ' + formatDate(m.created_at) + "</span></div>";
            if (m.order_no) {
                html += '<p class="history-order">Order No: ' + esc(m.order_no) + "</p>";
            }
            html += '<p class="history-message">' + nl2br(m.message) + "</p>";
            if (replied) {
                html += '<div class="admin-reply-box"><small>REPLY FROM OWN THE LABEL · ' +
                    formatDate(m.replied_at) + "</small>" + nl2br(m.reply) + "</div>";
            } else {
                html += '<p class="waiting-note">Waiting for a reply from our team.</p>';
            }
            return html + "</div>";
        }).join(""));
    }

    function loadHistory() {
        fetch("../api/contact.php")
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; });
            })
            .then(function (r) {
                if (r.status === 401) {
                    $("#history-list").html('<div class="history-empty">Log in to see the messages you sent. <a href="../login/login.html"><u>Login</u></a></div>');
                } else if (r.ok) {
                    renderHistory(r.body);
                } else {
                    throw new Error();
                }
            })
            .catch(function () {
                $("#history-list").html('<div class="history-empty">Could not load your messages. Is XAMPP running?</div>');
            });
    }

    $("#topic").change(function () {
        $("#order-group").toggleClass("d-none", this.value !== "Order Cancellation");
    });

    var cancelNo = new URLSearchParams(location.search).get("cancel");
    if (cancelNo) {
        $("#topic option[value='Order Cancellation']").prop("selected", true);
        $("#topic").trigger("change");
        $("#order-no").val(cancelNo);
    }

    $form.submit(function (e) {
        e.preventDefault();
        $success.addClass("d-none");

        var data = {
            first_name: $.trim($("#first-name").val()),
            last_name: $.trim($("#last-name").val()),
            email: $.trim($("#email").val()),
            topic: $("#topic").val(),
            order_no: $.trim($("#order-no").val()),
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
        if (data.topic === "Order Cancellation" && !data.order_no) {
            showToast("Please enter your order number.");
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
                    $("#order-group").addClass("d-none");
                    loadHistory();
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

    loadHistory();
});