/**
 * JavaScript Admin & Petugas - Sistem Kost Modern
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle Mobile
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // 2. Modal Handler
    window.openModal = function(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.add('active');
    };

    window.closeModal = function(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.remove('active');
    };

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // 3. Confirm Delete Helper
    window.confirmAction = function(message, targetUrl) {
        if (confirm(message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?')) {
            window.location.href = targetUrl;
        }
    };

    // 4. Live Table Search Filter
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        const tableId = input.dataset.targetTable;
        const table = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 5. Image Preview Helper
    const imgInputs = document.querySelectorAll('.admin-img-input');
    imgInputs.forEach(input => {
        input.addEventListener('change', function() {
            const targetId = this.dataset.previewTarget;
            const preview = document.getElementById(targetId);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // 6. User Profile Dropdown Toggle
    const profileWrappers = document.querySelectorAll('.user-profile-wrapper');
    profileWrappers.forEach(wrapper => {
        const btn = wrapper.querySelector('.user-profile-btn');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other wrappers
                profileWrappers.forEach(other => {
                    if (other !== wrapper) other.classList.remove('active');
                });
                wrapper.classList.toggle('active');
            });
        }
    });

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        profileWrappers.forEach(wrapper => {
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('active');
            }
        });
    });
});
