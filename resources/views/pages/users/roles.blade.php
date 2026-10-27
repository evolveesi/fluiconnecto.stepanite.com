@extends('layouts.dashboard',[
    'page_name'=>'Roles',
    'section_name'=>'Users',
    'activity_name'=>'Role Management'
])
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row gutters">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="datatable table-container">
                        <h5 class="datatable table-title">
                            <div class="row">
                                <div class="col-md-4">Role Profiles</div>
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("store-role"))
                                        <div class="d-grid gap-2">
                                            <a data-bs-toggle="modal" data-bs-target="#create-role" class="btn btn-sm px-5 btn-primary">Create
                                            Role</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </h5>
                        <hr>
                        <div class="datatable table-responsive px-4">
                            <table class="datatable table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Number of Permissions</th>
                                    <th>Number of Users with Role</th>
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("configure-role"))
                                    <th>Configure</th>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-role"))
                                        <th>Edit</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $count = 1
                                @endphp
                                @foreach($roles as $role)
                                    <tr>
                                        <td>{{$count}}</td>
                                        <td>{{$role->name}}</td>
                                        <td>{{$role->number_of_permissions()}}</td>
                                        <td>{{$role->number_of_users()}}</td>
                                        @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("configure-role"))
                                            <td>
                                                <div class="d-grid gap-2">
                                                    <a href="{{url('roles/configure/'.$role->id)}}"
                                                       class="btn  px-5 btn-sm  btn-dark ">
                                                        Configure
                                                    </a>
                                                </div>
                                            </td>
                                        @endif
                                        @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-role"))
                                            <td>
                                                <div class="d-grid gap-2">
                                                    <a data-bs-toggle="modal" data-bs-target="#edit-role{{$role->id}}"
                                                   class="btn px-5 btn-sm btn-dark">
                                                   Edit
                                                    </a>
                                                </div>

                                            <!-- Modal -->
                                            <div class="modal fade edit-role{{$role->id}}"
                                                 id="edit-role{{$role->id}}" tabindex="-1"
                                                 aria-labelledby="" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <form action="{{route('update-role')}}" method="post">
                                                            @csrf
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="permissionModalLabel">Updating
                                                                    Role</h5>
                                                                <button type="button" class="close" data-bs-dismiss="modal"
                                                                        aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row my-2">
                                                                    <div class="col-md-12">
                                                                        <label for="">Role Name</label>
                                                                        <input type="hidden" name="id" value="{{$role->id}}">
                                                                        <input type="text" name="name" class="form-control"
                                                                               value="{{$role->name}}" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger"
                                                                        data-bs-dismiss="modal">Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Update Role
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
    </div>


    <!-- Modal -->
    <div class="modal fade " id="create-role" tabindex="-1" tabindex="-1" role="dialog"
         aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <form action="{{route('store-role')}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Creating Role</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Role Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Role</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
