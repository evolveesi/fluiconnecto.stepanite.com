@extends('layouts.dashboard',[
    'page_name'=>'Business Settings',
    'section_name'=>'Settings',
    'activity_name'=>'Business Details'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Business Settings/Details</div>
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                        </div>
                    </div>
                </h5>
                <div class="row my-2">
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <img src="{{env('APP_URL').'/storage/'.$settings->logo}}" width="240px"  alt="">
                    </div>
                    <div class="col-md-4"></div>
                </div>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table  class="datatable table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Business Name</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Telephone</th>
                                <th>Bank Name</th>
                                <th>Branch</th>
                                <th>Local Currency Acc No.</th>
                                <th>Foreign Currency Acc No.</th>
                                <th>Swift Code</th>
                                <th>Creation Date</th>
                                <th>Last Update Date</th>
{{--                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("push-items"))--}}
                                    <th >Update</th>
{{--                                @endif--}}
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$settings->company_name}}</td>
                                    <td>{{$settings->address}}</td>
                                    <td>{{$settings->email}}</td>
                                    <td>{{$settings->tel}}</td>
                                    <td>{{$settings->bank}}</td>
                                    <td>{{$settings->branch}}</td>
                                    <td>{{$settings->local_curr_account_no}}</td>
                                    <td>{{$settings->foreign_curr_account_no}}</td>
                                    <td>{{$settings->swift_code}}</td>
                                    <td>{{$settings->created_at}}</td>
                                    <td>{{$settings->updated_at}}</td>
{{--                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("push-items"))--}}
                                        <td>
                                            <div class="d-grid gap-2">
                                                <a href="" data-bs-toggle="modal" data-bs-target="#edit-permission{{$settings->id}}"
                                                   class="btn btn-sm px-5 btn-dark">Updating Business Settings</a>
                                            </div>
                                            <!-- Modal -->
                                            <div class="modal fade edit-permission{{$settings->id}}"
                                                 id="edit-permission{{$settings->id}}" tabindex="-1"
                                                 aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <form action="{{route('update-settings')}}" enctype="multipart/form-data" method="post">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="permissionModalLabel">Business Details Update
                                                                    </h5>
                                                                <button type="button" class="close" data-bs-dismiss="modal"
                                                                        aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row my-2">
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Company Name" required name="company_name" value="{{$settings->company_name}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Telephone Number" required name="tel" value="{{$settings->tel}}" class="form-control">
                                                                    </div>
                                                                </div>
                                                                <div class="row my-2">
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Email Address" required name="email" value="{{$settings->email}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="">Business Logo</label>
                                                                        <input type="file" name="logo" class="form-control">
                                                                    </div>
                                                                </div>
                                                                <div class="row my-2">
                                                                    <div class="col-md-12">
                                                                        <label for="">Business Address (seperate by &lt;br/&gt; for new line)</label>
                                                                        <textarea name="address" required class="form-control">{{$settings->address}}</textarea>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" value="{{$settings->id}}" name="id">

                                                                <div class="row my-2">
                                                                    <div class="col-md-12">
                                                                        <input type="text" placeholder="Bank Name" required name="bank" value="{{$settings->bank}}" class="form-control">
                                                                    </div>
                                                                </div>

                                                                <div class="row my-2">
                                                                    <div class="col-md-4">
                                                                        <input type="text" placeholder="Account Name" required name="account_name" value="{{$settings->account_name}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <input type="text" placeholder="Branch Name" required name="branch" value="{{$settings->branch}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <input type="text" placeholder="Bank Code" required name="branch_code" value="{{$settings->branch_code}}" class="form-control">
                                                                    </div>
                                                                </div>
                                                                <div class="row my-2">
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Local Currency Account No." required name="local_curr_account_no" value="{{$settings->local_curr_account_no}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Foreign Currency Account No." required name="foreign_curr_account_no" value="{{$settings->foreign_curr_account_no}}" class="form-control">
                                                                    </div>
                                                                </div>
                                                                <div class="row my-2">
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Swift Code" required name="swift_code" value="{{$settings->swift_code}}" class="form-control">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input type="text" placeholder="Sort Code" required name="sort_code" value="{{$settings->sort_code}}" class="form-control">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger"
                                                                        data-bs-dismiss="modal">Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Update
                                                                </button>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>
                                        </td>
{{--                                    @endif--}}
                                </tr>
                                @php
                                    $count += 1
                                @endphp
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade create-permission" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <form action="{{route('pull-items')}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Pulling Items from ERP</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row my-2">
                            <div class="col-md-12">
                                <div class="alert alert-info border-0 bg-info alert-dismissible fade show py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="font-35 text-white"><i class='bx bxs-check-circle'></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 text-white">System Notice!</h6>
                                            <div class="text-white">Are you sure you want to proceed?</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Proceed</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
