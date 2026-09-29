@props([
    'chartData' => [
        'scan_trend' => ['labels' => [], 'completed' => [], 'failed' => []],
        'compliance_trend' => ['labels' => [], 'berlisensi' => [], 'tidak_berlisensi' => [], 'grace_period' => [], 'perlu_ditinjau' => []],
    ],
])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="trendCharts(@js($chartData))">
    {{-- Chart 1: Tren Aktivitas Scan Harian --}}
    <div class="bg-card border border-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-foreground flex items-center gap-2">
                    <i class="fa-solid fa-chart-column text-primary"></i> Tren Aktivitas Scan Berkala
                </h3>
                <p class="text-xs text-muted-foreground mt-0.5">Frekuensi scan sukses vs gagal per tanggal</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Berhasil
                </span>
                <span class="inline-flex items-center gap-1.5 text-muted-foreground">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Gagal
                </span>
            </div>
        </div>

        <div x-ref="scanChart" class="w-full min-h-[280px]"></div>
    </div>

    {{-- Chart 2: Tren Status Kepatuhan --}}
    <div class="bg-card border border-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-foreground flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-primary"></i> Tren Kepatuhan Lisensi
                </h3>
                <p class="text-xs text-muted-foreground mt-0.5">Distribusi status lisensi dari riwayat scan</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 text-muted-foreground">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Berlisensi
                </span>
                <span class="inline-flex items-center gap-1 text-muted-foreground">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Grace
                </span>
                <span class="inline-flex items-center gap-1 text-muted-foreground">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Ditinjau
                </span>
                <span class="inline-flex items-center gap-1 text-muted-foreground">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Non-Lisensi
                </span>
            </div>
        </div>

        <div x-ref="complianceChart" class="w-full min-h-[280px]"></div>
    </div>
</div>

@push('scripts')
<script>
    function trendCharts(chartData) {
        return {
            scanChartInstance: null,
            complianceChartInstance: null,
            init() {
                this.renderScanChart();
                this.renderComplianceChart();
            },
            renderScanChart() {
                const scanData = chartData?.scan_trend || { labels: [], completed: [], failed: [] };
                const isDark = document.documentElement.classList.contains('dark');

                const options = {
                    series: [
                        { name: 'Scan Berhasil', data: scanData.completed || [] },
                        { name: 'Scan Gagal', data: scanData.failed || [] }
                    ],
                    chart: {
                        type: 'bar',
                        height: 280,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        background: 'transparent'
                    },
                    colors: ['#10b981', '#ef4444'],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 4
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: scanData.labels || [],
                        labels: {
                            style: {
                                colors: isDark ? '#94a3b8' : '#64748b',
                                fontSize: '11px'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        title: { text: undefined },
                        labels: {
                            style: {
                                colors: isDark ? '#94a3b8' : '#64748b',
                                fontSize: '11px'
                            }
                        }
                    },
                    grid: {
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        strokeDashArray: 4
                    },
                    legend: { show: false },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function (val) {
                                return val + " sesi";
                            }
                        }
                    }
                };

                if (this.scanChartInstance) {
                    this.scanChartInstance.destroy();
                }
                this.scanChartInstance = new window.ApexCharts(this.$refs.scanChart, options);
                this.scanChartInstance.render();
            },
            renderComplianceChart() {
                const compData = chartData?.compliance_trend || { labels: [], berlisensi: [], tidak_berlisensi: [], grace_period: [], perlu_ditinjau: [] };
                const isDark = document.documentElement.classList.contains('dark');

                const options = {
                    series: [
                        { name: 'Berlisensi', data: compData.berlisensi || [] },
                        { name: 'Grace Period', data: compData.grace_period || [] },
                        { name: 'Perlu Ditinjau', data: compData.perlu_ditinjau || [] },
                        { name: 'Tidak Berlisensi', data: compData.tidak_berlisensi || [] }
                    ],
                    chart: {
                        type: 'area',
                        height: 280,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        background: 'transparent'
                    },
                    colors: ['#10b981', '#f59e0b', '#6366f1', '#ef4444'],
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.35,
                            opacityTo: 0.05,
                            stops: [0, 95, 100]
                        }
                    },
                    xaxis: {
                        categories: compData.labels || [],
                        labels: {
                            style: {
                                colors: isDark ? '#94a3b8' : '#64748b',
                                fontSize: '11px'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: isDark ? '#94a3b8' : '#64748b',
                                fontSize: '11px'
                            }
                        }
                    },
                    grid: {
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        strokeDashArray: 4
                    },
                    legend: { show: false },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: function (val) {
                                return val + " lisensi";
                            }
                        }
                    }
                };

                if (this.complianceChartInstance) {
                    this.complianceChartInstance.destroy();
                }
                this.complianceChartInstance = new window.ApexCharts(this.$refs.complianceChart, options);
                this.complianceChartInstance.render();
            }
        };
    }
</script>
@endpush
