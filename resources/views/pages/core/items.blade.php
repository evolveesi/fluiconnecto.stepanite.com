@extends('layouts.dashboard',[
    'page_name'=>'Items Interfacing Report',
    'section_name'=>'Items',
    'activity_name'=>'Items Interfacing Report'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Items Interfacing Report</div>
                        <div class="col-md-4">
                            @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("batch-push-items"))
                                <div class="d-grid gap-2">
                                    <a href="{{route("batch-push-items")}}" class="btn btn-dark btn-sm px-5 rounded-2 " >Items Batch
                                        Interfacing</a>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("pull-items"))
                                <div class="d-grid gap-2">
                                    <a class="btn btn-primary btn-sm px-5 rounded-2" data-bs-toggle="modal" data-bs-target=".create-permission">Pull
                                        from ERP</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </h5>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table  class="datatable table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Code</th>
                                <th>Interfacing Status</th>
                                <th>Interfacing Message</th>
                                <th>Creation Date</th>
                                <th>Last Update Date</th>
                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("push-items"))
                                    <th >Reinterface</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($items as $item)
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$item->itemCd}}</td>
                                    <td>{{$item->status}}</td>
                                    <td>{{$item->message}}</td>
                                    <td>{{$item->created_at}}</td>
                                    <td>{{$item->updated_at}}</td>
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("push-items"))
                                        <td>
                                            <div class="d-grid gap-2">
                                                <a href="" data-bs-toggle="modal" data-bs-target="#edit-permission{{$item->id}}"
                                                   class="btn btn-sm px-5 btn-dark">Interface Item to Smart Invoicing</a>
                                            </div>
                                            <!-- Modal -->
                                            <div class="modal fade edit-permission{{$item->id}}"
                                                 id="edit-permission{{$item->id}}" tabindex="-1"
                                                 aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <form action="{{route('push-item')}}" method="post">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="permissionModalLabel">Manual Item Interfacing
                                                                    </h5>
                                                                <button type="button" class="close" data-bs-dismiss="modal"
                                                                        aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="alert alert-info border-0 bg-info alert-dismissible fade show py-2">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="font-35 text-white"><i class='bx bxs-check-circle'></i>
                                                                        </div>
                                                                        <div class="ms-3">
                                                                            <h6 class="mb-0 text-white">System Notice!</h6>
                                                                            <div class="text-white">Are you sure you want to proceed with interfacing to Smart Invoicing?</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" value="{{$item->itemCd}}" name="itemCd">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger"
                                                                        data-bs-dismiss="modal">Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Proceed
                                                                </button>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
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
