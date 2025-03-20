
// world map
const map= new jsVectorMap({
  map: "world_mill",
  selector: "#world_map",
  zoomButtons: false,
  zoomOnScroll: true,
    
  // regionStyle: {
  //   initial: {
  //     fill: "#fff"
  //   }
  // },
});

// usa map
const usa_map= new jsVectorMap({
  map: "us_aea",
  selector: "#usa_map",
  zoomButtons: false,
  zoomOnScroll: true,
});

// canada map
const canada_map= new jsVectorMap({
  map: "ca_lcc",
  selector: "#canada_map",
  zoomButtons: false,
  zoomOnScroll: true,
});

// australia map
const australia_map= new jsVectorMap({
  map: "au_mill",
  selector: "#australia_map",
  zoomButtons: false,
  zoomOnScroll: true,
});