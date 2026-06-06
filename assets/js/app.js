// Klinik Sehat Bersama - App JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Fade-in animation for cards
    document.querySelectorAll('.card, .stat-card').forEach(function(card, i) {
        card.style.animationDelay = (i * 0.05) + 's';
        card.classList.add('fade-in');
    });

    // Kembalian calculator
    const jumlahBayar = document.getElementById('jumlah_bayar');
    const totalTagihan = document.getElementById('total_tagihan_value');
    const kembalianEl = document.getElementById('kembalian_display');
    if (jumlahBayar && totalTagihan && kembalianEl) {
        jumlahBayar.addEventListener('input', function() {
            const bayar = parseFloat(this.value) || 0;
            const total = parseFloat(totalTagihan.value) || 0;
            const kembalian = Math.max(0, bayar - total);
            kembalianEl.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
            const hiddenInput = document.getElementById('kembalian');
            if (hiddenInput) hiddenInput.value = kembalian;
        });
    }

    // ICD-10 autocomplete
    const icdInput = document.getElementById('kode_icd');
    const icdDropdown = document.getElementById('icd_dropdown');
    const diagnosisInput = document.getElementById('diagnosis');
    if (icdInput && icdDropdown) {
        let debounceTimer;
        icdInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) { icdDropdown.innerHTML = ''; icdDropdown.style.display = 'none'; return; }
            debounceTimer = setTimeout(function() {
                fetch(BASE_URL + '/modules/rekam_medis/aksi.php?action=search_icd&q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        icdDropdown.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(function(item) {
                                const div = document.createElement('div');
                                div.className = 'dropdown-item';
                                div.style.cursor = 'pointer';
                                div.textContent = item.kode + ' - ' + item.nama_penyakit;
                                div.addEventListener('click', function() {
                                    icdInput.value = item.kode;
                                    if (diagnosisInput) diagnosisInput.value = item.nama_penyakit;
                                    icdDropdown.style.display = 'none';
                                });
                                icdDropdown.appendChild(div);
                            });
                            icdDropdown.style.display = 'block';
                        } else {
                            icdDropdown.style.display = 'none';
                        }
                    });
            }, 300);
        });
        document.addEventListener('click', function(e) {
            if (!icdInput.contains(e.target) && !icdDropdown.contains(e.target)) {
                icdDropdown.style.display = 'none';
            }
        });
    }

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });
});

// Global BASE_URL
const BASE_URL = document.querySelector('link[href*="style.css"]')?.href.replace('/assets/css/style.css', '') || '';
