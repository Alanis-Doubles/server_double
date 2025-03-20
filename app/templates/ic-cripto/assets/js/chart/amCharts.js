//================================== 
// column with label Chart 
// ==================================
(() => {
    'use strict';
    am5.ready(function() {
        var root = am5.Root.new("column_with_label");
        // Set themes
        var myTheme = am5.Theme.new(root);
        myTheme.rule("Label").setAll({
          fill: am5.color("#a8a8a8"),
          fontFamily: "Roboto",
          fontSize: "12px",
        });
        root.setThemes([
            am5themes_Animated.new(root),
            am5themes_Responsive.new(root),
            myTheme
        ]);
        // responsive
        const responsive = am5themes_Responsive.new(root);
        responsive.addRule({
          relevant: am5themes_Responsive.widthM,
          applying: function() {
            chart.set("layout", root.verticalLayout);
            legend.setAll({
              y: null,
              centerY: null,
              x: am5.p0,
              centerX: am5.p0
            });
          },
          removing: function() {
            chart.set("layout", root.horizontalLayout);
            legend.setAll({
              y: am5.p50,
              centerY: am5.p50,
              x: null,
              centerX: null
            });
          }
        });
        // Create chart
  
        var chart = root.container.children.push(am5xy.XYChart.new(root, {
            panX: true,
            panY: true,
            wheelX: "panX",
            wheelY: "zoomX",
            pinchZoomX: true
        }));

        // Add cursor
        var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
        cursor.lineY.set("visible", false);


        // Create axes
        var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });

        xRenderer.grid.template.setAll({
            location: 1
        })

        var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
            maxDeviation: 0.3,
            categoryField: "country",
            renderer: xRenderer,
            tooltip: am5.Tooltip.new(root, {})
        }));

        var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
            maxDeviation: 0.3,
            renderer: am5xy.AxisRendererY.new(root, {
            strokeOpacity: 0.1
            })
        }));


        // Create series
        var series = chart.series.push(am5xy.ColumnSeries.new(root, {
            name: "Series 1",
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "value",
            sequencedInterpolation: true,
            categoryXField: "country",
            tooltip: am5.Tooltip.new(root, {
              labelText: "{valueY}"
            })
        }));

        series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5, strokeOpacity: 0 });
        series.columns.template.adapters.add("fill", function(fill, target) {
            return chart.get("colors").getIndex(series.columns.indexOf(target));
        });

        series.columns.template.adapters.add("stroke", function(stroke, target) {
            return chart.get("colors").getIndex(series.columns.indexOf(target));
        });

        // Set data
        var data = [{
            country: "USA",
            value: 2025,
        }, {
            country: "China",
            value: 1882
        }, {
            country: "Japan",
            value: 1809
        }, {
            country: "Germany",
            value: 1322
        }, {
            country: "UK",
            value: 1122
        }, {
            country: "France",
            value: 1114
        }, {
            country: "India",
            value: 984
        }, {
            country: "Spain",
            value: 711
        }, {
            country: "Poland",
            value: 665
        }, {
            country: "Finland",
            value: 443
        }, {
            country: "Canada",
            value: 441
        }];
        xAxis.data.setAll(data);
        series.data.setAll(data);
        // Make stuff animate on load
        series.appear(1000);
        chart.appear(1000, 100);
    }); // end am5.ready()
})();


//================================== 
// Clustered Column Chart 
// ==================================
am5.ready(function() {
    // Create root element
    var root = am5.Root.new("d2c_column_chart");
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    // Create chart
    var chart = root.container.children.push(am5xy.XYChart.new(root, {
        panX: false,
        panY: false,
        wheelX: "panX",
        wheelY: "zoomX",
        layout: root.verticalLayout
    }));
    // Add legend
    var legend = chart.children.push(
        am5.Legend.new(root, {
        centerX: am5.p50,
        x: am5.p50
        })
    );
    var data = [{
        "year": "2021",
        "europe": 2.5,
        "namerica": 2.5,
        "asia": 2.1,
        "lamerica": 1,
        "meast": 0.8,
        "africa": 0.4
    }, {
        "year": "2022",
        "europe": 2.6,
        "namerica": 2.7,
        "asia": 2.2,
        "lamerica": 0.5,
        "meast": 0.4,
        "africa": 0.3
    }, {
        "year": "2023",
        "europe": 2.8,
        "namerica": 2.9,
        "asia": 2.4,
        "lamerica": 0.3,
        "meast": 0.9,
        "africa": 0.5
    }]
    // Create axes
    var xRenderer = am5xy.AxisRendererX.new(root, {
        cellStartLocation: 0.1,
        cellEndLocation: 0.9
    })

    var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
        categoryField: "year",
        renderer: xRenderer,
        tooltip: am5.Tooltip.new(root, {})
    }));

    xRenderer.grid.template.setAll({
        location: 1
    })

    xAxis.data.setAll(data);

    var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
        renderer: am5xy.AxisRendererY.new(root, {
        strokeOpacity: 0.1
        })
    }));
    // Add series
    function makeSeries(name, fieldName) {
        var series = chart.series.push(am5xy.ColumnSeries.new(root, {
        name: name,
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: fieldName,
        categoryXField: "year"
        }));

        series.columns.template.setAll({
        tooltipText: "{name}, {categoryX}:{valueY}",
        width: am5.percent(90),
        tooltipY: 0,
        strokeOpacity: 0
        });

        series.data.setAll(data);
        // Make stuff animate on load
        series.appear();

        series.bullets.push(function() {
        return am5.Bullet.new(root, {
            locationY: 0,
            sprite: am5.Label.new(root, {
            text: "{valueY}",
            fill: root.interfaceColors.get("alternativeText"),
            centerY: 0,
            centerX: am5.p50,
            populateText: true
            })
        });
        });

        legend.data.push(series);
    }

    makeSeries("Europe", "europe");
    makeSeries("North America", "namerica");
    makeSeries("Asia", "asia");
    makeSeries("Latin America", "lamerica");
    makeSeries("Middle East", "meast");
    makeSeries("Africa", "africa");
    // Make stuff animate on load
    chart.appear(3000, 100);

}); // end am5.ready()


//================================== 
// stacked bar Chart 
// ==================================
am5.ready(function() {


    // Create root element
    var root = am5.Root.new("stacked_bar_chart");
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    
    myTheme.rule("Grid", ["base"]).setAll({
      strokeOpacity: 0.1
    });
    
    // Create chart
    var chart = root.container.children.push(am5xy.XYChart.new(root, {
      panX: false,
      panY: false,
      wheelX: "panY",
      wheelY: "zoomY",
      layout: root.verticalLayout
    }));
    
    // Add scrollbarscrollbars/
    chart.set("scrollbarY", am5.Scrollbar.new(root, {
      orientation: "vertical"
    }));
    
    var data = [{
      "year": "2021",
      "europe": 2.5,
      "namerica": 2.5,
      "asia": 2.1,
      "lamerica": 1,
      "meast": 0.8,
      "africa": 0.4
    }, {
      "year": "2022",
      "europe": 2.6,
      "namerica": 2.7,
      "asia": 2.2,
      "lamerica": 0.5,
      "meast": 0.4,
      "africa": 0.3
    }, {
      "year": "2023",
      "europe": 2.8,
      "namerica": 2.9,
      "asia": 2.4,
      "lamerica": 0.3,
      "meast": 0.9,
      "africa": 0.5
    }]
    
    // Create axes
    var yRenderer = am5xy.AxisRendererY.new(root, {});
    var yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
      categoryField: "year",
      renderer: yRenderer,
      tooltip: am5.Tooltip.new(root, {})
    }));
    
    yRenderer.grid.template.setAll({
      location: 1
    })
    
    yAxis.data.setAll(data);
    
    var xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
      min: 0,
      renderer: am5xy.AxisRendererX.new(root, {
        strokeOpacity: 0.1
      })
    }));
    
    // Add legend
    var legend = chart.children.push(am5.Legend.new(root, {
      centerX: am5.p50,
      x: am5.p50
    }));
    
    
    // Add series
    function makeSeries(name, fieldName) {
      var series = chart.series.push(am5xy.ColumnSeries.new(root, {
        name: name,
        stacked: true,
        xAxis: xAxis,
        yAxis: yAxis,
        baseAxis: yAxis,
        valueXField: fieldName,
        categoryYField: "year"
      }));
      series.columns.template.setAll({
        tooltipText: "{name}, {categoryY}: {valueX}",
        tooltipY: am5.percent(90)
      });
      series.data.setAll(data);
    
      // Make stuff animate on load
      series.appear();
      series.bullets.push(function() {
        return am5.Bullet.new(root, {
          sprite: am5.Label.new(root, {
            text: "{valueX}",
            fill: root.interfaceColors.get("alternativeText"),
            centerY: am5.p50,
            centerX: am5.p50,
            populateText: true
          })
        });
      });
      legend.data.push(series);
    }
    
    makeSeries("Europe", "europe");
    makeSeries("North America", "namerica");
    makeSeries("Asia", "asia");
    makeSeries("Latin America", "lamerica");
    makeSeries("Middle East", "meast");
    makeSeries("Africa", "africa");
    
    // Make stuff animate on load
    chart.appear(1000, 100);
}); // end am5.ready()


//================================== 
// Pie Chart 
// ==================================
am5.ready(function() {
    // Create root element
    var root = am5.Root.new("am_pie_chart");
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    // Create chart
    var chart = root.container.children.push(
      am5percent.PieChart.new(root, {
        endAngle: 270
      })
    );
    // Create series
    var series = chart.series.push(
      am5percent.PieSeries.new(root, {
        valueField: "value",
        categoryField: "category",
        endAngle: 270
      })
    );
    series.states.create("hidden", {
      endAngle: -90
    });
    // Set data
    series.data.setAll([{
      category: "Lithuania",
      value: 501.9
    }, {
      category: "Czechia",
      value: 301.9
    }, {
      category: "Ireland",
      value: 201.1
    }, {
      category: "Germany",
      value: 165.8
    }, {
      category: "Australia",
      value: 139.9
    }, {
      category: "Austria",
      value: 128.3
    }, {
      category: "UK",
      value: 99
    }]);
    series.appear(1000, 100);
}); // end am5.ready()

//================================== 
// variable radius pie chart
// ==================================

am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_pie_chart_radius");
  
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    
    // Create chart
    var chart = root.container.children.push(am5percent.PieChart.new(root, {
      layout: root.verticalLayout
    }));
    
    // Create series
    var series = chart.series.push(am5percent.PieSeries.new(root, {
      alignLabels: true,
      calculateAggregates: true,
      valueField: "value",
      categoryField: "category"
    }));
    series.labelsContainer.set("paddingTop", 30)
    // Set up adapters for variable slice radius
    series.slices.template.adapters.add("radius", function (radius, target) {
      var dataItem = target.dataItem;
      var high = series.getPrivate("valueHigh");
    
      if (dataItem) {
        var value = target.dataItem.get("valueWorking", 0);
        return radius * value / high
      }
      return radius;
    });
    // Set data
    series.data.setAll([{
      value: 10,
      category: "One"
    }, {
      value: 9,
      category: "Two"
    }, {
      value: 6,
      category: "Three"
    }, {
      value: 5,
      category: "Four"
    }, {
      value: 4,
      category: "Five"
    }, {
      value: 3,
      category: "Six"
    }]);
    
    // Create legend
    var legend = chart.children.push(am5.Legend.new(root, {
      centerX: am5.p50,
      x: am5.p50,
      marginTop: 15,
      marginBottom: 15
    }));
    legend.data.setAll(series.dataItems);
    
    // Play initial series animation
    series.appear(1000, 100);
}); // end am5.ready()


//================================== 
// Donut Chart 
// ==================================

am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_donut_Chart");
    
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#3557d4"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    
    // Create chart
    // https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/
    var chart = root.container.children.push(
      am5percent.PieChart.new(root, {
        startAngle: 160, endAngle: 380
      })
    );
    
    // Create series
    // https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
    
    var series0 = chart.series.push(
      am5percent.PieSeries.new(root, {
        valueField: "litres",
        categoryField: "country",
        startAngle: 160,
        endAngle: 380,
        radius: am5.percent(70),
        innerRadius: am5.percent(65)
      })
    );
    
    var colorSet = am5.ColorSet.new(root, {
      colors: [series0.get("colors").getIndex(0)],
      passOptions: {
        lightness: -0.05,
        hue: 0
      }
    });
    
    series0.set("colors", colorSet);
    
    series0.ticks.template.set("forceHidden", true);
    series0.labels.template.set("forceHidden", true);
    
    var series1 = chart.series.push(
      am5percent.PieSeries.new(root, {
        startAngle: 160,
        endAngle: 380,
        valueField: "bottles",
        innerRadius: am5.percent(80),
        categoryField: "country"
      })
    );
    
    series1.ticks.template.set("forceHidden", true);
    series1.labels.template.set("forceHidden", true);
    
    var label = chart.seriesContainer.children.push(
      am5.Label.new(root, {
        textAlign: "center",
        centerY: am5.p100,
        centerX: am5.p50,
        text: "[fontSize:18px]total[/]:\n[bold fontSize:30px]1647.9[/]"
      })
    );
    
    var data = [
      {
        country: "Lithuania",
        litres: 501.9,
        bottles: 1500
      },
      {
        country: "Czech Republic",
        litres: 301.9,
        bottles: 990
      },
      {
        country: "Ireland",
        litres: 201.1,
        bottles: 785
      },
      {
        country: "Germany",
        litres: 165.8,
        bottles: 255
      },
      {
        country: "Australia",
        litres: 139.9,
        bottles: 452
      },
      {
        country: "Austria",
        litres: 128.3,
        bottles: 332
      },
      {
        country: "UK",
        litres: 99,
        bottles: 150
      },
      {
        country: "Belgium",
        litres: 60,
        bottles: 178
      },
      {
        country: "The Netherlands",
        litres: 50,
        bottles: 50
      }
    ];
    
    // Set data
    // https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Setting_data
    series0.data.setAll(data);
    series1.data.setAll(data);
    
}); // end am5.ready()


//================================== 
// globe selected country Chart 
// ==================================
am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_select_country");
    
    
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    
    
    // Create the map chart
    // https://www.amcharts.com/docs/v5/charts/map-chart/
    var chart = root.container.children.push(am5map.MapChart.new(root, {
      panX: "rotateX",
      panY: "rotateY",
      projection: am5map.geoOrthographic(),
      paddingBottom: 20,
      paddingTop: 20,
      paddingLeft: 20,
      paddingRight: 20
    }));
    
    
    // Create main polygon series for countries
    // https://www.amcharts.com/docs/v5/charts/map-chart/map-polygon-series/
    var polygonSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {
      geoJSON: am5geodata_worldLow 
    }));
    
    polygonSeries.mapPolygons.template.setAll({
      tooltipText: "{name}",
      toggleKey: "active",
      interactive: true
    });
    
    polygonSeries.mapPolygons.template.states.create("hover", {
      fill: root.interfaceColors.get("primaryButtonHover")
    });
    
    polygonSeries.mapPolygons.template.states.create("active", {
      fill: root.interfaceColors.get("primaryButtonHover")
    });
    
    
    // Create series for background fill
    // https://www.amcharts.com/docs/v5/charts/map-chart/map-polygon-series/#Background_polygon
    var backgroundSeries = chart.series.push(am5map.MapPolygonSeries.new(root, {}));
    backgroundSeries.mapPolygons.template.setAll({
      fill: root.interfaceColors.get("alternativeBackground"),
      fillOpacity: 0.1,
      strokeOpacity: 0
    });
    backgroundSeries.data.push({
      geometry: am5map.getGeoRectangle(90, 180, -90, -180)
    });
    
    
    // Set up events
    var previousPolygon;
    
    polygonSeries.mapPolygons.template.on("active", function(active, target) {
      if (previousPolygon && previousPolygon != target) {
        previousPolygon.set("active", false);
      }
      if (target.get("active")) {
        selectCountry(target.dataItem.get("id"));
      }
      previousPolygon = target;
    });
    
    function selectCountry(id) {
      var dataItem = polygonSeries.getDataItemById(id);
      var target = dataItem.get("mapPolygon");
      if (target) {
        var centroid = target.geoCentroid();
        if (centroid) {
          chart.animate({ key: "rotationX", to: -centroid.longitude, duration: 1500, easing: am5.ease.inOut(am5.ease.cubic) });
          chart.animate({ key: "rotationY", to: -centroid.latitude, duration: 1500, easing: am5.ease.inOut(am5.ease.cubic) });
        }
      }
    }
    
    // Uncomment this to pre-center the globe on a country when it loads
    //polygonSeries.events.on("datavalidated", function() {
    //  selectCountry("AU");
    //});
    
    
    // Make stuff animate on load
    chart.appear(1000, 100);
    
}); // end am5.ready()


