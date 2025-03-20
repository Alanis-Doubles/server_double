
// basic bar chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('bar_chart'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
        xAxis: {
            type: 'category',
            data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            borderColor: '#000',
        },
        yAxis: {
          type: 'value',
          splitLine: {
            lineStyle: {
                color: '#00000014'
            }
          }
        },
        series: [
            {
              data: [120, 200, 150, 80, 70, 110, 130],
              type: 'bar'
            }
        ],
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// radial polar bar chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('radial_polar_bar'));

    // Specify the configuration items and data for the chart
    var option = {
        polar: {
            radius: [30, '80%']
        },
        radiusAxis: {
            max: 4
        },
        angleAxis: {
            type: 'category',
            data: ['a', 'b', 'c', 'd'],
            startAngle: 75
        },
        tooltip: {},
        series: {
            type: 'bar',
            data: [2, 1.2, 2.4, 3.6],
            coordinateSystem: 'polar',
            label: {
                show: true,
                position: 'middle',
                formatter: '{b}: {c}'
            }
        },
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();


// basic line chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('line_chart_1'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
        xAxis: {
          type: 'category',
          data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        },
        yAxis: {
          type: 'value',
          splitLine: {
            lineStyle: {
                color: '#00000014'
            }
          }
        },
        series: [
          {
            data: [150, 230, 224, 218, 135, 147, 260],
            type: 'line'
          }
        ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// area chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('area_chart'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        },
        yAxis: {
            type: 'value',
            splitLine: {
              lineStyle: {
                  color: '#00000014'
              }
            }
        },
        series: [
            {
                data: [820, 932, 901, 934, 1290, 1330, 1320],
                type: 'line',
                areaStyle: {}
            }
        ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// Doughnut chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('doughnut_chart'));

    // Specify the configuration items and data for the chart
    var option = {
        tooltip: {
            trigger: 'item'
        },
        legend: {
            show: false
        },
        series: [
            {
                name: 'Access From',
                type: 'pie',
                radius: ['40%', '70%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 10,
                    borderColor: '#00000014',
                    borderWidth: 2
                },
                label: {
                    show: false,
                    position: 'center'
                },
                emphasis: {
                    label: {
                        show: true,
                        fontSize: 20,
                        fontWeight: 'bold'
                    }
                },
                labelLine: {
                    show: false
                },
                data: [
                    { value: 1048, name: 'Search Engine' },
                    { value: 735, name: 'Direct' },
                    { value: 580, name: 'Email' },
                    { value: 484, name: 'Union Ads' },
                    { value: 300, name: 'Video Ads' }
                ]
            }
        ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// Radar chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('radar_chart'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
          legend: {
            show: false,
          },
          radar: {
            // shape: 'circle',
            indicator: [
              { name: 'Sales', max: 6500 },
              { name: 'Administration', max: 16000 },
              { name: 'Information Technology', max: 30000 },
              { name: 'Customer Support', max: 38000 },
              { name: 'Development', max: 52000 },
              { name: 'Marketing', max: 25000 }
            ]
          },
          series: [
            {
              name: 'Budget vs spending',
              type: 'radar',
              data: [
                {
                  value: [4200, 3000, 20000, 35000, 50000, 18000],
                  name: 'Allocated Budget'
                },
                {
                  value: [5000, 14000, 28000, 26000, 42000, 21000],
                  name: 'Actual Spending'
                }
              ]
            }
          ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// Radar chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('stacked_bar_chart'));

    // Specify the configuration items and data for the chart
    var option = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
              // Use axis to trigger tooltip
              type: 'shadow' // 'shadow' as default; can also be 'line' or 'shadow'
            }
          },
          legend: {
            show:false
          },
          grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
          },
          xAxis: {
            type: 'value',
            splitLine: {
              lineStyle: {
                  color: '#00000014'
              }
            }
          },
          yAxis: {
            type: 'category',
            data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            splitLine: {
              lineStyle: {
                  color: '#00000014'
              }
            }
          },
          series: [
            {
              name: 'Direct',
              type: 'bar',
              stack: 'total',
              label: {
                show: true
              },
              emphasis: {
                focus: 'series'
              },
              data: [320, 302, 301, 334, 390, 330, 320]
            },
            {
              name: 'Mail Ad',
              type: 'bar',
              stack: 'total',
              label: {
                show: true
              },
              emphasis: {
                focus: 'series'
              },
              data: [120, 132, 101, 134, 90, 230, 210]
            },
            {
              name: 'Affiliate Ad',
              type: 'bar',
              stack: 'total',
              label: {
                show: true
              },
              emphasis: {
                focus: 'series'
              },
              data: [220, 182, 191, 234, 290, 330, 310]
            },
            {
              name: 'Video Ad',
              type: 'bar',
              stack: 'total',
              label: {
                show: true
              },
              emphasis: {
                focus: 'series'
              },
              data: [150, 212, 201, 154, 190, 330, 410]
            },
            {
              name: 'Search Engine',
              type: 'bar',
              stack: 'total',
              label: {
                show: true
              },
              emphasis: {
                focus: 'series'
              },
              data: [820, 832, 901, 934, 1290, 1330, 1320]
            }
          ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// scatter chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('scatter_chart'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
        xAxis: {
          splitLine: {
            lineStyle: {
                color: '#00000014'
            }
          }
        },
        yAxis: {
          splitLine: {
            lineStyle: {
                color: '#00000014'
            }
          }
        },
        series: [
          {
            symbolSize: 20,
            data: [
              [10.0, 8.04],
              [8.07, 6.95],
              [13.0, 7.58],
              [9.05, 8.81],
              [11.0, 8.33],
              [14.0, 7.66],
              [13.4, 6.81],
              [10.0, 6.33],
              [14.0, 8.96],
              [12.5, 6.82],
              [9.15, 7.2],
              [11.5, 7.2],
              [3.03, 4.23],
              [12.2, 7.83],
              [2.02, 4.47],
              [1.05, 3.33],
              [4.05, 4.96],
              [6.03, 7.24],
              [12.0, 6.26],
              [12.0, 8.84],
              [7.08, 5.82],
              [5.02, 5.68]
            ],
            type: 'scatter'
          }
        ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// Candlestick chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('candlestick_chart'));

    // Specify the configuration items and data for the chart
    var option = {
      tooltip: {
        trigger: 'item'
      },
        xAxis: {
            data: ['2017-10-24', '2017-10-25', '2017-10-26', '2017-10-27']
          },
          yAxis: {
            splitLine: {
              lineStyle: {
                  color: '#00000014'
              }
            } 
          },
          series: [
            {
              type: 'candlestick',
              data: [
                [20, 34, 10, 38],
                [40, 35, 30, 50],
                [31, 38, 33, 44],
                [38, 15, 5, 42],
              ]
            }
          ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();

// pie chart
(() => {
    'use strict';
    // Initialize the echarts instance based on the prepared dom
    var myChart = echarts.init(document.getElementById('pie_chart'));

    // Specify the configuration items and data for the chart
    var option = {
          tooltip: {
            trigger: 'item'
          },
          legend: {
            show: false,
          },
          series: [
            {
              name: 'Access From',
              type: 'pie',
              radius: '50%',
              data: [
                { value: 1048, name: 'Search Engine' },
                { value: 735, name: 'Direct' },
                { value: 580, name: 'Email' },
                { value: 484, name: 'Union Ads' },
                { value: 300, name: 'Video Ads' }
              ],
              emphasis: {
                itemStyle: {
                  shadowBlur: 10,
                  shadowOffsetX: 0,
                  shadowColor: '#fff'
                }
              }
            }
          ]
    };

    // Display the chart using the configuration items and data just specified.
    myChart.setOption(option);
})();


// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */