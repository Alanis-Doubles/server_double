
// Line Chart
(() => {
    'use strict';
    const Chart = document.querySelector('#d2c_dashboard_lineChart') ?? '';
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
                height: 400,
                zoom: {
                    type: 'x',
                    enabled: true,
                    autoScaleYaxis: true,
                },
                toolbar: {
                    show: false,
                },
            },
            colors: ['#3557D4'],
            dataLabels: {
                enabled: false,
            },
            grid:{
                borderColor: '#e5e3f21f',  
            },
            markers: {
                size: 0,
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    inverseColors: false,
                    opacityFrom: 0.5,
                    opacityTo: 0.5,
                },
            },
            xaxis: {
                type: 'datetime',
                axisBorder: {
                    color: '#e5e3f21f',
                },
            },
            yaxis: {
                labels: {
                    formatter: function (y) {
                        return y.toFixed(0) + "K";
                    }
                }
            },
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();

// VolumeBar Chart
var options = {
    series: [{
        name: 'Net Profit',
        data: [44, 55, 57, 56, 61, 58, 63],
    }, {
        name: 'Revenue',
        data: [76, 85, 101, 98, 87, 105, 91],
    }, {
        name: 'Free Cash Flow',
        data: [35, 41, 36, 26, 45, 48, 52]
    }],
    chart: {
        type: 'bar',
        height: 390,
        foreColor: '#ccc',
        toolbar: {
            show: false,
        },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '70%',
            borderRadius: 5,
        },
    },
    dataLabels: {
        enabled: false
    },
    colors: ['#3557D4', '#886DD1', '#327AD1'],
    legend: {
        show: false,
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: ['Tus', 'Fri', 'Sat', 'Sun', 'Mon', 'Wed', 'Thu'],
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
    grid:{
        borderColor: '#e5e3f21f',  
    },
    tooltip: {
        y: {
            formatter: function (val) {
            return "$ " + val + " thousands"
            }
        }
    }
};

var chart = new ApexCharts(document.querySelector("#d2c_volume_bar_chart"), options);
chart.render();


// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */