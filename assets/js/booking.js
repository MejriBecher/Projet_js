document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('booking-form');
    if (!form) return;

    var roomSelect = document.getElementById('room_id');
    var checkIn = document.getElementById('check_in');
    var checkOut = document.getElementById('check_out');
    var preview = document.getElementById('price-preview');
    var totalEl = document.getElementById('total-price');
    var detailEl = document.getElementById('price-detail');

    function calcPrice() {
        var selected = roomSelect.options[roomSelect.selectedIndex];
        var price = parseFloat(selected ? selected.dataset.price : 0) || 0;
        var ci = checkIn.value;
        var co = checkOut.value;
        if (!price || !ci || !co) { preview.style.display = 'none'; return; }
        var d1 = new Date(ci + 'T00:00:00');
        var d2 = new Date(co + 'T00:00:00');
        if (d2 <= d1) { preview.style.display = 'none'; return; }
        var days = Math.round((d2 - d1) / 86400000);
        var total = price * days;
        preview.style.display = '';
        totalEl.textContent = total.toFixed(2);
        detailEl.textContent = '$' + price.toFixed(2) + ' x ' + days + ' night' + (days > 1 ? 's' : '');
    }

    roomSelect.addEventListener('change', calcPrice);
    checkIn.addEventListener('change', function() {
        var d = new Date(checkIn.value + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        checkOut.min = d.toISOString().split('T')[0];
        if (checkOut.value && new Date(checkOut.value + 'T00:00:00') <= d) {
            checkOut.value = d.toISOString().split('T')[0];
        }
        calcPrice();
    });
    checkOut.addEventListener('change', calcPrice);

    // Inline validation
    form.addEventListener('submit', function(e) {
        var missing = [];
        if (!roomSelect.value) missing.push('Please select a room.');
        if (!checkIn.value) missing.push('Please select a check-in date.');
        if (!checkOut.value) missing.push('Please select a check-out date.');
        if (checkIn.value && checkOut.value && checkOut.value <= checkIn.value) {
            missing.push('Check-out must be after check-in.');
        }
        if (missing.length) {
            e.preventDefault();
            alert(missing.join('\n'));
        }
    });
});
