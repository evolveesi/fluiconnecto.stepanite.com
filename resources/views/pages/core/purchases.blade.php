@extends('layouts.dashboard',[
    'page_name'=>'Purchases Report',
    'section_name'=>'Purchases',
    'activity_name'=>'Purchases Report'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Purchases Report</div>
                        <div class="col-md-4">

                        </div>
                        <div class="col-md-4">

                        </div>
                    </div>
                </h5>
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    @foreach($purchases as $purchase)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="flush-heading{{$purchase->id}}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse{{$purchase->id}}" aria-expanded="false" aria-controls="flush-collapse{{$purchase->id}}">
                                    {{$purchase->itemNm."-".$purchase->itemCd}}
                                </button>
                            </h2>
                            <div id="flush-collapse{{$purchase->id}}" class="accordion-collapse collapse" aria-labelledby="flush-heading{{$purchase->id}}" data-bs-parent="#accordionFlushExample">
                                <form action="{{url('process-purchase')}}" method="post" name="form_{{$purchase->id}}"  class="py-5">
                                    @csrf
                                    <input type="hidden" name="id" value="{{$purchase->id}}">
                                    <div class="row px-5">
                                        <div class="col-md-4">
                                            <label for="">Supplier Tpin</label>
                                            <input type="text" name="spplrTpin" readonly {{$purchase->status=="Interfaced"?"disabled":""}}  value="{{$purchase->spplrTpin}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Supplier Name</label>
                                            <input type="text" name="spplrNm" readonly {{$purchase->status=="Interfaced"?"disabled":""}} value="{{$purchase->spplrNm}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Sage Vendor Code <code><sup>*</sup></code> </label>
                                            <input type="text" name="sageVendorCd" {{$purchase->status=="Interfaced"?"disabled":""}} required  value="{{$purchase->sageVendorCd}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-4">
                                            <label for="">Vendor Account Set <code><sup>*</sup></code> </label>
                                            <input type="text" name="vendorAccountSet" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->vendorAccountSet}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Terms Code <code><sup>*</sup></code> </label>
                                            <input type="text" name="termsCode" {{$purchase->status=="Interfaced"?"disabled":""}}  required value="{{$purchase->termsCode}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">PurchaseOrderNumber <code><sup>*</sup></code> </label>
                                            <input type="text" name="PurchaseOrderNumber"  {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->PurchaseOrderNumber}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-4">
                                            <label for="">Currency</label>
                                            <input type="text" name="currency" readonly  {{$purchase->status=="Interfaced"?"disabled":""}}  value="{{$purchase->currency}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Payment Type </label>
                                            <input type="text" name="pmtTyCd" readonly {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->pmtTyCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Purchase Date</label>
                                            <input type="text" name="salesDt"  {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->salesDt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Confirmation Date</label> --}}
                                            <input type="hidden" name="cfmDt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->cfmDt}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            {{-- <label for="">Supplier Tpin</label> --}}
                                            <input type="hidden" name="rcptTyCd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->rcptTyCd}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-4">
                                            <label for="">Item Code</label>
                                            <input type="text" name="itemCd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->itemCd}}" class="form-control">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="">Item Classification Code</label>
                                            <input type="text" name="itemClsCd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->itemClsCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Sage Item Code <code><sup>*</sup></code></label>
                                            @if($purchase->status=="Pending")
                                            <input list="items-list" required {{$purchase->status=="Interfaced"?"disabled":""}} class="form-control" id="sageItemCd" name="sageItemCd" />
                                            <datalist id="items-list">
                                                @foreach ($items as $item)
                                                    <option value="{{$item->itemCd}}" {{$item->itemCd==$purchase->sageItemCd?"selected":""}}></option>
                                                @endforeach
                                            </datalist>
                                            @else
                                                <input type="text" name="spplrNm" readonly {{$purchase->status=="Interfaced"?"disabled":""}} value="{{$purchase->sageItemCd}}" class="form-control">
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Bar Code</label> --}}
                                            <input type="hidden" name="bcd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->bcd}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-4">
                                            <label for="">Packaging</label>
                                            <input type="text" name="pkg" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->pkg}}" class="form-control">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="">Unit of Measure</label>
                                            <input type="text" name="qtyUnitCd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->qtyUnitCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Quantity <code><sup>*</sup></code></label>
                                            <input type="text" name="qty" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->qty}}" class="form-control">
                                        </div>
                                        
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-4">
                                            <label for="">Unit Price</label>
                                            <input type="text" name="prc" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->prc}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Package Unit Code</label>
                                            <input type="text" name="pkgUnitCd" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->pkgUnitCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="">Location</label>
                                            <input type="text" name="location" {{$purchase->status=="Interfaced"?"disabled":""}} required  value="{{$purchase->location}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Supply Amount (USD$)</label> --}}
                                            <input type="hidden" name="splyAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->splyAmt}}" class="form-control">
                                        </div>
                                        
                                    
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-6">
                                            {{-- <label for="">Discount Rate</label> --}}
                                            <input type="hidden" name="dcRt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->dcRt}}" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            {{-- <label for="">Discount Amount</label> --}}
                                            <input type="hidden" name="dcAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->dcAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">VAT Category Code</label> --}}
                                            <input type="hidden" name="vatCatCd" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->vatCatCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Insurance Premium Levy Code</label> --}}
                                            <input type="hidden" name="iplCatCd" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->iplCatCd}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-3">
                                            {{-- <label for="">Tourism levy Code</label> --}}
                                            <input type="hidden" name="tlCatCd" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->tlCatCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Excise Category Code</label> --}}
                                            <input type="hidden" name="exciseTxCatCd" {{$purchase->status=="Interfaced"?"disabled":""}} required value="{{$purchase->exciseTxCatCd}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">VAT Taxable Amount</label> --}}
                                            <input type="hidden" name="vatTaxblAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->vatTaxblAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Insurance levy Taxable Amount</label> --}}
                                            <input type="hidden" name="iplTaxblAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->iplTaxblAmt}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-3">
                                            {{-- <label for="">Excise Taxable Amount</label> --}}
                                            <input type="hidden" name="exciseTaxblAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->exciseTaxblAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Tourism Levy Taxable Amount</label> --}}
                                            <input type="hidden" name="tlTaxblAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->tlTaxblAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Total Taxable Amount</label> --}}
                                            <input type="hidden" name="taxblAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->taxblAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Excise Amount</label> --}}
                                            <input type="hidden" name="exciseTxAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->exciseTxAmt}}" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row px-5 my-2">
                                        <div class="col-md-3">
                                            {{-- <label for="">VAT Amount</label> --}}
                                            <input type="hidden" name="vatAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->vatAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Insurance Levy Amount</label> --}}
                                            <input type="hidden" name="iplAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->iplAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Tourism Levy Amount</label> --}}
                                            <input type="hidden" name="tlAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->tlAmt}}" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            {{-- <label for="">Total Amount</label> --}}
                                            <input type="hidden" name="totAmt" {{$purchase->status=="Interfaced"?"disabled":""}} readonly value="{{$purchase->totAmt}}" class="form-control">
                                            <input type="hidden" name="itemNm" value="{{$purchase->itemNm}}">
                                            <input type="hidden" name="itemCd" value="{{$purchase->itemCd}}">
                                        </div>
                                    </div>
                                    
                                    @if($purchase->status=="Pending")
                                    <div class="row px-5 my-2">
                                        <div class="col-md-12">
                                            <div class="d-grid gap-2">
                                                <input type="submit"  value="Interface" class="btn btn-dark btn-md btn-block">
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
