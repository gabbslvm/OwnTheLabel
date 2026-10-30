function esc(text) {
    return String(text).replace(/[&<>"']/g, function (ch) {
    return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    }[ch];
    });
}

function formatPeso(amount) {
    return "₱" + Number(amount).toLocaleString("en-PH");
}

var CART_KEY = "otl_cart";

function getCart() {
    try {
        return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    } catch (e) {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCount();
}

function sameItem(a, b) {
    if (a.variantId && b.variantId) {
        return String(a.variantId) === String(b.variantId);
    }
    return (
        String(a.id) === String(b.id) &&
        a.size === b.size &&
        (a.color || "") === (b.color || "")
    );
}

function addToCart(item) {
    var cart = getCart();
    var found = cart.find(function (line) {
    return sameItem(line, item);
    });
    if (found) {
    found.qty += 1;
    } else {
        item.qty = 1;
        cart.push(item);
    }
    saveCart(cart);
}

function changeQty(index, change) {
    var cart = getCart();
    if (!cart[index]) return;
    cart[index].qty = Math.max(1, cart[index].qty + change);
    saveCart(cart);
}

function removeItem(index) {
    var cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
}

function clearCart() {
    saveCart([]);
}

function cartCount() {
    return getCart().reduce(function (sum, item) {
        return sum + item.qty;
    }, 0);
}

function cartSubtotal() {
    return getCart().reduce(function (sum, item) {
        return sum + item.price * item.qty;
    }, 0);
}

function itemLabel(item) {
    return item.size + (item.color ? ", " + item.color : "");
}

function updateCartCount() {
    $(".cart-count").text(cartCount());
}

$(updateCartCount);

var SITE_ROOT = document.currentScript.src.replace("js/shared.js", "");

function loadAuthLinks() {
    var nav = document.querySelector("#navbarNav .navbar-nav");
    if (!nav) return;

fetch(SITE_ROOT + "api/session.php")
    .then(function (res) {
    return res.json();
    })
    .then(function (s) {
    var html = "";
    if (s.loggedIn) {
        if (s.role !== "admin") {
        html +=
            '<li class="nav-item"><a class="nav-link" href="' + SITE_ROOT + 'checkout/checkout.html#order-history">My Orders</a></li>';
        }
        html +=
        '<li class="nav-item"><a class="nav-link" href="#" id="logout-link">Logout (' +
        esc(s.name) +
        ")</a></li>";
    } else {
        var onLogin = /\/login\//.test(location.pathname);
        html =
        '<li class="nav-item"><a class="nav-link' + (onLogin ? " active" : "") + '" href="' +
        SITE_ROOT +
        'login/login.html">Login</a></li>';
    }
    var cartItem = nav.querySelector(".nav-cart");
    if (cartItem) {
        cartItem.insertAdjacentHTML("beforebegin", html);
    } else {
        nav.insertAdjacentHTML("beforeend", html);
    }

    var out = document.getElementById("logout-link");
    if (out) {
        out.addEventListener("click", function (e) {
        e.preventDefault();
        fetch(SITE_ROOT + "api/logout.php").then(function () {
            sessionStorage.setItem(
            "otl_toast",
            "You have been logged out successfully.",
            );
            location.reload();
        });
        });
    }
    });
}

$(loadAuthLinks);

(function () {
    var style = document.createElement("style");
    style.textContent =
    ".otl-toast{position:fixed;top:90px;left:50%;transform:translate(-50%,-20px);" +
    "background:#080808;color:#F7F7F5;padding:14px 28px;font-family:'Inter',sans-serif;font-size:.8rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;" +
    "z-index:2000;opacity:0;transition:.4s ease;box-shadow:0 10px 30px rgba(0,0,0,.25);}" +
    ".otl-toast.show{opacity:1;transform:translate(-50%,0);}";
    document.head.appendChild(style);
})();

function showToast(message) {
    var toast = document.createElement("div");
    toast.className = "otl-toast";
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(function () {
    toast.classList.add("show");
    }, 50);
    setTimeout(function () {
    toast.classList.remove("show");
    setTimeout(function () {
        toast.remove();
    }, 400);
    }, 3000);
}

$(function () {
    var msg = sessionStorage.getItem("otl_toast");
    if (msg) {
    sessionStorage.removeItem("otl_toast");
    showToast(msg);
    }
});

/* ---------- Product rating (1 to 5 circles) ----------
   ratingHtml(product) builds the circles for any product card.
   Click a circle to rate; hover previews the rating.
   Filled black circles = your rating. Small text = average + count. */

function ratingSummary(avg, count) {
    if (!count) return "No ratings yet";
    return Number(avg).toFixed(1) + " (" + count + ")";
}

function ratingHtml(p) {
    var mine = Number(p.my_rating) || 0;
    var circles = "";
    for (var i = 1; i <= 5; i++) {
        circles += '<button type="button" class="otl-rate-dot' + (i <= mine ? " is-filled" : "") + '"' +
            ' data-value="' + i + '" role="radio" aria-checked="' + (i === mine) + '"' +
            ' aria-label="Rate ' + i + " out of 5\"></button>";
    }
    return '<div class="otl-rating" data-product-id="' + p.id + '" data-rating="' + mine + '">' +
        '<div class="otl-rate-dots" role="radiogroup" aria-label="Rate ' + esc(p.name) + '">' + circles + "</div>" +
        '<span class="otl-rate-summary">' + ratingSummary(p.avg_rating, p.rating_count) + "</span>" +
        "</div>";
}

function paintRating($box, value) {
    $box.find(".otl-rate-dot").each(function () {
        var v = Number($(this).data("value"));
        $(this).toggleClass("is-filled", v <= value).attr("aria-checked", v === value);
    });
}

$(document).on("mouseenter", ".otl-rate-dot", function () {
    var $box = $(this).closest(".otl-rating");
    var value = Number($(this).data("value"));
    $box.toggleClass("is-previewing", value !== Number($box.attr("data-rating")));
    paintRating($box, value);
});

$(document).on("mouseleave", ".otl-rate-dots", function () {
    var $box = $(this).closest(".otl-rating");
    $box.removeClass("is-previewing");
    paintRating($box, Number($box.attr("data-rating")));
});

$(document).on("click", ".otl-rate-dot", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $dot = $(this);
    var $box = $dot.closest(".otl-rating");
    if ($box.hasClass("is-saving")) return;

    var value = Number($dot.data("value"));
    var previous = Number($box.attr("data-rating"));

    $box.removeClass("is-previewing").addClass("is-saving").attr("data-rating", value);
    paintRating($box, value);

    fetch(SITE_ROOT + "api/rate.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ product_id: Number($box.data("product-id")), rating: value })
    })
        .then(function (res) {
            return res.json().then(function (data) { return { status: res.status, data: data }; });
        })
        .then(function (r) {
            if (r.status !== 200) {
                $box.attr("data-rating", previous);
                paintRating($box, previous);
                showToast(r.data.error || "Could not save your rating.");
                return;
            }
            $box.find(".otl-rate-summary").text(ratingSummary(r.data.avg_rating, r.data.rating_count));
            showToast("Thanks! You rated this " + value + "/5.");
        })
        .catch(function () {
            $box.attr("data-rating", previous);
            paintRating($box, previous);
            showToast("Could not save your rating.");
        })
        .then(function () {
            $box.removeClass("is-saving");
        });
});
