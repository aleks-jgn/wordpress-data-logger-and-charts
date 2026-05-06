(function(){
  async function cryptoRenderCharts() {
    // Find all chart canvases on the page
    const canvasElements = document.querySelectorAll('.crypto-chart-canvas');
    if (!canvasElements.length) return;

    // Fetch data once from the REST endpoint
    const resp = await fetch(CRYPTO_SETTINGS.endpoint);
    const data = await resp.json();

    const last7 = data.slice(-7);
    const labels = last7.map(item => item.date);
    const values = last7.map(item => item.value);

    const defaultRadius = 4, hoverRadius = 6;
    const radii = values.map((_, i) => i === values.length - 1 ? hoverRadius : defaultRadius);
    const bgColors = values.map((_, i) =>
      i === values.length - 1 ? '#33a63b' : 'rgb(255, 255, 255)'
    );

    // Define plugins (same for all charts)
    const lastPointHighlightPlugin = {
      id: 'lastPointHighlight',
      afterInit(chart) {
        chart._lastPointActive = true;

        chart.canvas.addEventListener('mouseleave', () => {
          const lastIndex = chart.data.labels.length - 1;
          if (lastIndex < 0) return;

          chart.setActiveElements([{ datasetIndex: 0, index: lastIndex }]);
          const point = chart.getDatasetMeta(0).data[lastIndex];
          chart.tooltip.setActiveElements(
            [{ datasetIndex: 0, index: lastIndex }],
            { x: point.x, y: point.y }
          );
          chart.update({ duration: 0 });
          chart._lastPointActive = true;
        });

        chart.canvas.addEventListener('mouseenter', () => {
          chart._lastPointActive = false;
        });
      },
      afterDraw(chart) {
        if (!chart._lastPointActive) return;
        const lastIndex = chart.data.labels.length - 1;
        if (lastIndex < 0) return;

        chart.setActiveElements([{ datasetIndex: 0, index: lastIndex }]);
        const point = chart.getDatasetMeta(0).data[lastIndex];
        chart.tooltip.setActiveElements(
          [{ datasetIndex: 0, index: lastIndex }],
          { x: point.x, y: point.y }
        );
        chart.tooltip.update(true);
      },
      beforeEvent(chart, args) {
        if (args.event.type === 'mouseout' && chart._lastPointActive) {
          const lastIndex = chart.data.labels.length - 1;
          if (lastIndex < 0) return;

          chart.setActiveElements([{ datasetIndex: 0, index: lastIndex }]);
          const point = chart.getDatasetMeta(0).data[lastIndex];
          chart.tooltip.setActiveElements(
            [{ datasetIndex: 0, index: lastIndex }],
            { x: point.x, y: point.y }
          );
          chart.update({ duration: 0 });
        }
      }
    };

    // Create a separate chart for each canvas
    canvasElements.forEach(canvas => {
      const ctx = canvas.getContext('2d');
      new Chart(ctx, {
        plugins: [lastPointHighlightPlugin],
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Token Holders',
            data: values,
            fill: false,
            tension: 0.4,
            borderColor: '#1C142B',
            backgroundColor: '#1C142B',
            pointBackgroundColor: bgColors,
            pointBorderColor: '#1C142B',
            pointHoverBackgroundColor: '#33a63b',
            pointHoverBorderColor: '#1C142B',
            borderWidth: 2,
            pointRadius: radii,
            pointHoverRadius: radii
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#000',
              titleColor: '#fff',
              bodyColor: '#fff',
              titleFont: { size: 0 },
              displayColors: false,
              callbacks: {
                title: () => '',
                label: ctx => String(ctx.parsed.y)
              },
              padding: 10
            }
          },
          hover: {
            mode: 'nearest',
            intersect: true
          },
          scales: {
            x: {
              title: { display: false },
              grid: { display: false, drawBorder: false },
              border: { display: true, color: 'transparent' },
              ticks: {
                callback: function(value) {
                  const date = new Date(this.getLabelForValue(value));
                  const day = String(date.getDate()).padStart(2, '0');
                  const monthAbbr = date.toLocaleString('en', { month: 'short' });
                  return `${day} ${monthAbbr}`;
                },
                color: '#1C142B'
              }
            },
            y: {
              title: { display: false },
              beginAtZero: true,
              grid: { display: false, drawBorder: false },
              ticks: { color: 'transparent' },
              border: { display: true, color: 'transparent' }
            }
          }
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', cryptoRenderCharts);
})();