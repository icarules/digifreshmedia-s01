@extends('layouts.app')

@section('content')
    <h4 style="margin-top: 12px;" class="alert alert-info">Klanten digifresh S01</h4><br>
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-11">
                    <a href="javascript:void(0)" class="btn btn-success mb-2" id="create-new-apiKey">Toevoegen</a>
                </div>
                <div class="col-1">
                    <select name="resultsPerPage" id="resultsPerPage" class="form-control">
                        <?php for ($i=8; $i<=28; $i+=2) : ?>
                        <option value="<?php echo $i;?>" <?php if ($i == $resultsPerPage) echo " selected";?>><?php echo $i;?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <table class="table table-sm table-bordered" id="laravel_crud">
                <thead>
                <tr>
                    <?php
                        $iconClass = ( $sort == 'asc') ? 'fa-sort-up pt-2' : 'fa-sort-down';
                    ?>
                    <th class="thSort <?= ($orderBy == 'id') ? 'active' : 'inactive';?>"><a href="/iomsapi/apikey?orderBy=id&sort=<?= ($orderBy == 'id' && $sort == 'asc') ? 'desc' : 'asc';?>">Id</a><?= ($orderBy == 'id') ? "<i class='fa-solid $iconClass float-right'></i>" : '';?></th>
                    <th class="thSort <?= ($orderBy == 'client_name') ? 'active' : 'inactive';?>"><a href="/iomsapi/apikey?orderBy=client_name&sort=<?= ($orderBy == 'client_name' && $sort == 'asc') ? 'desc' : 'asc';?>">Klantnaam</a><?= ($orderBy == 'client_name') ? "<i class='fa-solid $iconClass float-right'></i>" : '';?></th>
                    <th>Apikey</th>
                    <th colspan="3">Acties</th>
                </tr>
                </thead>
                <tbody id="apiKeys-crud">
                @foreach($apiKeys as $u_info)
                    <tr id="apiKey_id_{{ $u_info->id }}">
                        <td>{{ $u_info->id  }}</td>
                        <td>{{ $u_info->client_name }}</td>
                        <td>{{ $u_info->api_key }}</td>
                        <td><a href="javascript:void(0)" id="edit-apiKey" data-id="{{ $u_info->id }}" class="btn btn-sm btn-info">Wijzigen</a></td>
                        <td><a href="javascript:void(0)" id="delete-apiKey" data-id="{{ $u_info->id }}" data-client-name="{{ $u_info->client_name }}" class="btn btn-sm btn-danger delete-apiKey">Verwijderen</a></td>
                        <td><a href="https://digifreshmedia.nl/solr/sync.php" target="_blank" class="btn btn-sm btn-warning">Sync solr</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $apiKeys->links() }}
        </div>
    </div>

    @include('apikey.modal')
@endsection
