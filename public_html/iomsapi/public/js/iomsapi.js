$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    /*  Add apiKey button */
    $('#create-new-apiKey').click(function () {

        $.get('apikey/generate', function (data) {
            $('#btn-save').val("create-apiKey");
            $('#apiKeyForm').trigger("reset");
            $('#apiKeyCrudModal').html("Toevoegen");
            $('#ajax-crud-modal').modal('show');
            $('#apiKey').val(data.apikey);
            $('#apikeyText').val(data.apikey);

            $('#divClientNumbers').hide();
        })
    });

    /* When click cancel in modal form */
    $('body').on('click', '#btn-cancel', function () {
            $('#ajax-crud-modal').modal('hide');
        });

    /* When click edit apiKey */
    $('body').on('click', '#edit-apiKey', function () {
        var apiKey_id = $(this).data('id');
        $.get('apikey/' + apiKey_id +'/edit', function (data) {
            $('#apiKeyCrudModal').html("Wijzigen");
            $('#btn-save').val("edit-apiKey");
            $('#ajax-crud-modal').modal('show');
            $('#apiKey_id').val(data.id);
            $('#clientName').val(data.client_name);
            // $('#clientNrs').val(data.client_nrs);
            $('#leaseDownPayment').val(data.lease_down_payment);
            $('#leaseInterestRateGeneral').val(data.lease_interest_rate_general);
            $('#leaseInterestRate').val(data.lease_interest_rate);
            $('#apiKey').val(data.api_key);
            $('#apikeyText').val(data.api_key);

            $("input[type=checkbox]").prop('checked', false);
            $('#clientNrs').val('');
            $('#divClientNumbers').hide();

            let tableData = JSON.parse(data.tables);

            if (tableData) {
                tableData.forEach(function (table) {
                    $('#' + table.tableName).prop("checked", true);
                    if (table.tableName == 'voertuigen') {
                        $('#divClientNumbers').show();
                        $('#clientNrs').val(table.clientNrs);
                    }
                    if (table.tableName == 'voertuigen_global') {
                        $('#divClientNumbers_global').show();
                        $('#clientNrs_global').val(table.clientNrs_global);
                    }
                });
            }
        })
    });

    //delete apiKey
    $('body').on('click', '.delete-apiKey', function () {
        var apiKey_id = $(this).data("id");
        if (confirm("Weet u zeker dat u deze klant wilt verwijderen?")) {

            $.ajax({
                type: "DELETE",
                url: "apikey" + '/' + apiKey_id,
                success: function (data) {
                    $("#apiKey_id_" + apiKey_id).remove();
                },
                error: function (data) {
                    console.log('Error:', data);
                }
            });
        }
    });

    //delete apiKey
    $('body').on('change', '#voertuigen', function () {
        if (this.checked) {
            $('#divClientNumbers').show();
        } else {
            $('#divClientNumbers').hide();
        }
    });

    if ($("#apiKeyForm").length > 0) {
        $("#apiKeyForm").validate({

            submitHandler: function(form) {

                var actionType = $('#btn-save').val();
                $('#btn-save').html('Sending..');

                $.ajax({
                    data: $('#apiKeyForm').serialize(),
                    url: "apikey",
                    type: "POST",
                    dataType: 'json',
                    success: function (data) {
                        var apiKey = '<tr id="apiKey_id_' + data.id + '"><td>' + data.id + '</td><td>' + data.client_name + '</td><td>' + data.api_key + '</td>';
                        apiKey += '<td><a href="javascript:void(0)" id="edit-apiKey" data-id="' + data.id + '" class="btn btn-info">Wijzigen</a></td>';
                        apiKey += '<td><a href="javascript:void(0)" id="delete-apiKey" data-id="' + data.id + '" class="btn btn-danger delete-apiKey">Verwijderen</a></td></tr>';


                        if (actionType == "create-apiKey") {
                            $('#apiKeys-crud').prepend(apiKey);
                        } else {
                            $("#apiKey_id_" + data.id).replaceWith(apiKey);
                        }

                        $('#apiKeyForm').trigger("reset");
                        $('#ajax-crud-modal').modal('hide');
                        $('#btn-save').html('Opslaan');

                    },
                    error: function (data) {
                        console.log('Error:', data);
                        $('#btn-save').html('Opslaan');
                    }
                });
            }
        })
    }

});
