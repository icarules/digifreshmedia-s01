<div class="modal fade" id="ajax-crud-modal" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="apiKeyCrudModal"></h4>
        </div>
        <div class="modal-body">
            <form id="apiKeyForm" name="apiKeyForm" class="form-horizontal">
                <input type="hidden" name="apiKey_id" id="apiKey_id">
                <input type="hidden" name="apiKey" id="apiKey">
                <div class="row mr-1">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="name" class="col-sm-2 control-label">Klantnaam</label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control" id="clientName" name="clientName" placeholder="Klantnaam" value="" maxlength="250" required="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Databases</label>
{{--                            @foreach($vehicleTables as $vehicleTable)--}}
{{--                                <div>{{ $vehicleTable->table_name  }}</div>--}}
{{--                            @endforeach--}}
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen" name="voertuigen" value="voertuigen">
                                <label for="voertuigen" class="form-check-label">Voertuigen</label>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigens01" name="voertuigens01" value="voertuigens01">
                                <label for="voertuigens01" class="form-check-label">Voertuigens01</label>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen_api" name="voertuigen_api" value="voertuigen_api">
                                <label for="voertuigen_api" class="form-check-label">voertuigen_api</label>
                            </div>
                            <div class="col-sm-12">
                                <input type="checkbox" class="" id="voertuigen_carhotspot" name="voertuigen_carhotspot" value="voertuigen_carhotspot">
                                <label for="voertuigen_carhotspot" class="form-check-label">Voertuigen_carhotspot</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="row">
                            <div class="col-sm12">
                                <h5>Lease instellingen</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseInterestRateGeneral" class="col-sm-12 control-label px-0">Rentepercentage losse calculator</label>
                                    <div class="col-sm-12 px-0">
                                        <input type="text" class="form-control" id="leaseInterestRateGeneral" name="leaseInterestRateGeneral" placeholder="Rentepercentage" value="" maxlength="250">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseInterestRate" class="col-sm-12 control-label px-0">Rentepercentage</label>
                                    <div class="col-sm-12 px-0">
                                        <input type="text" class="form-control" id="leaseInterestRate" name="leaseInterestRate" placeholder="Rentepercentage" value="" maxlength="250">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseDownPayment" class="col-sm-12 control-label px-0">Aanbetaling</label>
                                    <p class="subLabel px-0">percentage van aanschafwaarde</p>
                                    <div class="col-sm-12 px-0">
                                        <input type="text" class="form-control" id="leaseDownPayment" name="leaseDownPayment" placeholder="Aanbetaling" value="" maxlength="250">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseFinalPayment" class="col-sm-12 control-label px-0">Slottermijn</label>
                                    <p class="subLabel px-0">percentage van aanschafwaarde</p>
                                    <div class="col-sm-12 px-0">
                                        <input type="text" class="form-control" id="leaseFinalPayment" name="leaseFinalPayment" placeholder="Slottermijn" value="" maxlength="250">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseDefaultTerm" class="col-sm-12 control-label px-0">Default Looptijd</label>
                                    <p class="subLabel px-0">default looptijd in maanden</p>
                                    <div class="col-sm-12 px-0">
                                        <select class="form-control" id="leaseDefaultTerm" name="leaseDefaultTerm">
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                            <option value="36">36</option>
                                            <option value="48">48</option>
                                            <option value="60" selected>60</option>
                                            <option value="72">72</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseMaxAge" class="col-sm-12 control-label px-0">Maximale Leeftijd Voertuig</label>
                                    <p class="subLabel px-0">maximale leeftijd bij einde contract</p>
                                    <div class="col-sm-12 px-0">
                                        <select class="form-control" id="leaseMaxAge" name="leaseMaxAge">
                                            <option value="">n.v.t.</option>
                                            <option value="6">6 jaar</option>
                                            <option value="7">7 jaar</option>
                                            <option value="8">8 jaar</option>
                                            <option value="9">9 jaar</option>
                                            <option value="10">10 jaar</option>
                                            <option value="11">11 jaar</option>
                                            <option value="12">12 jaar</option>
                                            <option value="13">13 jaar</option>
                                            <option value="14">14 jaar</option>
                                            <option value="15">15 jaar</option>
                                            <option value="16">16 jaar</option>
                                            <option value="17">17 jaar</option>
                                            <option value="18">18 jaar</option>
                                            <option value="19">19 jaar</option>
                                            <option value="20">20 jaar</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseMinTerm" class="col-sm-12 control-label px-0">Minimale Looptijd</label>
                                    <p class="subLabel px-0">minimale looptijd in maanden</p>
                                    <div class="col-sm-12 px-0">
                                        <select class="form-control" id="leaseMinTerm" name="leaseMinTerm">
                                            <option value=""></option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                            <option value="36">36</option>
                                            <option value="48">48</option>
                                            <option value="60">60</option>
                                            <option value="72">72</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="leaseMaxTerm" class="col-sm-12 control-label px-0">Maximale Looptijd</label>
                                    <p class="subLabel px-0">maximale looptijd in maanden</p>
                                    <div class="col-sm-12 px-0">
                                        <select class="form-control" id="leaseMaxTerm" name="leaseMaxTerm">
                                            <option value=""></option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                            <option value="36">36</option>
                                            <option value="48">48</option>
                                            <option value="60">60</option>
                                            <option value="72">72</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

{{--                <div class="row mr-1 my-2">--}}
{{--                    <div class="col-sm-6">--}}
{{--                    </div>--}}
{{--                    <div class="col-sm-6">--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-sm12">--}}
{{--                                <h5>Opties</h5>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row">--}}
{{--                            <div class="col-sm-6">--}}
{{--                                <div class="form-group">--}}
{{--                                    <input type="checkbox" class="" id="use_lease_price_filter" name="use_lease_price_filter" value="1">--}}
{{--                                    <label for="use_lease_price_filter" class="form-check-label">Filteren op lease prijs </label>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col-sm-6">--}}
{{--                                <div class="form-group">--}}
{{--                                    <button type="button" class="btn btn-success" id="calculateLeasePrices" disabled="disabled">Leaseprijzen (her)berekenen</button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

                <div class="form-group">
                    <label class="col-sm-2 control-label">Apikey</label>
                    <div class="col-sm-12">
                        <input type="text" class="form-control" id="apikeyText" name="apikeyText" value="" required="" disabled>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="formStatus" class="alert" role="alert"></div>
                    <button type="button" class="btn btn-default" id="btn-cancel" value="cancel">Annuleren</button>
                    <button type="submit" class="btn btn-primary" id="btn-save" value="save">Opslaan</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-close" value="save-close">Opslaan & Sluiten</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
