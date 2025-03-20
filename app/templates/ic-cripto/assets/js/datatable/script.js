$(function () {
    
    const table1 = '#d2c_advanced_table';
    const table3 = '#d2c_trade_table';
    const table4 = '#d2c_wallet_activity_table';
    const table5 = '#d2c_crypto_currency';
    const table6 = '#d2c_table_with_pagination';
    const table7 = '#d2c_table_with_pagination_search';
    const table8 = '#d2c_datatable';

    if (table1) {
        new DataTable(table1);
    }

    if (table3) {
        new DataTable(table3);
    }

    if (table4) {
        new DataTable(table4);
    }

    if (table5) {
        new DataTable(table5);
    }

    if (table6) {
        new DataTable(table6);
    }

    if (table7) {
        new DataTable(table7);
    }

    if (table8) {
        new DataTable(table8);
    }

});

$(document).ready(function() {
    // all activity table
    new DataTable('#d2c_activity_table');
    // withdraw table
    new DataTable('#d2c_withdraw_table');
    // deposit table
    new DataTable('#d2c_deposit_table');
    // vertical scroll table
    new DataTable('#d2c_vertical_scroll', {
        paging: false,
        scrollCollapse: true,
        scrollY: '330px'
    });
    // complex header
    new DataTable('#d2c_complex_header', {
        columnDefs: [
            {
                targets: -1,
                visible: false
            }
        ]
    });
    // checkbox selection
    new DataTable('#d2c_selection_control', {
        dom: 'Bfrtip',
        info:false,
        buttons: [
            'selectAll',
            'selectNone',
            'selectRows',
            'selectColumns',
            'selectCells'
        ],
        select: {
            style: 'multi'
        }
    });
    // assets balance data table
    new DataTable('#d2c_asset_balance_table', {});
    
} );

// delete row table
const table = new DataTable('#d2c_delete_row');
 
table.on('click', 'tbody tr', (e) => {
    let classList = e.currentTarget.classList;
 
    if (classList.contains('selected')) {
        classList.remove('selected');
    }
    else {
        table.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        classList.add('selected');
    }
    document.querySelector('#button').addEventListener('click', function () {
        table.row('.selected').remove().draw(false);
    });
});
 


// /* 
// Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
// Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
// Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
// Author:        DesignToCodes
// Author URI:    https://www.designtocodes.com
// Text Domain:   IC Crypto
// */