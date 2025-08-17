jQuery(document).ready(function($) {
    let timeout;
    let currentPage = 1;

    $('#city-search').on('keyup', function() {
        clearTimeout(timeout);
        const search = $(this).val();
        currentPage = 1;

        timeout = setTimeout(function() {
            updateTable(search, currentPage);
        }, 300);
    });

    $('body').on('click', '.pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const urlParams = new URLSearchParams(href.split('?')[1] || '');
        currentPage = urlParams.get('paged') || 1;
        const search = $('#city-search').val();
        updateTable(search, currentPage);
    });

    function updateTable(search, page) {
        $.ajax({
            url: ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'cities_search',
                nonce: ajax_params.nonce,
                search: search,
                paged: page
            },
            dataType: 'json',
            success: function(response) {
                $('#cities-table tbody').html(response.table_rows);
                $('.pagination').html(response.pagination);
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', error);
            }
        });
    }
});