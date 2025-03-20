// Bar Chart
function d2c_barChart(){
    const barChart = document.getElementById('bar-chart');
    new Chart(barChart, {
        type: 'bar',
        data: {
            labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
            datasets: [{
                label: 'chart',
                data: [20,40,60,80,100,11,36,47,83,45,92,62,53,78,60],
                backgroundColor: [
                    'rgb(63, 81, 181)',
                ],
                borderRadius: 10,
                borderColor: [
                    'rgb(63, 81, 181)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}d2c_barChart();

// Line Chart
function d2c_lineChart(){
    const lineChart = document.getElementById('line-Chart');
    new Chart(lineChart, {
        type: 'line',
        data: {
            labels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
            datasets: [{
                label: 'Line',
                data: [20,40,60,80,62,11,36,47,83,45,92,62,53,78,60],
                fill: false,
                borderColor: '#3557d4',
                tension: 0.1,
                borderWidth: 2,
            }]
        },
        options: {
            animations: {
                tension: {
                  duration: 2000,
                  easing: 'linear',
                  from: 1,
                  to: 0,
                  loop: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}d2c_lineChart();

// // Area Chart
function d2c_areaChart(){
    const areaChart = document.getElementById('area-Chart');
    new Chart(areaChart, {
        type: 'line',
        data: {
            labels: [1,2,3,4,5,6,7,8],
        datasets: [{
            label: 'Series 1',
            data: [100, 20, 45, 15, 60, 58, 85, 95],
            fill: true,
            borderColor: 'rgba(63, 81, 181)',
            backgroundColor: 'rgba(63, 81, 181, 0.8)',
            borderWidth: 1
        },
        {
            label: 'Series 2',
            data: [30, 80, 82, 56, 58, 5, 80, 100],
            fill: true,
            borderColor: 'rgba(156, 39, 176)',
            backgroundColor: 'rgba(156, 39, 176, 0.6)',
            borderWidth: 1
        }]
        },
        options: {
            plugins: {
                filler: {
                    propagate: true
                }
            }
        }
    });
}d2c_areaChart();   

// scatter chart
function d2c_scatter_chart(){
    const scatterChart = document.getElementById('scatter-Chart');
    new Chart(scatterChart, {
        type: 'scatter',
        data:{
            datasets: [{
                label: 'Scatter Dataset',
                data: [{x: 10,y: 0}, {x: 0,y: 10}, {x: 10,y: 5}, {x: 0.5,y: 5.5},{x: 5,y: 30}, {x: 30,y: 0},{x: 40,y: 15}, {x: 25,y: 10},{x: 50,y: 20}, {x: 15,y: 20},{x: 20,y: 20}, {x: 35,y: 5},{x: 15,y: 10}, {x: 45,y: 10}, {x: 30,y: 25}, {x: 5,y: 15},{x: 35,y: 10},{x: 35,y: 20}, {x: 40,y: 15}, {x: 25,y: 25},{x: 50,y: 15}],
                backgroundColor: 'rgb(255, 99, 132)'
            }],
        },
        options: {
            scales: {
                x: {
                    type: 'linear',
                    position: 'bottom'
                }
            }
        }
    });
}d2c_scatter_chart();

// // // Doughnut Chart
function d2c_doughnutChart(){
    const doughnutChart = document.getElementById('doughnut-Chart');
    new Chart(doughnutChart, {
        type: 'doughnut',
        data: {
            labels: [
                'Sales',
                'Old user',
                'New User'
            ],
            datasets: [{
                label: 'Doughnut Dataset',
                data: [70, 50,30],
                borderWidth: 0,
                backgroundColor: [
                    'rgba(156, 39, 176, 0.6)',
                    'rgba(63, 81, 181, 0.8)',
                    '#a7b3e2'
                ],
                hoverOffset: 2,

            }]
        },
        options: {
            animations: {
                tension: {
                  duration: 3000,
                  easing: 'linear',
                  from: 0,
                  to: 1,
                  loop: true
                }
            }
        }
    });
}d2c_doughnutChart();

// // Polar Chart
function d2c_polarChart(){
    const polarChart = document.getElementById('polar-Chart');
    new Chart(polarChart, {
        type: 'polarArea',
        data: {
            labels: [
              'Series 1',
              'Series 2',
              'Series 3',
            ],
            datasets: [{
              label: 'Polar Dataset',
              data: [11, 16, 7],
              borderWidth: 0,
              backgroundColor: [
                'rgba(156, 39, 176, 0.6)',
                'rgba(63, 81, 181, 0.8)',
                'rgb(80 164 1 / 51%)',
              ]
            }]
        },
        maintainAspectRatio: false,
        options: {
            animations: {
                tension: {
                  duration: 3000,
                  easing: 'linear',
                  from: 0,
                  to: 1,
                  loop: true
                }
            },
        }
    });
}d2c_polarChart();

// stacked bar chart
function d2c_color_bar_chart(){
    const d2c_color_bar_chart = document.getElementById('pie-Chart');
    new Chart(d2c_color_bar_chart, {
        type: 'pie',
        data: {
            labels: [
                'Red',
                'Blue',
                'Yellow'
              ],
              datasets: [{
                data: [300, 50, 100],
                borderWidth: 0,
                backgroundColor: [
                  'rgba(63, 81, 181, 0.8)',
                  'rgb(224 203 2 / 47%)',
                  'rgba(156, 39, 176, 0.6)'
                ],
                hoverOffset: 4
              }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
            
    });
}d2c_color_bar_chart();

// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */