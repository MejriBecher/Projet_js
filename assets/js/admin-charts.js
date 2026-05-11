document.addEventListener('DOMContentLoaded', function() {
    // Load stats
    fetch('/app/admin/stats_data.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var roomsEl = document.getElementById('stat-rooms');
            var rsvEl = document.getElementById('stat-reservations');
            var usersEl = document.getElementById('stat-users');
            var revEl = document.getElementById('stat-revenue');
            if (roomsEl) roomsEl.textContent = data.rooms;
            if (rsvEl) rsvEl.textContent = data.reservations;
            if (usersEl) usersEl.textContent = data.users;
            if (revEl) revEl.textContent = '$' + data.revenue.toFixed(2);
        });

    // Charts
    var ctx1 = document.getElementById('chart-revenue');
    var ctx2 = document.getElementById('chart-status');
    if (!ctx1 || !ctx2 || typeof Chart === 'undefined') return;

    fetch('/app/admin/chart_data.php')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: d.revenue.labels,
                    datasets: [{ label: 'Revenue ($)', data: d.revenue.data, backgroundColor: '#0C3B5E' }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });

            new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: d.status.labels,
                    datasets: [{ data: d.status.data, backgroundColor: ['#fff3cd','#cce5ff','#d4edda','#e2e3e5','#f8d7da'] }]
                }
            });
        });
});
