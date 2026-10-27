@php
$settings = \App\Settings::find(1);
@endphp
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Invoice - {{$data['invoice_id']}}</title>
  </head>
  <body onload="window.print()">
    <div class="row">
      <div class="col-md-12">

        <div class="row  my-2" >
          <div class="col-md-4"></div>
          <div class="col-md-4">
                  <br><h2 class="text text-center font-weight-bold">{{$data['isreprint']?"Copy ":""}}TAX INVOICE</h2>
          </div>
          <div class="col-md-4"></div>
        </div>

        <div class="row mx-3">

            <div class="col-md-6">
            <p class="text text-right">
                <img src="{{env('APP_URL').'/storage/'.$settings->logo}}"  alt="Logo" width="30%" >
            </p>
            <address>
                      <strong>{{$settings->company_name}}</strong><br>
                        {!! $settings->address !!} <br>
                <b>Tel: </b>{{$settings->tel}}<br>
                <b>Email: </b>{{$settings->email}}<br>
                <b>TPIN: </b> {{$data['tPin']}}<br>
                  </address>
            </div>
            <div class="col-md-6 my-5" >
            <address style="text-align: right;">
  <p style="text-align: right;">
    <b>Order #: </b>&nbsp;<span style="display: inline-block; width: 150px;">{{$data['OrderNumber']}}</span> <br>
    <b>Invoice #: </b>&nbsp;<span style="display: inline-block; width: 150px;">{{$data['invoice_id']}}</span> <br>
    <b>PO Number: </b>&nbsp;<span style="display: inline-block; width: 150px;">{{$data['PONumber']}}</span> <br>
    <b>Invoice Date: </b>&nbsp;<span style="display: inline-block; width: 150px;">{{$data['InvoiceDate']}}</span> <br>
    @if(isset($data['Reference']) and strlen($data['Reference']))
    <b>Reference: </b>&nbsp;<span style="display: inline-block; width: 150px;">{{$data['Reference']}}</span> <br>
    @endif
  </p>
</address>




</div>
        </div>
        <div class="row my-3 mx-3">
  <div class="col-md-6" style="border: 1px solid #ccc; padding: 10px;">
    <strong class="d-block mb-2">Bill To:</strong>
    <div><b>Customer Name:</b> <span>{{$data["BillTo"]}}</span></div>
    <div><b>TPIN:</b> <span>{{$data['buyerTPin']}}</span></div>
    <div><b>TEL:</b> <span>{{$data["BillToPhone"]}}</span></div>
    <div><b>Address:</b> <span>{{$data["BillToAddress1"]}} {{$data["BillToAddress2"]}} {{$data["BillToAddress3"]}} {{$data["BillToAddress4"]}}, {{$data["BillToCity"]}}, {{$data["BillToCountry"]}}</span></div>
  </div>
  <div class="col-md-6" style="border: 1px solid #ccc; padding: 10px;">
    <strong class="d-block mb-2">Ship To:</strong>
    <div><b>Customer Name:</b> <span>{{$data["BillTo"]}}</span></div>
    <div><b>TPIN:</b> <span>{{$data['buyerTPin']}}</span></div>
    <div><b>TEL:</b> <span>{{$data["BillToPhone"]}}</span></div>
    <div><b>Address:</b> <span>{{$data["BillToAddress1"]}} {{$data["BillToAddress2"]}} {{$data["BillToAddress3"]}} {{$data["BillToAddress4"]}}, {{$data["BillToCity"]}}, {{$data["BillToCountry"]}}</span></div>
  </div>
