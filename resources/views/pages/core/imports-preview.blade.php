@extends('layouts.dashboard',[
    'page_name'=>'Imports Processing',
    'section_name'=>'Imports',
    'activity_name'=>'Pending Imports'
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
            <form action="{{url('process-imports')}}" method="post" name="form_{{$import_item->id}}">
                @csrf
                <div class="card shadow-lg mb-3 bg-body rounded">
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Task Code</th>
                                <td>{{$import_item->taskCd}}</td>
                                <th>Sage Item Number</th>
                                <td>
                                    <input type="hidden" name="id" value="{{$import_item->id}}">
                                    <input list="items-list" required class="form-control" id="sage_item_code" name="sage_item_code" />

                                    <datalist id="items-list">
                                        @foreach ($items as $item)
                                            <option value="{{$item->itemCd}}"></option>
                                        @endforeach
                                    </datalist>
                                </td>
                                <th>Status</th>
                                <td>
                                    <select name="import_status" id="" class="form-control" required>
                                        <option value="">-- Select Status--</option>
                                        <option value="3">Accept</option>
                                        <option value="4">Reject</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Vendor</th>
                                <td><input type="text" name="spplrNm" {{$import_item->status=="Pending"?"":"disabled"}}  required class="form-control"  value="{{$import_item->spplrNm}}"></td>
                                <th>Declaration#</th>
                                <td>{{$import_item->dclNo}}</td>
                                <th>Item Name</th>
                                <td>{{$import_item->itemNm}}</td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td><input type="text" name="qty" {{$import_item->status=="Pending"?"":"disabled"}}  required class="form-control"  value="{{$import_item->qty}}"></td>
                                <th>Net Weight</th>
                                <td>{{$import_item->netWt}}</td>
                                <th>Payment Type</th>
                                <td>
                                    <select name="payment_type" id="payment_type" class="form-control" {{$import_item->status=="Pending"?"":"disabled"}} required>
                                        <option value="">-- Select Status--</option>
                                        @foreach ($payment_types as $key=>$pmtype)
                                        <option value="{{$key}}" {{$import_item->payment_type==$key?'selected':''}}>{{$pmtype}}</option> 
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr> 
                                <th>Location</th>   
                                <td><input type="text" name="location" {{$import_item->status=="Pending"?"":"disabled"}}  required class="form-control"  value="{{$import_item->location}}"></td>
                                <th>Terms Code</th>
                                <td>
                                    @if ($import_item->status=="Pending")
                                        <select name="terms_code" id="terms_code" class="form-control" {{$import_item->status=="Pending"?"":"disabled"}} required>
                                            <option value="">-- Select Status--</option>
                                            <option value="30DAYS">30DAYS</option>
                                            <option value="30DAYS">60DAYS</option>
                                            <option value="30DAYS">90DAYS</option>
                                        </select>
                                    @else
                                        <input type="text" name="terms_code" disabled  required class="form-control"  value="{{$import_item->terms_code}}">
                                    @endif
                                </td>
                                <th>Vendor Acc Set</th>
                                <td><input type="text" name="vendor_account_set" {{$import_item->status=="Pending"?"":"disabled"}}  required class="form-control"  value="{{$import_item->vendor_account_set}}"></td>
                            </tr>
                            <tr> 
                                <th>Currency</th>
                                <td>{{$import_item->invcFcurCd}}</td>
                                <th>Amount</th>
                                <td>{{number_format($import_item->invcFcurAmt,2)}}</td>
                                <th>Exchange Rate</th>
                                <td>{{number_format($import_item->invcFcurExcrt,2)}}</td>
                            </tr>
                            <tr> 
                                @if($import_item->status=="Pending" )
                                    <td colspan="6">
                                        <button id="submit-btn" type="submit" class="btn btn-block btn-lg btn-dark">Interface</button>
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </div>
                </div>
            </form>
        @endforeach
                
    </div>
</div>

@endsection



