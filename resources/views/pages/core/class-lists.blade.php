@extends('layouts.dashboard',[
    'page_name'=>'Classification Codes Listing',
    'section_name'=>'Configurations',
    'activity_name'=>'Classification Codes Listing'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Classification Codes Listing</div>
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
                                <th>Item Class Name</th>
                                <th>Class Code</th>
                                <th>Class Level</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($classLists as $classList)
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$classList->itemClsNm}}</td>
                                    <td>{{$classList->itemClsCd}}</td>
                                    <td>{{$classList->itemClsLvl}}</td>
                                    <td>{{$classList->useYn=="Y"?"Enabled":"Disabled"}}</td>
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
