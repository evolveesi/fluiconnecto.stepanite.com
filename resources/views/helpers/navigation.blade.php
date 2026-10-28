<!--navigation-->
<div class="primary-menu">
    <nav class="navbar navbar-expand-lg align-items-center">
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header border-bottom">
                <div class="d-flex align-items-center">
                    <a href="{{url('home')}}" class="d-flex align-items-center gap-2">
                        <img src="{{asset('assets/images/logo-icon.png')}}" class="logo-icon" alt="Stepanite">
                        <h4 class="logo-text mb-0">Stepanite</h4>
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav align-items-center flex-grow-1">
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="{{url('home')}}" >
                            <div class="parent-icon"><i class='bx bx-home-alt'></i>
                            </div>
                            <div class="menu-title d-flex align-items-center">Dashboard</div>
                        </a>

                    </li>

                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("user-management"))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                                <div class="parent-icon"><i class="bx bx-grid-alt"></i>
                                </div>
                                <div class="menu-title d-flex align-items-center">Access Control</div>
                                <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
                            </a>
                            <ul class="dropdown-menu">
                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("view-users"))
                                    <li><a class="dropdown-item" href="{{url('users')}}"><i class='bx bx-user-circle'></i>Users</a></li>
                                @endif
                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("view-roles"))
                                    <li><a class="dropdown-item" href="{{url('roles')}}"><i class='bx bxs-user-account' ></i>Roles</a></li>
                                @endif
                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("view-permissions"))
                                    <li><a class="dropdown-item" href="{{url('permissions')}}"><i class='bx bxs-user-detail' ></i>Permissions</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                                <div class="parent-icon"><i class="bx bx-sync"></i>
                                </div>
                                <div class="menu-title d-flex align-items-center">Interfacing Reports</div>
                                <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{url('items-interfacing-report')}}"><i class='bx bx-cube'></i>Items Report</a></li>
                                <li><a class="dropdown-item" href="{{route('device-requests-responses')}}"><i class='bx bx-fingerprint'></i>Device Report</a></li>
                                <li><a class="dropdown-item" href="{{route('classification-codes-list')}}"><i class='bx bx-book-content'></i>Class Codes</a></li>
                                <li><a class="dropdown-item" href="{{route('standard-codes-list')}}"><i class='bx bx-building'></i>Standard Codes</a></li>
                                <li><a class="dropdown-item" href="{{route('invoices-interfacing-report')}}"><i class='bx bx-cube'></i>Invoices Report</a></li>
                                <li><a class="dropdown-item" href="{{route('credit-notes-interfacing-report')}}"><i class='bx bx-cube'></i>Credit/Debit Notes</a></li>
                            </ul>
                        </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                            <div class="parent-icon"><i class="bx bx-import"></i>
                            </div>
                            <div class="menu-title d-flex align-items-center">Imports</div>
                            <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{url('imports-report')}}"><i class='bx bx-x-circle'></i>Pending</a></li>
                            <li><a class="dropdown-item" href="{{route('cimports-report')}}"><i class='bx bx-check-circle'></i>Completed</a></li>
                           </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                            <div class="parent-icon"><i class="bx bx-library"></i>
                            </div>
                            <div class="menu-title d-flex align-items-center">Purchases</div>
                            <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{url('purchases-report')}}"><i class='bx bx-x-circle'></i>Pending</a></li>
                            <li><a class="dropdown-item" href="{{url('cpurchases-report')}}"><i class='bx bx-check-circle'></i>Completed</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>
<!--end navigation-->
