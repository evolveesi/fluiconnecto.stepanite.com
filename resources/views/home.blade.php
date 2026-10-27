@extends('layouts.dashboard',[
    'page_name'=>'Home',
    'section_name'=>'Dashboard',
    'activity_name'=>'Live Statistics'
])

@section('content')
    <div class="row mt-2 mb-2">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th><h5>TPIN</h3></th><td><h5>{{$settings->tinNumber}}</h3></td>
                        <th><h5>Branch</h3></th><td><h5>{{$settings->branchCode}}</h3></td>
                        <th><h5>Serial</h3></th><td><h5>{{$settings->dvcSrlNo}}</h3></td>
                        <th><h5>Base URL</h3></th><td><h5>{{$settings->baseUrl}}</h3></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".init-device">
                <div class="card bg-dark">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-fingerprint text-white font-60 '></i>
                        <h2 class="text text-white text-center">Initialise Device</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".standard-codes">
                <div class="card bg-secondary">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-coin text-white font-60 '></i>
                        <h2 class="text text-white text-center">Standard Codes</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".classification-codes">
                <div class="card bg-warning">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-cube text-white font-60 '></i>
                        <h2 class="text text-white text-center">Classification Codes</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".fetch-purchases">
                <div class="card bg-dark">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-sync text-white font-60 '></i>
                        <h2 class="text text-white text-center">Fetch Purchase</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".fetch-imports">
                <div class="card bg-secondary">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-import text-white font-60 '></i>
                        <h2 class="text text-white text-center">Fetch Imports</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a data-bs-toggle="modal" data-bs-target=".offline-transactions">
                <div class="card bg-warning">
                    <div class="card-body">
                        <p class="text-center">
                            <i class='bx bx-building text-white font-60 '></i>
                        <h2 class="text text-white text-center">Offline Transactions</h2>
                        </p>

                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="modal fade init-device" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <form action="{{route('initialise-device')}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Device Details</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row my-2">
                            <div class="col-md-12">
                                    <div class="row my-2">
                                        <div class="col-md-12">
                                            <label for="">TPIN Number</label>
                                            <input type="text" class="form-control" name="tinNumber" required/>
                                        </div>
                                    </div>
                                    <div class="row my-2">
                                        <div class="col-md-12">
                                            <label for="">Branch Code</label>
                                            <input type="text" class="form-control" name="branchCode" required/>
                                        </div>
                                    </div>
                                    <div class="row my-2">
                                        <div class="col-md-12">
                                            <label for="">Device Serial Number</label>
                                            <input type="text" class="form-control" name="dvcSrlNo" required/>
                                        </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Initialise Device</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    <div class="modal fade standard-codes" tabindex="-1" tabindex="-1" role="dialog"
        aria-labelledby="mySmallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Standard Codes</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row my-2">
                        <div class="col-md-12">
                                <p>Get Standard Codes</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <a href="{{url('get-standard-codes')}}" class="btn btn-primary">Get Standard Codes</a>
                </div>
        </div>
    </div>
    </div>

    <div class="modal fade classification-codes" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Item Classification Codes</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row my-2">
                        <div class="col-md-12">
                            <p>Fetch Item Classification</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <a href="{{url('classification-codes')}}" class="btn btn-primary">Fetch Item Classification</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade fetch-purchases" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Purchases</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row my-2">
                        <div class="col-md-12">
                            <p>Fetch Purchases</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <a href="{{url('fetch-purchases')}}" class="btn btn-primary">Fetch Purchases</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade fetch-imports" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Imports</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row my-2">
                        <div class="col-md-12">
                            <p>Fetch Imports</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <a href="{{url('fetch-imports')}}" class="btn btn-primary">Fetch Imports</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade offline-transactions" tabindex="-1" tabindex="-1" role="dialog"
        aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">Offline Transactions</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row my-2">
                        <div class="col-md-12">
                            <p>Process Offline Transactions</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <a href="{{route('process-offline')}}" class="btn btn-primary">Process Offline Transactions</a>
                </div>
            </div>
        </div>
    </div>
@endsection
