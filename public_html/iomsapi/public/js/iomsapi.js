$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Results per page select box
    $('#resultsPerPage').change(function () {
        window.location = '/iomsapi/apikey?resultsPerPage=' + this.value;
    });

    /*  Add apiKey button */
    $('.thSort').click(function () {
        window.location = $(this).find('a').attr('href');
    });

    /*  Add apiKey button */
    $('#create-new-apiKey').click(function () {

        $.get('apikey/generate', function (data) {
            $('#apiKeyForm').trigger("reset");
            $('#apiKeyCrudModal').html("Toevoegen");
            $('#ajax-crud-modal').modal('show');
            $('#apiKey').val(data.apikey);
            $('#apikeyText').val(data.apikey);

            $('#divClientNumbers').hide();
            $('#divClientNumbers_global').hide();
            $('#divClientNumbers_incrementeel').hide();
        })
    });

    /* When click cancel in modal form */
    $('body').on('click', '#btn-cancel', function () {
            $('#ajax-crud-modal').modal('hide');
            if (this.value === 'reload') {
                location.reload();
            }
        });

    $(document).keydown(function(e) {
        // ESCAPE key pressed
        if (e.keyCode == 27) {
            $('#ajax-crud-modal').modal('hide');
        }
    });

    /* When click edit apiKey */
    $('body').on('click', '#edit-apiKey', function () {
        var apiKey_id = $(this).data('id');
        $.get('apikey/' + apiKey_id +'/edit', function (data) {

            $("input[type=checkbox]").prop('checked', false);

            $('#apiKeyCrudModal').html("Wijzigen");
            $('#ajax-crud-modal').modal('show');
            $('#apiKey_id').val(data.id);
            $('#clientName').val(data.client_name);
            $('#leaseDownPayment').val(data.lease_down_payment);
            $('#leaseFinalPayment').val(data.lease_final_payment);
            $('#leaseDefaultTerm').val(data.lease_default_term);
            $('#leaseMaxAge').val(data.lease_max_age);
            $('#leaseMinTerm').val(data.lease_min_term);
            $('#leaseMaxTerm').val(data.lease_max_term);
            $('#leaseInterestRateGeneral').val(data.lease_interest_rate_general);
            $('#leaseInterestRate').val(data.lease_interest_rate);
            $('#use_lease_price_filter').prop("checked", data.use_lease_price_filter);
            $('#apiKey').val(data.api_key);
            $('#apikeyText').val(data.api_key);

            if (data.use_lease_price_filter) {
                $('#calculateLeasePrices').prop("disabled", "");
            }

            setTerms();

            $('#clientNrs').val('');
            $('#divClientNumbers').hide();
            $('#clientNrs_global').val('');
            $('#divClientNumbers_global').hide();
            $('#clientNrs_incrementeel').val('');
            $('#divClientNumbers_incrementeel').hide();

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
                        $('#clientNrs_global').val(table.clientNrs);
                    }
                    if (table.tableName == 'voertuigen_incrementeel') {
                        $('#divClientNumbers_incrementeel').show();
                        $('#clientNrs_incrementeel').val(table.clientNrs);
                    }
                });
            }
        })
    });

    //delete apiKey
    $('body').on('click', '.delete-apiKey', function () {
        var apiKey_id = $(this).data("id");
        if (confirm("Weet u zeker dat u klant '" + $(this).data("client-name") + "' wilt verwijderen?")) {

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

    //handle client number tables
    $('body').on('change', '#voertuigen', function () {
        if (this.checked) {
            $('#divClientNumbers').show();
        } else {
            $('#divClientNumbers').hide();
        }
    });

    $('body').on('change', '#voertuigen_global', function () {
        if (this.checked) {
            $('#divClientNumbers_global').show();
        } else {
            $('#divClientNumbers_global').hide();
        }
    });

    $('body').on('change', '#voertuigen_incrementeel', function () {
        if (this.checked) {
            $('#divClientNumbers_incrementeel').show();
        } else {
            $('#divClientNumbers_incrementeel').hide();
        }
    });

    $('body').on('change', '#leaseMaxAge', function () {
        setTerms();
    });

    if ($("#apiKeyForm").length > 0) {
        $("#apiKeyForm").validate({

            submitHandler: function(form) {

                $('#formStatus').removeClass('alert-success alert-danger').html('');
                let actionType = this.submitButton.value;

                $.ajax({
                    data: $('#apiKeyForm').serialize(),
                    url: "apikey",
                    type: "POST",
                    dataType: 'json',
                    success: function (data) {
                        if (actionType === 'save-close') {
                            location.reload();
                        } else {
                            $('#formStatus').addClass('alert-success').html('Wijzigingen succesvol opgeslagen');
                            $('#btn-cancel').val('reload');
                            if (data.use_lease_price_filter) {
                                $('#calculateLeasePrices').prop("disabled", "");
                            } else {
                                $('#calculateLeasePrices').prop("disabled", "disabled");
                            }
                        }

                    },
                    error: function (data) {
                        $('#formStatus').addClass('alert-error').html('Wijzigingen konden niet worden opgeslagen');
                        console.log('Error:', data);
                    }
                });
            }
        })
    }

    $('body').on('click', '#calculateLeasePrices', function () {
        let apikey = $('#apiKey_id').val(),
            theButton = $(this),
            theButtonText = theButton.html();

        $('#formStatus').removeClass('alert-success alert-danger').html('');

        theButton.prop("disabled", true);
        theButton.html('<i class="fa fa-spinner fa-spin"></i> processing...');

        $.ajax({
            url: "update-lease-prices/" + apikey,
            type: "POST",
            dataType: 'json',
            success: function (data) {
                if (data.status) {
                    $('#formStatus').addClass('alert-success').html('De leaseprijzen worden in de achtergrond berekend, ' + data.josbDispatched + ' jobs zijn in de queue geplaatst.');
                } else {
                    $('#formStatus').addClass('alert-danger').html('Er is een fout opgetreden, de leaseprijzen konden niet worden berekend.');
                }
            },
            error: function (data) {
                $('#formStatus').addClass('alert-danger').html('Er is een fout opgetreden, de leaseprijzen konden niet worden berekend.');
                console.log('Error:', data);
            },
            complete: function () {
                theButton.prop("disabled", false);
                theButton.html(theButtonText);
            }
        });

    });
});

let setTerms = function() {
    let maxAge = $('#leaseMaxAge').val();

    if (maxAge) {
        $('#leaseMinTerm').removeAttr('disabled');
        $('#leaseMaxTerm').removeAttr('disabled');
    } else {
        $('#leaseMinTerm').val('').attr('disabled', 'disabled');
        $('#leaseMaxTerm').val('').attr('disabled', 'disabled');
    }
}