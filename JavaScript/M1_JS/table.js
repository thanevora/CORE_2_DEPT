document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('tableChart')?.getContext('2d');
    if (!ctx) return;

    // Fetch table chart data from dedicated PHP file
    fetch('../../M1/sub-modules/chart_table.php')
        .then(res => res.json())
        .then(response => {
            if (response.status !== 'success') {
                console.error('Chart data fetch failed:', response.message);
                return;
            }

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: response.labels,
                    datasets: [{
                        data: response.data,
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#1a2c5b', '#FF0000'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    cutout: '70%'
                }
            });
        })
        .catch(error => {
            console.error('[Chart Fetch Error]', error);
        });

    // Re-initialize icons
    lucide.createIcons();
});
