import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

window.Chart = Chart;
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.color = '#64748B';

// Render every <canvas data-chart='{...}'> on the page from its JSON config.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart]').forEach((el) => {
        try {
            new Chart(el, JSON.parse(el.dataset.chart));
        } catch (e) {
            console.error('Chart config error', e);
        }
    });
});

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();
