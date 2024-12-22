<div class="modal fade" id="ajax-crud-modal" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="apiKeyCrudModal"></h4>
        </div>
        <div class="modal-body">
            <form id="apiKeyForm" name="apiKeyForm" class="form-horizontal">
                <input type="hidden" name="apiKey_id" id="apiKey_id">
                <input type="hidden" name="apiKey" id="apiKey">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="name" class="col-sm-2 control-label">Klantnaam</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="clientName" name="clientName" placeholder="Klantnaam" value="" maxlength="250" required="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Databases</label>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen" name="voertuigen" value="voertuigen">
                                <label for="voertuigen" class="form-check-label">Voertuigen</label>
                                <div id="divClientNumbers" class="form-group">
                                    <label for="clientNrs" class="col-sm-2 control-label">Klantnummers</label>
                                    <div class="col-sm-12">
                                        <input type="text" class="form-control" id="clientNrs" name="clientNrs" placeholder="Klantnummers" value="" maxlength="250" required="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen_autovooru" name="voertuigen_autovooru" value="voertuigen_autovooru">
                                <label for="voertuigen_autovooru" class="form-check-label">Voertuigen_autovooru</label>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen_carhotspot" name="voertuigen_carhotspot" value="voertuigen_carhotspot">
                                <label for="voertuigen_carhotspot" class="form-check-label">Voertuigen_carhotspot</label>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen_global" name="voertuigen_global" value="voertuigen_global">
                                <label for="voertuigen_global" class="form-check-label">Voertuigen_global</label>
                                <div id="divClientNumbers_global" class="form-group">
                                    <label for="clientNrs" class="col-sm-2 control-label">Klantnummers</label>
                                    <div class="col-sm-12">
                                        <input type="text" class="form-control" id="clientNrs" name="clientNrs" placeholder="Klantnummers" value="" maxlength="250" required="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="leaseDownPayment" class="col-sm-8 control-label">Lease Aanbetaling</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="leaseDownPayment" name="leaseDownPayment" placeholder="Aanbetaling" value="" maxlength="250">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="leaseInterestRateGeneral" class="col-sm-8 control-label">Lease Rentepercentage losse calculator</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="leaseInterestRateGeneral" name="leaseInterestRateGeneral" placeholder="Rentepercentage" value="" maxlength="250">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="leaseInterestRate" class="col-sm-8 control-label">Lease Rentepercentage</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="leaseInterestRate" name="leaseInterestRate" placeholder="Rentepercentage" value="" maxlength="250">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">Apikey</label>
                    <div class="col-sm-12">
                        <input type="text" class="form-control" id="apikeyText" name="apikeyText" value="" required="" disabled>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="btn-cancel" value="cancel">Annuleren</button>
                    <button type="submit" class="btn btn-primary" id="btn-save" value="create">Opslaan</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
