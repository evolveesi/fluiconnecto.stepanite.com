@extends('layouts.dashboard',[
    'page_name'=>'Imports Processing',
    'section_name'=>'Imports',
    'activity_name'=>'Imports Processing'
])
@section('content')
@php
    $payment_types = array( 
        '01'=>'CASH',
        '02'=>'CREDIT',
        '03'=>'CASH/CREDIT',
        '04'=>'BANK CHECK',
        '05'=>'DEBIT&CREDIT CARD',
        '06'=>'MOBILE MONEY',
        '08'=> 'Bank transfer',
        '07'=>'OTHER',
    );

@endphp

<div class="row">
    <div class="col-md-12">
              
        @foreach($unprocessed_imports as $import_item)
        <div class="card shadow-lg mb-3 bg-body rounded">
            <div class="card-body">  
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>Task Code</th>
                        <td>{{$import_item->taskCd}}</td>
                        <th>Sage Item Number</th>
                        <td>{{$import_item->itemCd}}</td>
                        <th>Status</th>
                        <td>{{$import_item->status}}</td>
                        <th>Vendor</th>
                        <td>{{$import_item->spplrNm}}</td>
                        <th>Declaration#</th>
                        <td>{{$import_item->dclNo}}</td>
                        <th>Item Name</th>
                        <td>{{$import_item->itemNm}}</td>         
                        <th>Quantity</th>
                        <td>{{$import_item->qty}}</td>
                        <th>Net Weight</th>
                        <td>{{$import_item->netWt}}</td>
                    </tr>
                    <tr>
                        <th>Payment Type</th>
                        <td>{{$import_item->payment_type}}</td> 
                        <th>Location</th>   
                        <td>{{$import_item->location}}</td>
                        <th>Terms Code</th>
                        <td>{{$import_item->terms_code}}</td>
                        <th>Vendor Acc Set</th>
                        <td>{{$import_item->vendor_account_set}}</td>
                        <th>Currency</th>
                        <td>{{$import_item->invcFcurCd}}</td>
                        <th>Amount</th>
                        <td>{{number_format($import_item->invcFcurAmt,2)}}</td>
                        <th>Exchange Rate</th>
                        <td>{{number_format($import_item->invcFcurExcrt,2)}}</td>
                        <th>Processed Date</th>
                        <td>{{$import_item->updated_at}}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endforeach
                  
    </div>
</div>

@endsection



