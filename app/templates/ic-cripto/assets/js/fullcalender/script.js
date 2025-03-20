document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
  
    var calendar = new FullCalendar.Calendar(calendarEl, {
      headerToolbar: {
        left: 'today',
        center: 'title',
        right: 'prev,next'
      },
      initialDate: '2023-04-12',
      navLinks: true, // can click day/week names to navigate views
      nowIndicator: true,
  
      weekNumbers: false,
      weekNumberCalculation: 'ISO',
  
      editable: true,
      selectable: true,
      dayMaxEvents: true,
      padding: 20,
      events: [
        {
          title: 'All Day Event',
          start: '2023-04-01',
          color: '#3557d4'
        },
        {
          title: 'Long Event',
          start: '2023-04-07',
          end: '2023-01-10',
          color: '#6c757d'
        },
        {
          title: 'Long Event',
          start: '2023-04-16',
          end: '2023-01-10',
          color: '#327ad1'
        },
        {
          title: 'Long Event',
          start: '2023-04-24',
          end: '2023-01-10',
          color: '#981957'
        },
      ],
    });
  
    calendar.render();
  });

  // /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */