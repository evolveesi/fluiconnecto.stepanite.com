@extends('layouts.dashboard',[
    'page_name'=>'Purchases Credit/Debit Note Report',
    'section_name'=>'Purchases Credit/Debit Note',
    'activity_name'=>'Purchases Credit/Debit Note Report'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Purchases Credit/Debit Note Report</div>
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
                                <th>Status</th>
                                <th>Invoice ID</th>
                                <th>Sage Purchase Number</th>
                                <th>Creation Date</th>
                                <th>Last Update Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($purchases as $purchase)
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$purchase->status}}</td>
                                    <td>{{$purchase->invoice_id}}</td>
                                    <td>{{$purchase->sage_purchase_number}}</td>
                                    <td>{{$purchase->created_at}}</td>
                                    <td>{{$purchase->updated_at}}</td>
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
