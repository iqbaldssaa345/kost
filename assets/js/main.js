/**
 * JavaScript Utama - Sistem Kost Modern
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileBtn && navMenu) {
        mobileBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // 2. Hitung Total Biaya Sewa Dinamis pada Form Booking
    const durasiInput = document.getElementById('durasiSewa');
    const hargaPerBulanElem = document.getElementById('hargaPerBulan');
    const totalBiayaElem = document.getElementById('totalBiayaPreview');
    const totalBiayaInput = document.getElementById('totalBiayaInput');
    const tglMulaiInput = document.getElementById('tglMulai');
    const tglSelesaiElem = document.getElementById('tglSelesaiPreview');

    function updateRentalCalculation() {
        if (!durasiInput || !hargaPerBulanElem) return;

        const durasi = parseInt(durasiInput.value) || 1;
        const hargaPerBulan = parseFloat(hargaPerBulanElem.dataset.harga) || 0;
        const hargaPerTahun = parseFloat(hargaPerBulanElem.dataset.hargaTahun) || (hargaPerBulan * 12);
        
        let total = 0;
        if (durasi === 12 && hargaPerTahun > 0) {
            total = hargaPerTahun;
        } else {
            total = durasi * hargaPerBulan;
        }

        if (totalBiayaElem) {
            totalBiayaElem.innerText = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(total);
        }

        if (totalBiayaInput) {
            totalBiayaInput.value = total;
        }

        // Hitung estimasi tanggal selesai
        if (tglMulaiInput && tglMulaiInput.value && tglSelesaiElem) {
            const startDate = new Date(tglMulaiInput.value);
            startDate.setMonth(startDate.getMonth() + durasi);
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            tglSelesaiElem.innerText = startDate.toLocaleDateString('id-ID', options);
        }
    }

    if (durasiInput) {
        durasiInput.addEventListener('input', updateRentalCalculation);
        durasiInput.addEventListener('change', updateRentalCalculation);
    }
    if (tglMulaiInput) {
        tglMulaiInput.addEventListener('change', updateRentalCalculation);
    }

    // 3. Image Modal Lightbox
    window.openImageModal = function(imgSrc) {
        let modal = document.getElementById('imageModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'imageModal';
            modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;cursor:pointer;';
            modal.innerHTML = '<img id="modalImg" style="max-width:90%;max-height:85vh;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.5);">';
            modal.addEventListener('click', () => { modal.style.display = 'none'; });
            document.body.appendChild(modal);
        }
        document.getElementById('modalImg').src = imgSrc;
        modal.style.display = 'flex';
    };

    // 4. Preview Upload Gambar
    const uploadInputs = document.querySelectorAll('.image-preview-input');
    uploadInputs.forEach(input => {
        input.addEventListener('change', function() {
            const targetId = this.dataset.previewTarget;
            const previewElem = document.getElementById(targetId);
            if (previewElem && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElem.src = e.target.result;
                    previewElem.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});
