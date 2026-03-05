<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Only run this if the Admin charts actually exist on the page
    if (document.getElementById('stageChart')) {
        
        // --- 1. DOUGHNUT CHART (Stages) ---
        const stageCtx = document.getElementById('stageChart').getContext('2d');
        new Chart(stageCtx, {
            type: 'doughnut',
            data: {
                labels: ['Design', 'Impression', 'Livraison', 'Terminé'],
                datasets: [{
                    data: [
                        <?= $stats['design'] ?? 0 ?>, 
                        <?= $stats['printing'] ?? 0 ?>, 
                        <?= $stats['delivery'] ?? 0 ?>, 
                        <?= $stats['completed'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#3b82f6', // Blue (Design)
                        '#f97316', // Orange (Printing)
                        '#14b8a6', // Teal (Delivery)
                        '#10b981'  // Green (Completed)
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: {size: 11} } }
                },
                cutout: '70%' // Makes the doughnut thinner and more modern
            }
        });

        // --- 2. BAR CHART (Timeline) ---
        const volumeCtx = document.getElementById('volumeChart').getContext('2d');
        new Chart(volumeCtx, {
            type: 'bar',
            data: {
                // Pass PHP arrays securely to JavaScript
                labels: <?= json_encode($chartDates) ?>,
                datasets: [{
                    label: 'Nouvelles Commandes',
                    data: <?= json_encode($chartVolumes) ?>,
                    backgroundColor: '#6366f1', // Indigo
                    borderRadius: 4, // Rounded tops on the bars
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } // Hide legend since there's only one dataset
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1 } // Force whole numbers
                    },
                    x: {
                        grid: { display: false } // Hide vertical grid lines for a cleaner look
                    }
                }
            }
        });
    }

    // (Keep your existing Smart Auto-Sync script down here...)
});
</script>
</div> </body>
</html>