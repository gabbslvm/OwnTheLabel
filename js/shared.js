

/*HELPERS*/
// Makes text safe to put inside HTML
function esc(text) {
    return String(text).replace(/[&<>"']/g, function (ch) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
}

// 1299 -> "₱1,299"
function formatPeso(amount) {
    return "₱" + Number(amount).toLocaleString("en-PH");
}

/*CART*/
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

// Two cart lines are the same item if id, size and color match
function sameItem(a, b) {
    return String(a.id) === String(b.id) &&
        a.size === b.size &&
        (a.color || "") === (b.color || "");
}

function addToCart(item) {
    var cart = getCart();
    var found = cart.find(function (line) { return sameItem(line, item); });
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
    return getCart().reduce(function (sum, item) { return sum + item.qty; }, 0);
}

function cartSubtotal() {
    return getCart().reduce(function (sum, item) { return sum + item.price * item.qty; }, 0);
}

function itemLabel(item) {
    return item.size + (item.color ? ", " + item.color : "");
}

// Shows the number of items in every ".cart-count" (the Checkout button)
function updateCartCount() {
    $(".cart-count").text(cartCount());
}

$(updateCartCount);