//================================== 
// Bubble Chart 
// ==================================
am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_bubble_chart");
    
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    
    // Create chart
    var chart = root.container.children.push(am5xy.XYChart.new(root, {
      panX: true,
      panY: true,
      wheelY: "zoomXY",
      pinchZoomX:true,
      pinchZoomY:true
    }));
    
    chart.get("colors").set("step", 2);
    
    // Create axes
    var xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
      renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 50 }),
      tooltip: am5.Tooltip.new(root, {})
    }));
    
    var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
      renderer: am5xy.AxisRendererY.new(root, {}),
      tooltip: am5.Tooltip.new(root, {})
    }));
    
    // Create series
    var series0 = chart.series.push(am5xy.LineSeries.new(root, {
      calculateAggregates: true,
      xAxis: xAxis,
      yAxis: yAxis,
      valueYField: "y",
      valueXField: "x",
      valueField: "value",
      tooltip: am5.Tooltip.new(root, {
        labelText: "x: {valueX}, y: {valueY}, value: {value}"
      })
    }));
    
    
    // Add bullet#Bullets
    var circleTemplate = am5.Template.new({});
    series0.bullets.push(function() {
      var graphics = am5.Circle.new(root, {
        fill: series0.get("fill"),
      }, circleTemplate);
      return am5.Bullet.new(root, {
        sprite: graphics
      });
    });
    
    // Add heat rule
    // https://www.amcharts.com/docs/v5/concepts/settings/heat-rules/
    series0.set("heatRules", [{
      target: circleTemplate,
      min: 3,
      max: 35,
      dataField: "value",
      key: "radius"
    }]);
    
    
    // Create second series
    var series1 = chart.series.push(am5xy.LineSeries.new(root, {
      calculateAggregates: true,
      xAxis: xAxis,
      yAxis: yAxis,
      valueYField: "y2",
      valueXField: "x2",
      valueField: "value",
      tooltip: am5.Tooltip.new(root, {
        labelText: "x: {valueX}, y: {valueY}, value: {value}"
      })
    }));
    
    // Add bullet#Bullets
    var starTemplate = am5.Template.new({});
    series1.bullets.push(function() {
      var graphics = am5.Star.new(root, {
        fill: series1.get("fill"),
        spikes: 8,
        innerRadius: am5.percent(70),
      }, starTemplate);
      return am5.Bullet.new(root, {
        sprite: graphics
      });
    });
    
    
    // Add heat rule
    // https://www.amcharts.com/docs/v5/concepts/settings/heat-rules/
    series1.set("heatRules", [{
      target: starTemplate,
      min: 3,
      max: 50,
      dataField: "value",
      key: "radius"
    }]);
    
    
    series0.strokes.template.set("strokeOpacity", 0);
    series1.strokes.template.set("strokeOpacity", 0);
    
    // Add cursor
    chart.set("cursor", am5xy.XYCursor.new(root, {
      xAxis: xAxis,
      yAxis: yAxis,
      snapToSeries: [series0, series1]
    }));
    
    // Add scrollbarsscrollbars/
    chart.set("scrollbarX", am5.Scrollbar.new(root, {
      orientation: "horizontal"
    }));
    
    chart.set("scrollbarY", am5.Scrollbar.new(root, {
      orientation: "vertical"
    }));
    
    
    var data = [{
      "y": 10,
      "x": 14,
      "value": 59,
      "y2": -5,
      "x2": -3,
      "value2": 44
    }, {
      "y": 5,
      "x": 3,
      "value": 50,
      "y2": -15,
      "x2": -8,
      "value2": 12
    }, {
      "y": -10,
      "x": 8,
      "value": 19,
      "y2": -4,
      "x2": 6,
      "value2": 35
    }, {
      "y": -6,
      "x": 5,
      "value": 65,
      "y2": -5,
      "x2": -6,
      "value2": 168
    }, {
      "y": 15,
      "x": -4,
      "value": 92,
      "y2": -10,
      "x2": -8,
      "value2": 102
    }, {
      "y": 13,
      "x": 1,
      "value": 8,
      "y2": -2,
      "x2": 0,
      "value2": 41
    }, {
      "y": 1,
      "x": 6,
      "value": 35,
      "y2": 0,
      "x2": -3,
      "value2": 16
    }]
    series0.data.setAll(data);
    series1.data.setAll(data);
    // Make stuff animate on load
    series0.appear(1000);
    series1.appear(1000);
    
    chart.appear(1000, 100);
    
}); // end am5.ready()

//================================== 
// curved columns Chart 
// ==================================
am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_curved_columns");
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    // Create chart
    var chart = root.container.children.push(
      am5xy.XYChart.new(root, {
        panX: true,
        panY: true,
        wheelX: "panX",
        wheelY: "zoomX"
      })
    );
    // Add cursor
    var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
    cursor.lineY.set("visible", false);
    
    // Create axes
    var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
    
    var xAxis = chart.xAxes.push(
      am5xy.CategoryAxis.new(root, {
        maxDeviation: 0.3,
        categoryField: "country",
        renderer: xRenderer,
        tooltip: am5.Tooltip.new(root, {})
      })
    );
    
    xRenderer.grid.template.setAll({
      location: 1
    })
    
    var yAxis = chart.yAxes.push(
      am5xy.ValueAxis.new(root, {
        maxDeviation: 0.3,
        renderer: am5xy.AxisRendererY.new(root, {
          strokeOpacity: 0.1
        })
      })
    );
    
    // Create series
    var series = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "Series 1",
        xAxis: xAxis,
        yAxis: yAxis,
        valueYField: "value",
        sequencedInterpolation: true,
        categoryXField: "country"
      })
    );
    
    series.columns.template.setAll({
      width: am5.percent(120),
      fillOpacity: 0.9,
      strokeOpacity: 0
    });
    series.columns.template.adapters.add("fill", (fill, target) => {
      return chart.get("colors").getIndex(series.columns.indexOf(target));
    });
    
    series.columns.template.adapters.add("stroke", (stroke, target) => {
      return chart.get("colors").getIndex(series.columns.indexOf(target));
    });
    
    series.columns.template.set("draw", function(display, target) {
      var w = target.getPrivate("width", 0);
      var h = target.getPrivate("height", 0);
      display.moveTo(0, h);
      display.bezierCurveTo(w / 4, h, w / 4, 0, w / 2, 0);
      display.bezierCurveTo(w - w / 4, 0, w - w / 4, h, w, h);
    });
    
    // Set data
    var data = [{
      country: "USA",
      value: 2025
    }, {
      country: "China",
      value: 1882
    }, {
      country: "Japan",
      value: 1809
    }, {
      country: "Germany",
      value: 1322
    }, {
      country: "UK",
      value: 1122
    }, {
      country: "France",
      value: 1114
    }, {
      country: "India",
      value: 984
    }];
    
    xAxis.data.setAll(data);
    series.data.setAll(data);
    
    // Make stuff animate on load
    series.appear(1000);
    chart.appear(1000, 100);
    
}); // end am5.ready()


