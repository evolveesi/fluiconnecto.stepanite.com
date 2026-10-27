@extends('layouts.dashboard',[
    'page_name'=>'Permission Management',
    'section_name'=>'Users',
    'activity_name'=>'Permission Management'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Permissions Profiles</div>
                        <div class="col-md-4"></div>
                        <div class="col-md-4">
                            @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("store-permission"))
                                <div class="d-grid gap-2">
                                    <a class="btn btn-primary btn-sm px-5 rounded-2" data-bs-toggle="modal" data-bs-target=".create-permission">Create
                                        Permission</a>
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
                                <th>Permission Name</th>
                                <th>Number of Roles Assigned</th>
                                @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-permission"))
                                    <th >Edit</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $count = 1
                            @endphp
                            @foreach($permissions as $permission)
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$permission->name}}</td>
                                    <td>{{$permission->number_of_roles()}}</td>
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-permission"))
                                        <td>
                                            <div class="d-grid gap-2">
                                                <a href="" data-bs-toggle="modal" data-bs-target="#edit-permission{{$permission->id}}"
                                               class="btn btn-sm px-5 btn-dark">Edit </a>
                                            </div>
                                        <!-- Modal -->
                                        <div class="modal fade edit-permission{{$permission->id}}"
                                             id="edit-permission{{$permission->id}}" tabindex="-1"
                                             aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <form action="{{route('update-permission')}}" method="post">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="permissionModalLabel">Updating
                                                                Permission</h5>
                                                            <button type="button" class="close" data-bs-dismiss="modal"
                                                                    aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <label for="">Permission Name</label>
                                                                    <input type="hidden" name="id" value="{{$permission->id}}">
                                                                    <input type="text" name="name" class="form-control"
                                                                           value="{{$permission->name}}" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-danger"
                                                                    data-bs-dismiss="modal">Close
                                                            </button>
                                                            <button type="submit" class="btn btn-primary">Update Permission
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
                <form action="{{route('store-permission')}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Creating Permission</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Permission Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Permission</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
