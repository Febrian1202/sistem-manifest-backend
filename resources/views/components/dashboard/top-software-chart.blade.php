@props(['topSoftware' => []])

<div class="bg-card border border-border rounded-lg p-5 shadow-sm">
    <div class="mb-4">
        <h3 class="text-sm font-semibold text-foreground">Top 10 Software Paling Banyak Terinstall</h3>
        <p class="text-xs text-muted-foreground mt-1">Berdasarkan jumlah komputer yang menginstall</p>
    </div>
    <div class="h-100">
        <canvas id="topSoftwareChart"></canvas>
    </div>
</div>

{{-- Chart.js Initialization --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('topSoftwareChart').getContext('2d');

        // Ensure we work with a collection to use pluck()
        const topSoftware = @js(collect($topSoftware));
        const labels = @js(collect($topSoftware)->pluck('name'));
        const totals = @js(collect($topSoftware)->pluck('total'));

        // Using Chart.js for Top 10 Software as requested
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Instalasi',
                    data: totals,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 20
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.x + ' Unit';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    });
</script>