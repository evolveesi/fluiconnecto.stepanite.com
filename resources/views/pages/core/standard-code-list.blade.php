@extends('layouts.dashboard',[
    'page_name'=>'Standard Codes Listing',
    'section_name'=>'Configurations',
    'activity_name'=>'Standard Codes Listing'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Standard Codes Listing</div>
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
                                <th>Class</th>
                                <th>Class Name</th>
                                <th>Class Value</th>
                                <th>Code</th>
                                <th>Code Name</th>
                                <th>Code Value</th>
                                <th>Update At</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($standard_codes as $standard_code)
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$standard_code->standard_code_class}}</td>
                                    <td>{{$standard_code->standard_code_class_name}}</td>
                                    <td>{{$standard_code->standard_code_class_value}}</td>
                                    <td>{{$standard_code->standard_code}}</td>
                                    <td>{{$standard_code->standard_code_name}}</td>
                                    <td>{{$standard_code->standard_code_value}}</td>
                                    <td>{{$standard_code->updated_at}}</td>
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
