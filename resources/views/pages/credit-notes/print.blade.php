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

    <title>{{$data['CreditDebitNoteType']}}  - {{$data['CreditDebitNoteNumber']}}</title>
  </head>
  <body onload="window.print()">
    <div class="row">
      <div class="col-md-12">
        <div class="row mt-2">
          <div class="col-md-4"></div>
          <div class="col-md-4">
            <p class="text text-center"><img src="{{env('APP_URL').'/storage/'.$settings->logo}}"  alt="Logo" width="90%" ></p>
          </div>
          <div class="col-md-4"></div>
        </div>
        <div class="row  my-2" >
          <div class="col-md-4">

          </div>
          <div class="col-md-4">
            <h2 class="text text-center font-weight-bold">Tax {{$data['CreditDebitNoteType']}}</h2>
            @if ($data['isreprint'])
              <br><h2 class="text text-center font-weight-bold">Copy</h2>
            @endif
          </div>
          <div class="col-md-4"></div>
        </div>
          <div class="row mx-3">
              <div class="col-md-5">
                  <address>
                      <strong>{{$settings->company_name}}</strong><br>
                      {!! $settings->address !!}
                      <b>Tel:</b>{{$settings->tel}}<br>
                      <b>Email:</b>{{$settings->email}}<br>
                      <b>TPIN:</b> {{$data['tPin']}}<br>
                  </address>
              </div>
              <div class="col-md-2"></div>
              <div class="col-md-5" >

              </div>
          </div>
          <div class="row my-3 mx-3">
              <div class="col-md-6">
                  <table class="table table-border" >
                      <u><strong class="mx-2 py-2">Bill To:</strong></u>
                      <tr>
                          <th>Customer Name:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillTo"]}}</span></td>
                      </tr>
                      <tr>
                          <th>TPIN:</th>
                          <td><span class="col-6 text-sm-end">{{$data['buyerTPin']}}</span></td>
                      </tr><tr>
                          <th>TEL:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillToPhone"]}}</span></td>
                      </tr>
                      <tr>
                          <th>Address:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillToAddress2"]}},{{$data["BillToCity"]}},{{$data["BillToCountry"]}},</span></td>
                      </tr>
                  </table>
              </div>
              <div class="col-md-6">
                  <table class="table table-border" >
                      <u><strong class="mx-2 py-2">Deliver To:</strong></u>
                      <tr>
                          <th>Customer Name:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillTo"]}}</span></td>
                      </tr>
                      <tr>
                          <th>TPIN:</th>
                          <td><span class="col-6 text-sm-end">{{$data['buyerTPin']}}</span></td>
                      </tr><tr>
                          <th>TEL:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillToPhone"]}}</span></td>
                      </tr>
                      <tr>
                          <th>Address:</th>
                          <td><span class="col-6 text-sm-end">{{$data["BillToAddress2"]}},{{$data["BillToCity"]}},{{$data["BillToCountry"]}},</span></td>
                      </tr>
                  </table>
              </div>
          </div>
        <div class="row my-2 mx-2">
          <div class="col-md-12">
            <table class="datatable table table-bordered">
              <tr>
                <th>{{$data['CreditDebitNoteType']}} #:</th>

                <th>Reason:</th>

                <th>Invoice #:</th>

                <th>Order #:</th>

                <th>{{$data['CreditDebitNoteType']}} Date:</th>
                <th>Invoice Date:</th>

              </tr>
                <tr>
                    <td><span class="col-6 text-sm-end">{{$data['CreditDebitNoteNumber']}}</span></td>
                    <td><span class="col-6 text-sm-end">{{$data['CRNOTEREASON']}}</span></td>
                    <td><span class="col-6 text-sm-end">{{$data['InvoiceNumber']}}</span></td>
                    <td><span class="col-6 text-sm-end">{{$data['OrderNumber']}}</span></td>
                    <td><span class="col-6 text-sm-end">{{$data['CRDNDate']}}</span></td>
                    <td><span class="col-6 text-sm-end">{{$data['InvoiceDate']}}</span></td>
                </tr>
            </table>
          </div>

        </div>

        <div class="row my-2 mx-3">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="datatable table table-bordered">
                <thead>
                  <tr>
                    <th scope="col" style="font-size:0.8em">Description</th>
                    <th scope="col" style="font-size:0.8em">Tax Category</th>
                    <th scope="col"  style="font-size:0.8em" class="text-end">Unit Price</th>
                    <th scope="col" style="font-size:0.8em" class="text-end">Quantity</th>
                    <th scope="col" style="font-size:0.8em" class=" text-end">Discount</th>
                    <th scope="col" style="font-size:0.8em" class=" text-end">TotalAmount</th>
                  </tr>
                </thead>
                <tbody class="datatable table-group-divider">
                    @php
                        $total_taxable_amount =0;
                    @endphp
                  @foreach ($data['items'] as $item)
                      <tr>
                          <td style="font-size:0.8em">{{$item['Description']}}</td>
                          <td style="font-size:0.8em">{{$item['TaxCategory']}}</td>
                          <td class="text-end" style="font-size:0.8em">{{$item['UnitPrice']}}</td>
                          <td class="text-end" style="font-size:0.8em">{{$item['QuantityReturned']}}</td>
                          <td class="text-end" style="font-size:0.8em">{{$item['Discount']}}</td>
                          <td class="text-end" style="font-size:0.8em">{{$item['TotalAmount']}}</td>
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
                        <th class="text text-start" style="font-size:0.8em">Total Taxable Amount</th>
                        <th class="text text-end"></th>
                        <th class="text text-end" style="font-size:0.8em">{{number_format($total_taxable_amount,2)}}</th>
                    </tr>

                  @foreach ($data['taxes'] as $t_row)

                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:0.8em">Total Tax - {{$t_row['cat']}}({{$t_row['rate']}})</th>
                      <th class="text-uppercase text-end" style="font-size:0.8em"></th>
                      <th class="text-uppercase text-end" style="font-size:0.8em">{{$t_row['amt']}}</th>
                  </tr>
                  @endforeach

                  <!-- <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:0.8em">Total</th>
                      <th class="text text-end"></th>
                      <th class="text text-end" style="font-size:0.8em">{{$data['TotalTaxAmount']}}</th>
                  </tr> -->

                  <tr>
                      <th colspan='3'></th>
                      <th class="text text-start" style="font-size:0.8em">Total Amount</th>
                      <th class="text text-end"></th>
                      @php
                        $amount = preg_replace('/[^0-9\.]/', '', $data['CreditDebitNoteRunningTotal']);
                        $t_amount =  preg_replace('/[^0-9\.]/', '',$data['TotalTaxAmount']);
                        if (is_numeric($amount) and is_numeric($t_amount)) {
                            $total_taxable_amount = floatval($t_amount) + floatval($amount);
                        }
                      @endphp
                      <th class="text text-end" style="font-size:0.8em">{{number_format($total_taxable_amount,2)}}</th>
                  </tr>

                  </tbody>
              </table>
            </div>
          </div>
        </div>


          <div class="row">

          <div class="col-md-7 mx-3">
            <strong>VSDC Information</strong><br>
            <table class="datatable table table-bordered">
              <tr>
                <th style="font-size:0.8em">Date:</th>
                <td style="font-size:0.8em">{{$data['VSDCDate']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">Time:</th>
                <td style="font-size:0.8em">{{$data['VSDCTime']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">SDC ID:</th>
                <td style="font-size:0.8em">{{$data['VSDCID']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">{{$data['CreditDebitNoteType']}} Number:</th>
                <td style="font-size:0.8em">{{$data['VSDCInvoiceNumber']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">Invoice Type:</th>
                <td style="font-size:0.8em">{{$data['VSDCInvoiceType']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">Internal Data:</th>
                <td style="font-size:0.8em">{{$data['VSDCInternalData']}}</td>
              </tr>
              <tr>
                <th style="font-size:0.8em">Signature:</th>
                <td style="font-size:0.8em">{{$data['VSDCSignature']}}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-4">
            <div class="py-5 text-end">
              {!! QrCode::size(220)->generate($data['VSDCURL']) !!}
            </div>

          </div>

      </div>

<div class="row my-3 mx-4">
  <p class="text-danger">
  Please note that payment must be made into the correct bank account, refer our USD and ZMW banking details below
  </p>
</div>
          @if(isset($settings->bank))
              <div class="col-md-6">
                  <table class="table table-bordered">
                      <tr>
                          <th colspan="2"><h4>Banking Details</h4></th>
                      </tr>
                      <tr>
                          <th>Bank Name</th>
                          <td>{{$settings->bank}}</td>
                      </tr>
                      <div class="row">
                          <div class="col-6"><b>Account Name:</b></div>
                          <div class="col-6">{{$settings->account_name}}</div>
                      </div>
                      <tr>
                          <th>Branch Name</th>
                          <td>{{$settings->branch}}</td>
                      </tr>
                      <tr>
                          <th>Branch Code</th>
                          <td>{{$settings->branch_code}}</td>
                      </tr>
                      <tr>
                          <th>USD Account Number</th>
                          <td>{{$settings->local_curr_account_no}}</td>
                      </tr>
                      <tr>
                          <th>ZMW Account Number</th>
                          <td>{{$settings->foreign_curr_account_no}}</td>
                      </tr>
                      <tr>
                          <th>Swift Code</th>
                          <td>{{$settings->swift_code}}</td>
                      </tr>
                      <tr>
                          <th>Sort Code</th>
                          <td>{{$settings->sort_code}}</td>
                      </tr>
                  </table>
              </div>
              <div class="col-md-6"></div>
          @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
