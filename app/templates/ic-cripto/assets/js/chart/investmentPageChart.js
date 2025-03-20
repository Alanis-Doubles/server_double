// Line Chart
(() => {
    'use strict'
    const Chart = document.querySelector('#d2c_total_invest') ?? '';
    if(Chart == ""){
        return false;
    } else{
        var options = {
            series: [{
                name: "Desktops",
                data: [10, 35, 15, 5, 20, 25],
            }],
            chart: {
                foreColor: '#ccc',
                type: 'line',
                height: '420px',
                toolbar: {
                    show: false,
                },
                fontFamily: 'Poppins, sans-serif'
            },
            colors: ['#FFC107'],
            dataLabels: {
                enabled: false
            },
            markers:{
                size: [3,5],
                strokeColors: '#FFC107',
            },
            stroke: {
                width: 2,
                curve: 'smooth',
            },
            grid: {
                show: true,
                borderColor: 'rgba(56, 56, 56, 0.06)',
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
            },
            yaxis: {
                labels: {
                    formatter: function (y) {
                        return y.toFixed(0) + "K";
                    }
                }
            },
            xaxis: {
                categories: ['2018', '2019', '2020', '2021', '2022', '2023'],
                axisBorder: {
                    color: '#e5e3f21f',
                },
            }
        };

        var chart = new ApexCharts(Chart, options);
        chart.render();
    }
})();
// Area Chart
(() => {
    'use strict'
    const Chart = document.querySelector('#d2c_yearly_invest') ?? '' ;
    if(Chart == ""){
        return false;
    } else{
        var options = {
            series: [{
                    name: 'South',
                    data: [45, 15, 37, 32, 42, 40, 30, 38, 25, 25, 10, 38],
                },
            ],
            chart: {
                foreColor: '#ccc',
                type: 'area',
                stacked: true,
                height: '450px',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#3557d4'],
            dataLabels: {
                enabled: false
            },
            markers:{
                size: [3,5],
                colors: '#fff',
                strokeColors: '#3557d4',
            },
            stroke: {
                curve: 'straight',
                width: 2,
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: .5,
                    opacityTo: .1,
                }
            },
            grid: {
                show: true,
                borderColor: 'rgba(56, 56, 56, 0.06)',
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
            },
            yaxis: {
                labels: {
                    formatter: function (y) {
                        return y.toFixed(0) + "K";
                    }
                }
            },
            xaxis: {
                type: 'Month',
                categories: ["Jan", "Feb", "Marc", "April", "May", "Jun", "July", "Aug", "Sep", "Oct", "Nov", "Dec"],
                axisBorder: {
                    color: '#e5e3f21f',
                },
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