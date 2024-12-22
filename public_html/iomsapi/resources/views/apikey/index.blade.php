@extends('layouts.app')

@section('content')
    <h4 style="margin-top: 12px;" class="alert alert-info">Klanten digifresh</h4><br>
    <div class="row">
        <div class="col-12">
            <a href="javascript:void(0)" class="btn btn-success mb-2" id="create-new-apiKey">Toevoegen</a>
            <table class="table table-bordered" id="laravel_crud">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Klantnaam</th>
                    <th>Apikey</th>
                    <td colspan="2">Acties</td>
                </tr>
                </thead>
                <tbody id="apiKeys-crud">
                @foreach($apiKeys as $u_info)
                    <tr id="apiKey_id_{{ $u_info->id }}">
                        <td>{{ $u_info->id  }}</td>
                        <td>{{ $u_info->client_name }}</td>
                        <td>{{ $u_info->api_key }}</td>
                        <td><a href="javascript:void(0)" id="edit-apiKey" data-id="{{ $u_info->id }}" class="btn btn-info">Wijzigen</a></td>
                        <td>
                            <a href="javascript:void(0)" id="delete-apiKey" data-id="{{ $u_info->id }}" class="btn btn-danger delete-apiKey">Verwijderen</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $apiKeys->links() }}
        </div>
    </div>

    @include('apikey.modal')
@endsection
