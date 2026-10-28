$(function () {

    var SHIPPING_FEE = 100;
    var FREE_SHIPPING_MIN = 2500;

    var PAYMENT_LABELS = {
        cod: "Cash on Delivery",
        gcash: "GCash",
        card: "Credit / Debit Card"
    };

    function shippingFor(subtotal) {
        return subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_FEE;
    }

    function shippingText(amount) {
        return amount === 0 ? "FREE" : formatPeso(amount);
    }

    function digitsOnly(value) {
        return String(value).replace(/\D/g, "");
    }


    (function cleanCart() {
        var cart = getCart();
        var valid = cart.filter(function (item) { return item.variantId; });
        if (valid.length !== cart.length) saveCart(valid);
    })();


    function render() {
        var cart = getCart();

        if (!cart.length) {
            $("#checkout-content").addClass("d-none");
            $("#empty-cart").removeClass("d-none");
            return;
        }
        $("#empty-cart").addClass("d-none");
        $("#checkout-content").removeClass("d-none");

        var html = cart.map(function (item, i) {
            return '<div class="summary-item">' +
                '<img src="' + esc(item.img) + '" alt="' + esc(item.name) + '">' +
                '<div class="summary-info">' +
                "<strong>" + esc(item.name) + "</strong>" +
                "<small>" + esc(itemLabel(item)) + "</small>" +
                "<small>" + formatPeso(item.price) + "</small>" +
                '<div class="qty-controls">' +
                '<button type="button" class="qty-minus" data-index="' + i + '" aria-label="Decrease quantity">&minus;</button>' +
                "<span>" + item.qty + "</span>" +
                '<button type="button" class="qty-plus" data-index="' + i + '" aria-label="Increase quantity">+</button>' +
                '<button type="button" class="remove" data-index="' + i + '">Remove</button>' +
                "</div></div>" +
                "<div>" + formatPeso(item.price * item.qty) + "</div>" +
                "</div>";
        }).join("");

        var subtotal = cartSubtotal();
        var shipping = shippingFor(subtotal);

        $("#summary-items").html(html);
        $("#subtotal").text(formatPeso(subtotal));
        $("#shipping").text(shippingText(shipping));
        $("#total").text(formatPeso(subtotal + shipping));
    }

    $("#summary-items").on("click", ".qty-plus", function () {
        changeQty(Number($(this).data("index")), 1);
        render();
    });
    $("#summary-items").on("click", ".qty-minus", function () {
        changeQty(Number($(this).data("index")), -1);
        render();
    });
    $("#summary-items").on("click", ".remove", function () {
        removeItem(Number($(this).data("index")));
        render();
    });


    $("input[name=payment]").change(function () {
        $(".pay-panel").addClass("d-none");
        $("#pay-" + this.value).removeClass("d-none");
        $(".pay-panel .form-control").removeClass("is-invalid");
        $("#pay-error").addClass("d-none");
    });


    $("#phone, #gcash-number").on("input", function () {
        this.value = digitsOnly(this.value).slice(0, 11);
    });
    $("#postal").on("input", function () {
        this.value = digitsOnly(this.value).slice(0, 4);
    });
    $("#card-number").on("input", function () {
        this.value = digitsOnly(this.value).slice(0, 16).replace(/(.{4})/g, "$1 ").trim();
    });
    $("#card-expiry").on("input", function () {
        var d = digitsOnly(this.value).slice(0, 4);
        this.value = d.length > 2 ? d.slice(0, 2) + "/" + d.slice(2) : d;
    });
    $("#card-cvv").on("input", function () {
        this.value = digitsOnly(this.value).slice(0, 3);
    });

    $("#checkout-form").on("input change", ".form-control", function () {
        $(this).removeClass("is-invalid");
    });


    function mark($el, ok) {
        $el.toggleClass("is-invalid", !ok);
        return ok;
    }

    function expiryOk(value) {
        var m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(value);
        if (!m) return false;
        var now = new Date();
        var year = 2000 + Number(m[2]);
        var month = Number(m[1]);
        return year > now.getFullYear() || (year === now.getFullYear() && month >= now.getMonth() + 1);
    }

    function validate() {
        var ok = true;
        var method = $("input[name=payment]:checked").val();

        ok = mark($("#full-name"), $.trim($("#full-name").val()).length >= 2) && ok;
        ok = mark($("#email"), /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test($.trim($("#email").val()))) && ok;
        ok = mark($("#phone"), /^09\d{9}$/.test($("#phone").val())) && ok;
        ok = mark($("#address"), $.trim($("#address").val()).length >= 5) && ok;
        ok = mark($("#city"), $.trim($("#city").val()).length >= 2) && ok;
        ok = mark($("#postal"), /^\d{4}$/.test($("#postal").val())) && ok;

        if (method === "gcash") {
            ok = mark($("#gcash-number"), /^09\d{9}$/.test($("#gcash-number").val())) && ok;
        }
        if (method === "card") {
            ok = mark($("#card-number"), digitsOnly($("#card-number").val()).length === 16) && ok;
            ok = mark($("#card-expiry"), expiryOk($("#card-expiry").val())) && ok;
            ok = mark($("#card-cvv"), /^\d{3}$/.test($("#card-cvv").val())) && ok;
        }
        return ok;
    }

    function showPayError(msg) {
        $("#pay-error").text(msg).removeClass("d-none");
    }

        function formatWhen(value) {
        var when = new Date(String(value).replace(" ", "T"));
        return isNaN(when) ? value : when.toLocaleString("en-PH", { dateStyle: "medium", timeStyle: "short" });
    }

    function showReceipt(o, isReprint) {
        if (!isReprint) clearCart();

        var c = o.customer;

        $("#r-orderno").text(o.order_no);
        $("#r-date").text(formatWhen(o.created_at));
        $("#r-status").text(o.status);
        $("#r-payment").text(PAYMENT_LABELS[o.payment_method] || o.payment_method);

        $("#r-customer").html(
            "<strong>" + esc(c.full_name) + "</strong><br>" +
            esc(c.email) + " &middot; " + esc(c.phone) + "<br>" +
            esc(c.address) + ", " + esc(c.city) + " " + esc(c.postal)
        );

        $("#r-items").html(o.items.map(function (it) {
            var label = it.size + (it.color ? ", " + it.color : "");
            return '<div class="summary-row"><span>' + esc(it.name) + " (" + esc(label) + ") &times; " + it.qty +
                "</span><span>" + formatPeso(it.price * it.qty) + "</span></div>";
        }).join(""));

        $("#r-subtotal").text(formatPeso(o.subtotal));
        $("#r-shipping").text(shippingText(o.shipping));
        $("#r-total").text(formatPeso(o.total));

        $("#checkout-content").addClass("d-none");
        $("#empty-cart").addClass("d-none");
        $("#order-history").addClass("d-none");
        $("#receipt").removeClass("d-none");
        $("html, body").animate({ scrollTop: 0 }, 200);
    }

    $("#checkout-form").submit(function (e) {
        e.preventDefault();
        $("#pay-error").addClass("d-none");

        if (!validate()) {
            var $first = $(".is-invalid").first();
            $("html, body").animate({ scrollTop: Math.max(0, $first.offset().top - 140) }, 200);
            return;
        }

        var cart = getCart();
        if (!cart.length) {
            render();
            return;
        }

        var payload = {
            full_name: $.trim($("#full-name").val()),
            email: $.trim($("#email").val()),
            phone: $("#phone").val(),
            address: $.trim($("#address").val()),
            city: $.trim($("#city").val()),
            postal: $("#postal").val(),
            payment: $("input[name=payment]:checked").val(),
            items: cart.map(function (item) {
                return { variantId: item.variantId, qty: item.qty };
            })
        };

        var $btn = $("#place-order").prop("disabled", true).text("PLACING ORDER...");
        function resetButton() {
            $btn.prop("disabled", false).text("PLACE ORDER");
        }

        fetch("../api/place_order.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { ok: res.ok, status: res.status, body: body };
                });
            })
            .then(function (r) {
                if (r.ok) {
                    showReceipt(r.body.order);
                } else if (r.status === 401) {
                    sessionStorage.setItem("otl_toast", "Please log in to place your order.");
                    location.href = "../login/login.html";
                } else {
                    showPayError(r.body.error || "Something went wrong. Please try again.");
                    resetButton();
                }
            })
            .catch(function () {
                showPayError("Cannot reach the server. Is XAMPP running?");
                resetButton();
            });
    });

    render();

    var historyOrders = [];

    function toReceipt(o) {
        return {
            order_no: o.order_no,
            status: o.status,
            created_at: o.created_at,
            payment_method: o.payment_method,
            customer: {
                full_name: o.full_name,
                email: o.email,
                phone: o.phone,
                address: o.address,
                city: o.city,
                postal: o.postal
            },
            items: o.items,
            subtotal: o.subtotal,
            shipping: o.shipping,
            total: o.total
        };
    }

    function loadHistory() {
        fetch("../api/my_orders.php")
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; });
            })
            .then(function (r) {
                if (r.status === 401) {
                    $("#order-history-list").html('<p class="text-muted">Log in to see your past orders. <a href="../login/login.html"><u>Login</u></a></p>');
                    return;
                }
                if (!r.ok) throw new Error();

                historyOrders = r.body;
                if (!historyOrders.length) {
                    $("#order-history-list").html('<p class="text-muted">You have no orders yet.</p>');
                    return;
                }

                var rows = historyOrders.map(function (o, i) {
                    var count = o.items.reduce(function (sum, it) { return sum + it.qty; }, 0);
                    return "<tr>" +
                        "<td><strong>" + esc(o.order_no) + "</strong></td>" +
                        "<td>" + esc(formatWhen(o.created_at)) + "</td>" +
                        "<td>" + count + (count === 1 ? " item" : " items") + "</td>" +
                        "<td>" + formatPeso(o.total) + "</td>" +
                        '<td><span class="order-status status-' + esc(o.status.toLowerCase()) + '">' + esc(o.status) + "</span></td>" +
                        '<td class="text-right order-actions">' +
                        '<button type="button" class="btn btn-outline-otl btn-sm view-invoice" data-index="' + i + '">View Invoice</button>' +
                        (o.can_cancel
                            ? ' <a class="btn btn-outline-otl btn-sm cancel-order" href="../contact/Contact.html?cancel=' + encodeURIComponent(o.order_no) + '#contact-form">Request Cancel</a>'
                            : "") +
                        "</td>" +
                        "</tr>";
                }).join("");

                $("#order-history-list").html(
                    '<div class="table-responsive"><table class="table order-table"><thead><tr>' +
                    "<th>Order No.</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th>" +
                    "</tr></thead><tbody>" + rows + "</tbody></table></div>"
                );
            })
            .catch(function () {
                $("#order-history-list").html('<p class="text-muted">Could not load your orders. Is XAMPP running?</p>');
            });
    }

    $("#order-history-list").on("click", ".view-invoice", function () {
        var o = historyOrders[Number($(this).data("index"))];
        if (o) showReceipt(toReceipt(o), true);
    });

    $("#back-to-orders").click(function () {
        location.hash = "order-history";
        location.reload();
    });

    loadHistory();

    fetch("../api/session.php")
        .then(function (res) { return res.json(); })
        .then(function (s) {
            if (!s.loggedIn) {
                showToast("Log in to place your order.");
            } else if (!$.trim($("#full-name").val())) {
                $("#full-name").val(s.name);
            }
        })
        .catch(function () { });
});