</div>





        @if(isset($data['Comment']) and strlen($data['Comment']))
        <div class="mx-4">
          Comment: {{$data['Comment']}}<br>
        </div>
        @endif

        <div class="row my-2 mx-3">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="datatable table table-bordered">
                <thead>
                  <tr>
                    <th scope="col" style="font-size:1.5em">Description</th>
                    <th scope="col" style="font-size:1.5em">Tax Category</th>
                    <th scope="col"  style="font-size:1.5em" class="text-end">Unit Price</th>
                    <th scope="col" style="font-size:1.5em" class="text-end">Quantity</th>
                    <th scope="col" style="font-size:1.5em" class=" text-end">Discount</th>
                    <th scope="col" style="font-size:1.5em" class=" text-end">TotalAmount</th>
                  </tr>
                </thead>
                <tbody class="datatable table-group-divider">
                  @php
                  $total_taxable_amount =0;
                  @endphp
                  @foreach ($data['items'] as $item)
                      <tr>
                          <td style="font-size:1.5em">{{$item['Description']}}</td>
                          <td style="font-size:1.5em">{{$item['TaxCategory']}}</td>
                          <td class="text-end" style="font-size:1.5em">{{$item['UnitPrice']}}</td>
                          <td class="text-end" style="font-size:1.5em">{{$item['QuantityShipped']}}</td>
                          <td class="text-end" style="font-size:1.5em">{{$item['Discount']}}</td>
                          <td class="text-end" style="font-size:1.5em">{{$item['TotalAmount']}}</td>
                      </tr>
                      @php
                        $amount = preg_replace('/[^0-9\.]/', '', $item['TotalAmount']);
                        if (is_numeric($amount)) {
                            $total_taxable_amount = $total_taxable_amount + floatval($amount);
                        }
                      @endphp
                  @endforeach
                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:1.5em">Total Taxable Amount</th>
                      <th class="text text-end"></th>
                      <th class="text text-end" style="font-size:1.5em">{{number_format($total_taxable_amount,2)}}</th>
                  </tr>


                  @foreach ($data['taxes'] as $t_row)
                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:1.5em">Total Tax - {{$t_row['cat']}}({{$t_row['rate']}})</th>
                      <th class="text-uppercase text-end" style="font-size:1.5em"></th>
                      <th class="text-uppercase text-end" style="font-size:1.5em">{{$t_row['amt']}}</th>

                  </tr>
                  @endforeach
                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:1.5em">Total</th>
                      <th class="text text-end"></th>
                      <th class="text text-end" style="font-size:1.5em">{{$data['InvoiceSourceCurrency']}} {{$data['InvoiceRunningTotal']}}</th>
                  </tr>

                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:1.5em">Exchange Rate</th>
                      <th class="text text-end"></th>
                      <th class="text text-end" style="font-size:1.5em">{{$data['InvoiceRate']}}</th>
                  </tr>

                  </tbody>
              </table>
            </div>
          </div>
        </div>

<div class="row my-3 mx-4">
<h4>SDC Information</h4>
  <div class="col-md-6">
    <div class="row">
      <div class="col-6"><b>Date:</b></div>
      <div class="col-6">{{$data['VSDCDate']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>Time:</b></div>
      <div class="col-6">{{$data['VSDCTime']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>SDC ID:</b></div>
      <div class="col-6">{{$data['VSDCID']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>Fiscal Invoice No.:</b></div>
      <div class="col-6">{{$data['VSDCInvoiceNumber']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>Invoice Type:</b></div>
      <div class="col-6">{{$data['VSDCInvoiceType']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>Internal Data:</b></div>
      <div class="col-6">{{$data['VSDCInternalData']}}</div>
    </div>
    <div class="row">
      <div class="col-6"><b>Signature:</b></div>
      <div class="col-6">{{$data['VSDCSignature']}}</div>
    </div>
  </div>
  <div class="col-md-6" style="text-align: right;">
    {!! QrCode::size(200)->generate($data['VSDCURL']) !!}
  </div>
</div>

<div class="row my-3 mx-4">
  <p class="text-danger">
  Please note that payment must be made into the correct bank account, refer our USD and ZMW banking details below
  </p>
</div>

          <div class="row my-3  mx-4">
              @if(isset($settings->bank))
                  <div class="col-md-6">
                  <div class="">
  <h4>Banking Details</h4>
  <div class="row">
    <div class="col-6"><b>Bank Name:</b></div>
    <div class="col-6">{{$settings->bank}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>Account Name:</b></div>
    <div class="col-6">{{$settings->account_name}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>Branch Name:</b></div>
    <div class="col-6">{{$settings->branch}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>Branch Code:</b></div>
    <div class="col-6">{{$settings->branch_code}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>USD Account Number:</b></div>
    <div class="col-6">{{$settings->local_curr_account_no}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>ZMW Account Number:</b></div>
    <div class="col-6">{{$settings->foreign_curr_account_no}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>Swift Code:</b></div>
    <div class="col-6">{{$settings->swift_code}}</div>
  </div>
  <div class="row">
    <div class="col-6"><b>Sort Code:</b></div>
    <div class="col-6">{{$settings->sort_code}}</div>
  </div>
</div>

                  </div>
                  <div class="col-md-6"></div>
              @endif
          </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
