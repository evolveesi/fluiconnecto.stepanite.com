@extends('layouts.dashboard',[
    'page_name'=>'Device Request/Response Report',
    'section_name'=>'Device',
    'activity_name'=>'Device Request/Response Report'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Device Request/Response Report</div>
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                        </div>
                    </div>
                </h5>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table  class="datatable table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Url</th>
                                <th>Request</th>
                                <th>Response</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($device_responses as $device_response)
                                <tr>
                                    <td>{{$count}}</td>                                    
                                    <td><code>{{$device_response->url}}</code></td>
                                    <td><code>{{$device_response->zra_request}}</code></td>
                                    <td><code>{{$device_response->zra_response}}</code></td>
                                </tr>
                                @php
                                    $count += 1
                                @endphp
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
