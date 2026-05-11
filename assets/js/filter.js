document.addEventListener('DOMContentLoaded', function() {
    var grid = document.getElementById('room-grid');
    var filterType = document.getElementById('filter-type');
    var filterPrice = document.getElementById('filter-max-price');
    var clearBtn = document.getElementById('filter-clear');
    if (!grid || !filterType) return;

    function applyFilters() {
        var type = filterType.value.toLowerCase();
        var maxPrice = parseFloat(filterPrice.value) || Infinity;
        var cards = grid.querySelectorAll('.room-card');
        cards.forEach(function(card) {
            var cardType = (card.dataset.type || '').toLowerCase();
            var cardPrice = parseInt(card.dataset.price) || 0;
            var show = true;
            if (type && cardType !== type) show = false;
            if (cardPrice > maxPrice) show = false;
            card.style.display = show ? '' : 'none';
        });
    }

    filterType.addEventListener('change', applyFilters);
    filterPrice.addEventListener('input', applyFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            filterType.value = '';
            filterPrice.value = '';
            applyFilters();
        });
    }
});
