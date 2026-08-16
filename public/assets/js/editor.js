/*
 * Camagru — éditeur (JavaScript natif, aucune dépendance).
 * Webcam getUserMedia + capture canvas, sélection de l'overlay,
 * aperçu upload. La superposition finale est faite côté serveur (GD).
 */
(() => {
    'use strict';

    const video = document.getElementById('webcam');
    const fallback = document.getElementById('cam-fallback');
    const canvas = document.getElementById('capture-canvas');
    const captureForm = document.getElementById('capture-form');
    const uploadForm = document.getElementById('upload-form');
    const overlayField = document.getElementById('overlay-field');
    const uploadOverlayField = document.getElementById('upload-overlay-field');
    const imageData = document.getElementById('image-data');
    const captureBtn = document.getElementById('capture-btn');
    const uploadBtn = document.getElementById('upload-btn');
    const photoInput = document.getElementById('photo-input');
    const uploadPreview = document.getElementById('upload-preview');
    const overlayPreview = document.getElementById('overlay-preview');

    let selectedOverlay = '';

    // --- Webcam (sans HTTPS, l'échec est toléré : message au lieu d'erreur) ---
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices
            .getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 } }, audio: false })
            .then((stream) => {
                video.srcObject = stream;
                video.hidden = false;
                video.play().catch(() => {});
            })
            .catch(() => {
                fallback.hidden = false;
            });
    } else {
        fallback.hidden = false;
    }

    // --- Sélection de l'image superposable (+ aperçu en direct) ---
    const selectOverlay = (button) => {
        const isSelected = button.classList.contains('selected');
        document.querySelectorAll('.overlay-item').forEach((b) => b.classList.remove('selected'));

        if (isSelected) {
            // Désélection : on retire l'aperçu et on réactive la garde.
            selectedOverlay = '';
            overlayField.value = '';
            uploadOverlayField.value = '';
            overlayPreview.src = '';
            overlayPreview.hidden = true;
            captureBtn.disabled = true;
            uploadBtn.disabled = true;
            return;
        }

        button.classList.add('selected');
        selectedOverlay = button.dataset.overlay;
        overlayField.value = selectedOverlay;
        uploadOverlayField.value = selectedOverlay;
        overlayPreview.src = '/assets/overlays/' + encodeURIComponent(selectedOverlay);
        overlayPreview.hidden = false;
        captureBtn.disabled = false;
        uploadBtn.disabled = false;
    };

    document.querySelectorAll('.overlay-item').forEach((button) => {
        button.addEventListener('click', () => selectOverlay(button));
    });

    // --- Capture : image de la webcam mise en miroir, recadrée 480x360 ---
    captureForm.addEventListener('submit', (event) => {
        if (captureBtn.disabled) {
            event.preventDefault();
            return;
        }

        const w = 480;
        const h = 360;
        const vw = video.videoWidth || w;
        const vh = video.videoHeight || h;

        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');

        ctx.save();
        ctx.scale(-1, 1);          // miroir (comme un selfie)
        ctx.translate(-w, 0);

        // Couverture 4:3 (recadrage centré, pas de distorsion).
        const cover = Math.max(w / vw, h / vh);
        const sw = w / cover;
        const sh = h / cover;
        const sx = (vw - sw) / 2;
        const sy = (vh - sh) / 2;
        ctx.drawImage(video, sx, sy, sw, sh, 0, 0, w, h);
        ctx.restore();

        imageData.value = canvas.toDataURL('image/png');
    });

    // --- Aperçu local du fichier choisi ---
    photoInput.addEventListener('change', () => {
        const file = photoInput.files && photoInput.files[0];
        if (!file) {
            return;
        }
        const url = URL.createObjectURL(file);
        uploadPreview.src = url;
        uploadPreview.hidden = false;
    });

    // --- Confirmation de suppression (ne bloque pas la soumission native) ---
    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(button.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();
