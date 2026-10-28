// Chart.js empaquetado con el portal (no depende de un CDN). Lo usa el tablero del personal.
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);
Chart.defaults.font.family = '"Raleway Variable", system-ui, sans-serif';
Chart.defaults.font.size = 13;
Chart.defaults.color = '#4a5668';
Chart.defaults.borderColor = '#e7ecf2';
window.Chart = Chart;