//================================== 
// Radial Histogram Chart 
// ==================================
am5.ready(function() {

    // Create root element
    var root = am5.Root.new("am_radial_histogram");
    // Set themes
    var myTheme = am5.Theme.new(root);
    myTheme.rule("Label").setAll({
      fill: am5.color("#a8a8a8"),
      fontFamily: "Roboto",
      fontSize: "12px",
    });
    root.setThemes([
        am5themes_Animated.new(root),
        myTheme
    ]);
    // Create chart
    var chart = root.container.children.push(am5radar.RadarChart.new(root, {
      panX: false,
      panY: false,
      wheelX: "none",
      wheelY: "none",
      startAngle: -84,
      endAngle: 264,
      innerRadius: am5.percent(40)
    }));
    // Add cursor
    const cursor = chart.set("cursor", am5radar.RadarCursor.new(root, {
      behavior: "zoomX"
    }));
    cursor.lineY.set("forceHidden", true);
    // Add scrollbar
    chart.set("scrollbarX", am5.Scrollbar.new(root, {
      orientation: "horizontal",
      exportable: false
    }));
    // Create axes
    var xRenderer = am5radar.AxisRendererCircular.new(root, {
      minGridDistance: 30
    });
    xRenderer.grid.template.set("forceHidden", true);
    var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
      maxDeviation: 0,
      categoryField: "category",
      renderer: xRenderer
    }));
    var yRenderer = am5radar.AxisRendererRadial.new(root, {});
    yRenderer.labels.template.set("centerX", am5.p50);
    var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
      maxDeviation: 0.3,
      min: 0,
      renderer: yRenderer
    }));
    // Add series
    var series = chart.series.push(am5radar.RadarColumnSeries.new(root, {
      name: "Series 1",
      sequencedInterpolation: true,
      xAxis: xAxis,
      yAxis: yAxis,
      valueYField: "value",
      categoryXField: "category"
    }));
    // Rounded corners for columns
    series.columns.template.setAll({
      cornerRadius: 5,
      tooltipText: "{categoryX}: {valueY}"
    });
    // Make each column to be of a different color
    series.columns.template.adapters.add("fill", function (fill, target) {
      return chart.get("colors").getIndex(series.columns.indexOf(target));
    });
    series.columns.template.adapters.add("stroke", function (stroke, target) {
      return chart.get("colors").getIndex(series.columns.indexOf(target));
    });
    // Set data
    var data = [];
    for (var i = 1; i < 21; i++) {
      data.push({ category: i, value: Math.round(Math.random() * 100) });
    }
    xAxis.data.setAll(data);
    series.data.setAll(data);
    // Make stuff animate on load
    series.appear(1000);
    chart.appear(1000, 100);
}); // end am5.ready()


// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */