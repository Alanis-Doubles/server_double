// Bar Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_barChart') ?? '';

    if (Chart == '') {
        return false;
    } else {
        var options = {
            chart: {
                foreColor: '#ccc',
                type: 'bar',
                fontFamily: 'Poppins, sans-serif',
                toolbar: {
                    show: false,
                },
            },
            grid:{
                borderColor: '#e5e3f21f',  
            },
            series: [
                {
                    name: 'Income',
                    data: [80, 85, 105, 100, 92, 80, 120, 102, 98, 45, 92, 82],
                },
            ],
            colors: ['#5470c6'],
            legend: {
                show: false,
                position: 'top',
                horizontalAlign: 'right',
            },
            dataLabels: {
                enabled: false,
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Marc', 'April', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 8,
                    dataLabels: {
                        position: 'center',
                    },
                },
            },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#FFFFFF'],
                },
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// Line Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_lineChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [
                {
                    name: 'Desktop',
                    data: [
                        ['2023-05-01', 150],
                        ['2023-05-02', 160],
                        ['2023-05-03', 170],
                        ['2023-05-04', 161],
                        ['2023-05-05', 167],
                        ['2023-05-06', 162],
                        ['2023-05-07', 161],
                        ['2023-05-08', 152],
                        ['2023-05-09', 141],
                        ['2023-05-10', 144],
                        ['2023-05-11', 154],
                        ['2023-05-12', 166],
                        ['2023-05-13', 176],
                        ['2023-05-14', 187],
                        ['2023-05-15', 198],
                        ['2023-05-16', 210],
                        ['2023-05-17', 196],
                        ['2023-05-18', 207],
                        ['2023-05-19', 200],
                        ['2023-05-20', 187],
                        ['2023-05-21', 192],
                        ['2023-05-22', 204],
                        ['2023-05-23', 193],
                        ['2023-05-24', 204],
                        ['2023-05-25', 193],
                        ['2023-05-26', 204],
                        ['2023-05-27', 208],
                        ['2023-05-28', 196],
                        ['2023-05-29', 193],
                        ['2023-05-30', 178],
                        ['2023-05-31', 204],
                        ['2023-06-01', 218],
                        ['2023-06-02', 211],
                        ['2023-06-03', 218],
                        ['2023-06-04', 216],
                        ['2023-06-05', 197],
                        ['2023-06-06', 190],
                        ['2023-06-07', 179],
                        ['2023-06-08', 172],
                        ['2023-06-09', 158],
                        ['2023-06-10', 159],
                        ['2023-06-11', 147],
                        ['2023-06-12', 152],
                        ['2023-06-13', 137],
                        ['2023-06-14', 136],
                        ['2023-06-15', 123],
                        ['2023-06-16', 112],
                        ['2023-06-17', 99],
                        ['2023-06-18', 100],
                        ['2023-06-19', 95],
                        ['2023-06-20', 105],
                        ['2023-06-21', 116],
                        ['2023-06-22', 125],
                        ['2023-06-23', 124],
                        ['2023-06-24', 133],
                        ['2023-06-25', 129],
                        ['2023-06-26', 116],
                        ['2023-06-27', 119],
                        ['2023-06-28', 109],
                        ['2023-06-29', 115],
                        ['2023-06-30', 111],
                        ['2023-07-01', 96],
                        ['2023-07-02', 104],
                        ['2023-07-03', 102],
                        ['2023-07-04', 116],
                        ['2023-07-05', 126],
                        ['2023-07-06', 117],
                        ['2023-07-07', 130],
                        ['2023-07-08', 124],
                        ['2023-07-09', 126],
                        ['2023-07-10', 131],
                        ['2023-07-11', 143],
                        ['2023-07-12', 130],
                        ['2023-07-13', 116],
                        ['2023-07-14', 118],
                        ['2023-07-15', 122],
                        ['2023-07-16', 132],
                        ['2023-07-17', 126],
                        ['2023-07-18', 136],
                        ['2023-07-19', 123],
                        ['2023-07-20', 112],
                        ['2023-07-21', 116],
                        ['2023-07-22', 113],
                        ['2023-07-23', 109],
                        ['2023-07-24', 99],
                        ['2023-07-25', 100],
                        ['2023-07-26', 93],
                        ['2023-07-27', 85],
                        ['2023-07-28', 79],
                        ['2023-07-29', 64],
                        ['2023-07-30', 79],
                    ],
                },
            ],
            chart: {
                type: 'area',
                foreColor: '#ccc',
                stacked: false,
                zoom: {
                    type: 'x',
                    enabled: true,
                    autoScaleYaxis: true,
                },
                toolbar: {
                    show: false,
                },
            },
            colors: ['#5470c6'],
            dataLabels: {
                enabled: false,
            },
            markers: {
                size: 0,
            },
            grid:{
                borderColor: '#e5e3f21f',  
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.6,
                    opacityTo: 0.4,
                },
            },
            xaxis: {
                type: 'datetime',
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// Area Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_areaChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [
                {
                    name: 'series1',
                    data: [31, 40, 28, 51, 42, 109, 100],
                },
                {
                    name: 'series2',
                    data: [11, 32, 45, 32, 34, 52, 41],
                },
            ],
            colors: ['#5470c6', '#fac858'],
            chart: {
                type: 'area',
                foreColor: '#ccc',
                toolbar: {
                    show: false,
                },
            },
            dataLabels: {
                enabled: false,
            },
            grid:{
                borderColor: '#e5e3f21f',  
            },
            stroke: {
                curve: 'smooth',
            },
            legend: {
                show: false,
            },
            xaxis: {
                type: 'datetime',
                categories: ['2018-09-19T00:00:00.000Z', '2018-09-19T01:30:00.000Z', '2018-09-19T02:30:00.000Z', '2018-09-19T03:30:00.000Z', '2018-09-19T04:30:00.000Z', '2018-09-19T05:30:00.000Z', '2018-09-19T06:30:00.000Z'],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            tooltip: {
                x: {
                    format: 'dd/MM/yy HH:mm',
                },
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// Scatter Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_scatterChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [
                {
                    name: 'SAMPLE A',
                    data: [
                        [16.4, 5.4],
                        [21.7, 2],
                        [25.4, 3],
                        [19, 2],
                        [10.9, 1],
                        [13.6, 3.2],
                        [10.9, 7.4],
                        [10.9, 0],
                        [10.9, 8.2],
                        [16.4, 0],
                        [16.4, 1.8],
                        [13.6, 0.3],
                        [13.6, 0],
                    ],
                },
                {
                    name: 'SAMPLE B',
                    data: [
                        [36.4, 13.4],
                        [1.7, 11],
                        [5.4, 8],
                        [9, 17],
                        [1.9, 4],
                        [3.6, 12.2],
                        [1.9, 14.4],
                        [1.9, 9],
                        [1.9, 13.2],
                        [1.4, 7],
                        [6.4, 8.8],
                        [3.6, 4.3],
                        [1.6, 10],
                        [9.9, 2],
                    ],
                },
                {
                    name: 'SAMPLE C',
                    data: [
                        [21.7, 3],
                        [23.6, 3.5],
                        [24.6, 3],
                        [29.9, 3],
                        [21.7, 20],
                        [23, 2],
                        [10.9, 3],
                        [28, 4],
                        [27.1, 0.3],
                        [16.4, 4],
                        [13.6, 0],
                        [19, 5],
                        [22.4, 3],
                        [24.5, 3],
                    ],
                },
                {
                    name: 'SAMPLE D',
                    data: [
                        [1.9, 15.2],
                        [6.4, 16.5],
                        [0.9, 10],
                        [4.5, 17.1],
                        [10.9, 10],
                        [0.1, 14.7],
                        [9, 10],
                        [12.7, 11.8],
                        [2.1, 10],
                        [2.5, 10],
                        [27.1, 10],
                        [2.9, 11.5],
                        [7.1, 10.8],
                        [2.1, 12],
                    ],
                },
                {
                    name: 'SAMPLE E',
                    data: [
                        [36.4, 13.4],
                        [1.7, 11],
                        [5.4, 8],
                        [9, 17],
                        [1.9, 4],
                        [3.6, 12.2],
                        [1.9, 14.4],
                        [1.9, 9],
                        [1.9, 13.2],
                        [1.4, 7],
                        [6.4, 8.8],
                        [3.6, 4.3],
                        [1.6, 10],
                        [9.9, 2],
                    ],
                },
            ],
            chart: {
                foreColor: '#ccc',
                type: 'scatter',
                toolbar: {
                    show: false,
                },
            },
            colors: ['#5470c6', '#fac858', '#91cc75', '#ee6666', '#73c0de'],
            grid: {
                show: true,
                borderColor: '#e5e3f21f',
                xaxis: {
                    lines: {
                        show: true,
                    },
                },
                yaxis: {
                    lines: {
                        show: true,
                    },
                },
            },
            xaxis: {
                tickAmount: 10,
                labels: {
                    formatter: function (val) {
                        return parseFloat(val).toFixed(1);
                    },
                },
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            yaxis: {
                tickAmount: 7,
            },
            legend: {
                show: false,
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

//  Basic Donut Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_s_donutChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [45, 55],
            chart: {
                foreColor: '#ccc',
                type: 'donut',
                toolbar: {
                    show: false,
                },
            },
            colors: ['#91cc75', '#5470c6'],
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// Polar Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_polarChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [
                {
                    name: 'Series 1',
                    data: [80, 50, 30, 40, 100, 20],
                },
                {
                    name: 'Series 2',
                    data: [20, 30, 40, 80, 20, 80],
                },
                {
                    name: 'Series 3',
                    data: [44, 76, 78, 13, 43, 10],
                },
            ],
            chart: {
                type: 'radar',
                foreColor: '#ccc',
                dropShadow: {
                    enabled: true,
                    blur: 1,
                    left: 1,
                    top: 1,
                },
                toolbar: {
                    show: false,
                },
            },
            colors: ['#5470c6', '#fac858', '#91cc75'],
            stroke: {
                width: 2,
            },
            fill: {
                opacity: 0.1,
            },
            markers: {
                size: 0,
            },
            xaxis: {
                categories: ['2011', '2012', '2013', '2014', '2015', '2016'],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// RadialBar Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_radialBarChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [44, 55, 67, 83],
            chart: {
                foreColor: '#ccc',
                type: 'radialBar',
                fontFamily: 'Poppins, sans-serif',
                toolbar: {
                    show: false,
                },
            },
            plotOptions: {
                radialBar: {
                    dataLabels: {
                        name: {
                            fontSize: '22px',
                        },
                        value: {
                            fontSize: '16px',
                        },
                        total: {
                            show: false,
                            label: 'Total',
                            formatter: function (w) {
                                return 249;
                            },
                        },
                    },
                },
            },
            labels: ['Apples', 'Oranges', 'Bananas', 'Berries'],
            colors: ['#5470c6', '#fac858', '#91cc75', '#ee6666'],
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// Donut Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_donutChart') ?? '';
    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [22, 50, 28],
            chart: {
                foreColor: '#ccc',
                type: 'polarArea',
                toolbar: {
                    show: false,
                },
            },
            colors: ['#fac858', '#5470c6', '#91cc75'],
            stroke: {
                width: 0,
            },
            fill: {
                opacity: 1,
            },
            legend: {
                show: false,
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();


// column chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_column_chart') ?? '';

    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [{
                name: 'Net Profit',
                data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
            }, {
                name: 'Revenue',
                data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
            }, {
                name: 'Free Cash Flow',
                data: [35, 41, 36, 26, 45, 48, 52, 53, 41]
            }],
            chart: {
                type: 'bar',
                foreColor: '#ccc',
                toolbar: {
                    show: false,
                },
            },
            colors: ['#5470c6','#fac858','#91cc75'],
            grid:{
                borderColor: '#e5e3f21f',  
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            yaxis: {
                title: {
                    text: '$ (thousands)'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                    return "$ " + val + " thousands"
                    }
                }
            }
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// stacked bar chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_stacked_bar_chart') ?? '';

    if (Chart == '') {
        return false;
    } else {
        var options = {
            series: [{
                name: 'PRODUCT A',
                data: [44, 55, 41, 67, 22, 43],
            }, {
                name: 'PRODUCT B',
                data: [13, 23, 20, 8, 13, 27]
            }, {
                name: 'PRODUCT C',
                data: [11, 17, 15, 15, 21, 14]
            }, {
                name: 'PRODUCT D',
                data: [21, 7, 25, 13, 22, 8]
            }],
            chart: {
                type: 'bar',
                foreColor: "#ccc",
                stacked: true,
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: true
                }
            },
            colors: ['#5470c6','#fac858','#91cc75','#ee6666'],
            grid:{
                borderColor: '#e5e3f21f',  
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                    }
                }
            }],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 10,
                    dataLabels: {
                        total: {
                            enabled: true,
                            style: {
                                fontSize: '13px',
                                fontWeight: 900
                            }
                        }
                    }
                },
            },
            xaxis: {
                type: 'datetime',
                categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT',
                    '01/05/2011 GMT', '01/06/2011 GMT'
                ],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            legend: {
                position: 'right',
                offsetY: 40
            },
            fill: {
                opacity: 1
            }
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();


// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */
