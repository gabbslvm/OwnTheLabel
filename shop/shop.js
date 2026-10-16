$(function () {
    var PER_PAGE = 12;
    var allProducts = [];
    var byId = {};
    var page = 1;

    function uniq(variants, key) {
        var out = [];
        variants.forEach(function (v) {
            if (out.indexOf(v[key]) === -1) out.push(v[key]);
        });
        return out;
    }

    function values(selector) {
        return $(selector + ":checked").map(function () { return this.value; }).get();
    }

    function getFilters() {
        var price = $("input[name=price]:checked");
        return {
            cats: values(".f-category"),
            sizes: values(".f-size"),
            min: Number(price.data("min")),
            max: Number(price.data("max"))
        };
    }

    function getVisibleProducts() {
        var f = getFilters();

        var list = allProducts.filter(function (p) {
            if (f.cats.length && f.cats.indexOf(p.category) === -1) return false;
            if (p.price < f.min || p.price > f.max) return false;

            if (f.sizes.length) {
                var match = p.variants.some(function (v) {
                    return v.stock > 0 && f.sizes.indexOf(v.size) !== -1;
                });
                if (!match) return false;
            }
            return true;
        });

        var sort = $("#sort-select").val();
        list.sort(function (a, b) {
            if (sort === "low") return a.price - b.price;
            if (sort === "high") return b.price - a.price;
            if (sort === "newest") return b.created_at.localeCompare(a.created_at) || b.id - a.id;
            return b.is_featured - a.is_featured || a.id - b.id;
        });
        return list;
    }

    function cardHtml(p) {
        var f = getFilters();
        var sizes = uniq(p.variants, "size");
        var colors = uniq(p.variants, "color");

                var wantedSize = sizes.filter(function (s) { return f.sizes.indexOf(s) !== -1; });
        var preSize = wantedSize.length ? wantedSize[0] : sizes[0];

        var sizeOpts = sizes.map(function (s) {
            return '<option value="' + esc(s) + '"' + (s === preSize ? " selected" : "") + ">" + esc(s) + "</option>";
        }).join("");

        var colorHtml = "";
        if (colors.length > 1) {
            colorHtml = '<select class="form-control form-control-sm color-select">' +
                colors.map(function (c) {
                    return '<option value="' + esc(c) + '">' + esc(c) + "</option>";
                }).join("") + "</select>";
        }

        return '<div class="col-6 col-md-4 col-xl-3 mb-4">' +
            '<div class="product-card" data-id="' + p.id + '">' +
            '<div class="product-img"><img src="../' + esc(p.image) + '" alt="' + esc(p.name) + '" loading="lazy"></div>' +
            '<div class="product-info">' +
            '<span class="product-cat">' + esc(p.category) + "</span>" +
            "<h3>" + esc(p.name) + "</h3>" +
            '<p class="product-price">' + formatPeso(p.price) + "</p>" +
            '<div class="product-options">' +
            '<select class="form-control form-control-sm size-select">' + sizeOpts + "</select>" +
            colorHtml +
            "</div>" +
            '<small class="stock-note"></small>' +
            '<button type="button" class="btn btn-otl btn-sm add-to-cart">Add to Cart</button>' +
            "</div></div></div>";
    }

    function getChoice($card) {
        var p = byId[$card.data("id")];
        var size = $card.find(".size-select").val();
        var color = $card.find(".color-select").length
            ? $card.find(".color-select").val()
            : (p.variants[0] ? p.variants[0].color : "");
        var variant = p.variants.filter(function (v) {
            return v.size === size && v.color === color;
        })[0];
        return { p: p, variant: variant };
    }

    function updateCard($card) {
        var c = getChoice($card);
        var $btn = $card.find(".add-to-cart");
        var $note = $card.find(".stock-note");

        if (!c.variant || c.variant.stock <= 0) {
            $btn.prop("disabled", true).text("Sold out");
            $note.text("");
        } else {
            $btn.prop("disabled", false).text("Add to Cart");
            $note.text(c.variant.stock <= 5 ? "Only " + c.variant.stock + " left" : "");
        }
    }

    function render() {
        var list = getVisibleProducts();
        var totalPages = Math.max(1, Math.ceil(list.length / PER_PAGE));
        if (page > totalPages) page = totalPages;

        var items = list.slice((page - 1) * PER_PAGE, page * PER_PAGE);

        $("#result-count").text(list.length + (list.length === 1 ? " product" : " products"));
        $("#no-results").toggleClass("d-none", list.length > 0);
        $("#product-grid").html(items.map(cardHtml).join(""));
        $("#product-grid .product-card").each(function () { updateCard($(this)); });

        var pages = "";
        if (totalPages > 1) {
            for (var i = 1; i <= totalPages; i++) {
                pages += '<li class="page-item' + (i === page ? " active" : "") + '">' +
                    '<a class="page-link" href="#" data-page="' + i + '">' + i + "</a></li>";
            }
        }
        $("#pagination").html(pages);
    }

    $("#filters").on("change", "input", function () {
        page = 1;
        render();
    });

    $("#sort-select").change(function () {
        page = 1;
        render();
    });

    $("#clear-filters").click(function () {
        $("#filters input[type=checkbox]").prop("checked", false);
        $("#price-all").prop("checked", true);
        page = 1;
        render();
    });

    $("#pagination").on("click", "a", function (e) {
        e.preventDefault();
        page = Number($(this).data("page"));
        render();
        $("html, body").animate({ scrollTop: 0 }, 200);
    });

    $("#product-grid").on("change", ".size-select, .color-select", function () {
        updateCard($(this).closest(".product-card"));
    });

    $("#product-grid").on("click", ".add-to-cart", function () {
        var $btn = $(this);
        var $card = $btn.closest(".product-card");
        var c = getChoice($card);
        if (!c.variant || c.variant.stock <= 0) return;

        var item = {
            id: c.p.id,
            variantId: c.variant.id,
            name: c.p.name,
            price: c.p.price,
            img: "../" + c.p.image,
            size: c.variant.size,
            color: c.variant.color
        };

        var inCart = getCart().filter(function (line) { return sameItem(line, item); })[0];
        if (inCart && inCart.qty >= c.variant.stock) {
            showToast("Only " + c.variant.stock + " in stock for this item.");
            return;
        }

        addToCart(item);
        $btn.text("Added ✓");
        setTimeout(function () { updateCard($card); }, 1000);
    });

    fetch("../api/products.php")
        .then(function (res) { return res.json(); })
        .then(function (data) {
            allProducts = data;
            data.forEach(function (p) { byId[p.id] = p; });

            var cat = new URLSearchParams(location.search).get("category");
            if (cat) {
                $(".f-category").filter(function () {
                    return this.value.toLowerCase() === cat.toLowerCase();
                }).prop("checked", true);
            }

            render();
        })
        .catch(function () {
            $("#product-grid").html('<p class="col-12 text-center text-muted">Could not load products. Is XAMPP running?</p>');
        });
});