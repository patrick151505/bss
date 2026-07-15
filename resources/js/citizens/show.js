import QRCode from 'qrcode';

window.addEventListener('DOMContentLoaded', () => {
    const canvas  = document.getElementById('citizen-qrcode');
    const qrValue = canvas ? canvas.dataset.value : null;

    if (canvas && qrValue) {
        QRCode.toCanvas(canvas, qrValue, { width: 160, margin: 1 }, (err) => {
            if (err) console.error('QR error:', err);
        });
    }
});
