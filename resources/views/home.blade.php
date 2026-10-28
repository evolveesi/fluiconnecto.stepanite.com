@extends('layouts.dashboard',[
    'page_name'=>'Home',
    'section_name'=>'Dashboard',
    'activity_name'=>'Overview'
])

@section('content')
    <section class="dashboard-hero" aria-labelledby="dashboard-title">
        <div>
            <span class="dashboard-eyebrow">Operations overview</span>
            <h1 id="dashboard-title">Good to see you, {{ Auth::user()->name }}</h1>
            <p>Manage device setup, reference codes, and transaction imports from one place.</p>
        </div>
        <span class="dashboard-hero-mark" aria-hidden="true"><i class="bx bx-grid-alt"></i></span>
    </section>

    <section class="dashboard-details" aria-label="Connected device details">
        <div class="dashboard-detail"><span>TPIN</span><strong>{{ $settings?->tinNumber ?: 'Not configured' }}</strong></div>
        <div class="dashboard-detail"><span>Branch</span><strong>{{ $settings?->branchCode ?: 'Not configured' }}</strong></div>
        <div class="dashboard-detail"><span>Device serial</span><strong>{{ $settings?->dvcSrlNo ?: 'Not configured' }}</strong></div>
        <div class="dashboard-detail"><span>Base URL</span><strong title="{{ $settings?->baseUrl }}">{{ $settings?->baseUrl ?: 'Not configured' }}</strong></div>
    </section>

    <div class="dashboard-section-heading">
        <div><span class="dashboard-eyebrow">Quick actions</span><h2>Get work done</h2></div>
        <p>Select an action to continue</p>
    </div>
    <div class="dashboard-actions">
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".init-device">
            <span class="dashboard-action-icon"><i class="bx bx-fingerprint"></i></span><span><strong>Initialise device</strong><small>Connect and configure a device</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".standard-codes">
            <span class="dashboard-action-icon"><i class="bx bx-coin"></i></span><span><strong>Standard codes</strong><small>Fetch the latest reference codes</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".classification-codes">
            <span class="dashboard-action-icon"><i class="bx bx-cube"></i></span><span><strong>Classification codes</strong><small>Update item classifications</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".fetch-purchases">
            <span class="dashboard-action-icon"><i class="bx bx-sync"></i></span><span><strong>Fetch purchases</strong><small>Bring in purchase records</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".fetch-imports">
            <span class="dashboard-action-icon"><i class="bx bx-import"></i></span><span><strong>Fetch imports</strong><small>Retrieve pending import records</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
        <button type="button" class="dashboard-action" data-bs-toggle="modal" data-bs-target=".offline-transactions">
            <span class="dashboard-action-icon"><i class="bx bx-building"></i></span><span><strong>Offline transactions</strong><small>Process transactions waiting to sync</small></span><i class="bx bx-right-arrow-alt dashboard-action-arrow" aria-hidden="true"></i>
        </button>
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
