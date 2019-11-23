var timeout = null

var callAjax = function() {
    var filter = $("#filter").val();
    var action = $("form").attr('action');
    var checkbox = new Object();
    if ($('#users').is(":checked")) checkbox.users = true;
    if ($('#repositories').is(":checked")) checkbox.repositories = true;

    clearTimeout(timeout)
    timeout = setTimeout(function () {
        if (filter.length > 3) {
            var form_data = new FormData();
            form_data.append("ajax-search", true);
            form_data.append("filter", filter);
            form_data.append("checkbox", JSON.stringify(checkbox));

            $.ajax(
                {
                    url: action,
                    data: form_data,// the formData function is available in almost all new browsers.
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    cache: false,
                    error: function (err) {
                        console.log(err);
                    },
                    success: function (value) {
                        $("#nbResult").html(value.total);
                        $("#previewResult").empty();
                        $.each(value.datas, function( index, value ) {
                            $( "#previewResult" ).append( '<li class="list-group-item"><a target="_blank" href="/search-detail/'+value.username+'/'+value.name+'">'+value.name+'</a> - '+value.description+'</li>' );
                        });
                    },
                }
            );
        }
    }, 500)
}

$(function () {

    $("#filter").on('keyup', function () {
        callAjax();
    });

    $("#checkbox-group").on('change', function () {
        callAjax();
    });
});
