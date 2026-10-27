@extends('layouts.dashboard',[
    'page_name'=>'User Management',
    'section_name'=>'Users',
    'activity_name'=>'User Management'
])
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row gutters">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="datatable table-container">
                        <h5 class="datatable table-title">
                            <div class="row">
                                <div class="col-md-4">User Profiles</div>
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("store-user"))
                                        <div class="d-grid gap-2">
                                            <a data-bs-toggle="modal" class="btn btn-sm px-5 btn-primary" data-bs-target="#bd-add-user-modal">Create
                                                User</a>
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
                                    <th>Fullname</th>
                                    <th>Email Address</th>
                                    <th>Role Name</th>
                                    <th>Last Login</th>
                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-user"))
                                        <th>Edit</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>

                                @php
                                    $count = 1
                                @endphp
                                @foreach($users as $user)
                                    <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$user->name}}</td>
                                    <td>{{$user->email}}</td>
                                    <td>{{optional($user->role)->name}}</td>
                                    <td>{{$user->last_login}}</td>
                                        @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("update-user"))
                                            <td>
                                                <div class="d-grid gap-2">
                                                    <a class="btn btn-dark btn-sm px-5" data-bs-toggle="modal" data-bs-target="#bd-edit-user-modal{{$user->id}}">
                                                        Edit
                                                    </a>
                                                </div>
                                        <div class="modal fade " id="bd-edit-user-modal{{$user->id}}" tabindex="-1" role="dialog"
                                             aria-labelledby="mySmallModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="mySmallModalLabel">Edit User</h5>
                                                        <button type="button" class="close" data-bs-dismiss="modal"
                                                                aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form action="{{route('update-user')}}" method="post">
                                                            @csrf
                                                            <div class="row my-2">
                                                                <div class="col-md-12">
                                                                    <label for="">Fullname</label>
                                                                    <input type="text" class="form-control" name="name" required
                                                                           value="{{$user->name}}">
                                                                </div>
                                                            </div>
                                                            <div class="row my-2">
                                                                <div class="col-md-12">
                                                                    <label for="">Email Address</label>
                                                                    <input type="hidden" name="id" value="{{$user->id}}"/>
                                                                    <input type="text" class="form-control" name="email"
                                                                           required
                                                                           value="{{$user->email}}">
                                                                </div>
                                                            </div>
                                                            <div class="row my-2">
                                                                <div class="col-md-12">
                                                                    <label for="">Account Status</label>
                                                                    <select name="status" id="" required class="form-control">
                                                                        <option value="">-- Select Account Status --</option>
                                                                        <option value="1" {{$user->status?"selected":""}}>
                                                                            Enable
                                                                        </option>
                                                                        <option value="0"{{!$user->status?"selected":""}}>
                                                                            Disable
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row my-2">
                                                                <div class="col-md-12">
                                                                    <label for="">Role</label>
                                                                    <select name="role_id" id="" required class="form-control">
                                                                        <option value="">-- Select Role --</option>
                                                                        @foreach($roles as $role)
                                                                            <option
                                                                                value="{{$role->id}}" {{$user->role_id==$role->id?"selected":""}}>{{$role->name}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger"
                                                                        data-bs-dismiss="modal">Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>

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

    <div class="modal fade bd-add-user-modal" id="bd-add-user-modal" tabindex="-1" role="dialog"
         aria-labelledby="" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="">Add User</h5>
                    <button type="button" class="close" data-bs-dismiss="modal"
                            aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{route('store-user')}}" method="post">
                        @csrf
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Fullname</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Email Address</label>
                                <input type="text" class="form-control" name="email"
                                       required>
                            </div>
                        </div>
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Account Status</label>
                                <select name="status" id="" required class="form-control">
                                    <option value="">-- Select Account Status --</option>
                                    <option value="1">Enable</option>
                                    <option value="0">Disable</option>
                                </select>
                            </div>
                        </div>
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="">Role</label>
                                <select name="role_id" id="" required class="form-control">
                                    <option value="">-- Select Role --</option>
                                    @foreach($roles as $role)
                                        <option
                                            value="{{$role->id}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="password">{{ __('Password') }}</label>
                                <input id="password" type="password"
                                       class="form-control" name="password"
                                       required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row my-2">
                            <div class="col-md-12">
                                <label for="password">{{ __('Confirm Password') }}</label>
                                <input id="password-confirm" type="password" class="form-control"
                                       name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger"
                                    data-bs-dismiss="modal">Close
                            </button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection
