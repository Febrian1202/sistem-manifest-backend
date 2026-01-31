@props(['series' => [], 'labels' => []])

<div class="bg-card border border-border rounded-lg p-5 shadow-sm" x-data="licenseChart(@js($series), @js($labels))">

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-foreground">Status Lisensi</h3>
    </div>

    <div class="flex flex-col sm:flex-row items-center gap-6">

        <div x-ref="chart" class="flex shrink-0 min-h-50 w-50"></div>

        <div class="flex-1 w-full grid grid-cols-1 gap-3">
            <template x-for="(item, index) in chartData" :key="index">

                <div class="flex items-center justify-between group">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full shadow-sm" :style="`background-color: ${item.color}`"></span>

                        <span class="text-sm font-medium" :style="`color: ${item.color}`" x-text="item.label"></span>
                    </div>

                    <div class="text-right">
                        <span class="text-xs font-bold text-foreground" x-text="item.value"></span>
                        <span class="text-[10px] text-muted-foreground ml-1" x-text="'(' + item.percent + '%)'"></span>
                    </div>
                </div>

            </template>
        </div>

    </div>
</div>

<script>
    function licenseChart(seriesData, labelsData) {
        return {
            chart: null,
            // Data sesuai React Component
            series: seriesData || [],
            labels: labelsData || [],

            get chartData() {
                const total = this.series.reduce((a, b) => a + b, 0);
                const getVal = (variable) =>
                    `hsl(${getComputedStyle(document.documentElement).getPropertyValue(variable).trim()})`;

                // Mapping Warna ke CSS Variable (Success, Warning, Danger)
                const colors = [
                    getVal('--success'), // Licensed (Green)
                    getVal('--warning'), // Grace Period (Yellow)
                    getVal('--danger') // Unlicensed (Red)
                ];

                return this.labels.map((label, index) => {
                    return {
                        label: label,
                        value: this.series[index],
                        color: colors[index],
                        percent: ((this.series[index] / total) * 100).toFixed(1)
                    }
                });
            },

            init() {
                if (this.chart) this.chart.destroy();

                const data = this.chartData;
                const colors = data.map(i => i.color);

                const options = {
                    series: this.series,
                    labels: this.labels,
                    chart: {
                        type: 'donut',
                        height: 220,
                        width: '100%',
                        fontFamily: 'Poppins, sans-serif',
                        background: 'transparent',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true
                        }
                    },
                    colors: colors,
                    stroke: {
                        show: true,
                        width: 2,
                        // Border antar slice pakai warna background card agar terlihat "terpotong"
                        colors: [
                            `hsl(${getComputedStyle(document.documentElement).getPropertyValue('--card').trim()})`
                        ]
                    },
                    dataLabels: {
                        enabled: false
                    }, // Matikan label internal chart
                    legend: {
                        show: false
                    }, // Matikan legend bawaan

                    plotOptions: {
                        pie: {
                            donut: {
                                size: '35%', // Ketebalan Donut
                                labels: {
                                    show: true,
                                    name: {
                                        show: false
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '24px',
                                        fontWeight: 'bold',
                                        color: `hsl(${getComputedStyle(document.documentElement).getPropertyValue('--foreground').trim()})`,
                                        formatter: function(val) {
                                            return val
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '12px',
                                        color: `hsl(${getComputedStyle(document.documentElement).getPropertyValue('--muted-foreground').trim()})`,
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        y: {
                            formatter: function(val) {
                                return val + " Installs"
                            }
                        }
                    }
                };

                this.chart = new ApexCharts(this.$refs.chart, options);
                this.chart.render();
            }
        }
    }
</script>
