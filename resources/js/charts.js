import ApexCharts from 'apexcharts';

const labelStyle = {
    colors: '#A3AED0',
    fontSize: '14px',
    fontWeight: '500',
};

const axisLabels = {
    show: true,
    style: labelStyle,
};

const renderChart = (id, options) => {
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    const chart = new ApexCharts(element, options);

    chart.render();
};

const initCharts = () => {
    renderChart('chart-total-spent', {
        chart: {
            type: 'line',
            toolbar: { show: false },
            height: 190,
            zoom: { enabled: false },
        },
        series: [
            { name: 'Revenue', data: [50, 64, 48, 66, 49, 68] },
            { name: 'Profit', data: [30, 40, 24, 46, 20, 46] },
        ],
        colors: ['#4318FF', '#6AD2FF'],
        stroke: { curve: 'smooth', width: 3 },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { show: false },
        tooltip: { theme: 'dark' },
        xaxis: {
            categories: ['SEP', 'OCT', 'NOV', 'DEC', 'JAN', 'FEB'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                show: true,
                style: {
                    colors: '#A3AED0',
                    fontSize: '12px',
                    fontWeight: '500',
                },
            },
        },
        yaxis: { show: false },
    });

    renderChart('chart-weekly-revenue', {
        chart: {
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            height: 320,
        },
        series: [
            { name: 'PRODUCT A', data: [400, 370, 330, 390, 320, 350, 360, 320, 380] },
            { name: 'PRODUCT B', data: [400, 370, 330, 390, 320, 350, 360, 320, 380] },
            { name: 'PRODUCT C', data: [400, 370, 330, 390, 320, 350, 360, 320, 380] },
        ],
        colors: ['#5E37FF', '#6AD2FF', '#E1E9F8'],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '20px',
            },
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: {
            borderColor: 'rgba(163, 174, 208, 0.3)',
            show: true,
            yaxis: { lines: { show: false }, opacity: 0.5 },
            row: { opacity: 0.5 },
            xaxis: { lines: { show: false } },
        },
        tooltip: { theme: 'dark' },
        xaxis: {
            categories: ['17', '18', '19', '20', '21', '22', '23', '24', '25'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: axisLabels,
        },
        yaxis: { show: false },
    });

    renderChart('chart-daily-traffic', {
        chart: {
            type: 'bar',
            toolbar: { show: false },
            height: 250,
        },
        series: [{ name: 'Daily Traffic', data: [20, 30, 40, 20, 45, 50, 30] }],
        colors: ['#4318FF'],
        fill: {
            type: 'gradient',
            gradient: {
                type: 'vertical',
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.9,
                colorStops: [
                    { offset: 0, color: '#4318FF', opacity: 1 },
                    { offset: 100, color: 'rgba(67, 24, 255, 1)', opacity: 0.28 },
                ],
            },
        },
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '40px',
            },
        },
        dataLabels: { enabled: false },
        grid: { show: false },
        tooltip: { theme: 'dark' },
        xaxis: {
            categories: ['00', '04', '08', '12', '14', '16', '18'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: axisLabels,
        },
        yaxis: { show: false },
    });

    renderChart('chart-pie', {
        chart: {
            type: 'donut',
            width: 200,
        },
        series: [63, 25, 12],
        labels: ['Minyak Solar', 'Minyak Bensin', 'Lainnya'],
        colors: ['#4318FF', '#6AD2FF', '#EFF4FB'],
        legend: { show: false },
        dataLabels: { enabled: false },
        stroke: { width: 0 },
        states: { hover: { filter: { type: 'none' } } },
        tooltip: { enabled: true, theme: 'dark' },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
} else {
    initCharts();
}
