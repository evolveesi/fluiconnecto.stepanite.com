@extends('layouts.dashboard',[
    'page_name'=>'Permission Granting Console',
    'section_name'=>'Users',
    'activity_name'=>'Permission Granting Console'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="datatable table-container">

                <div class="datatable table-responsive px-4">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="live-preview">
                                <div class="row gy-4">
                                    <div class="col-xxl-6 col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <form action="{{route('remove-permission')}}" method="post">
                                                    @csrf
                                                    <h4 class="card-title mb-0 flex-grow-1 pt-3 pb-3">Assigned Permissions</h4>
                                                    <div class="table-responsive">
                                                        <table  class=" datatable table table-hover table-striped">
                                                        <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>
                                                                <p>
                                                                    <span class="px-5 font-weight-bold">Select All</span>
                                                                    <input type="checkbox" class="form-check-input form"
                                                                           onClick="toggle2(this)"/>
                                                                </p>
                                                            </th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($role->role_permissions as $count => $permission)
                                                            <tr>
                                                                <td>{{++$count}}</td>
                                                                <td>{{$permission->permission->name}}</td>
                                                                <td>
                                                                    <p>
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="permissions1[]"
                                                                               value="{{$permission->id}}">
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                        @if(count($role->role_permissions)>0)
                                                            <tfoot>
                                                            <tr>
                                                                <td colspan="3">
                                                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("remove-permission"))
                                                                        <div class="d-grid gap-2">
                                                                            <button type="submit"
                                                                                    class="btn btn-danger btn-sm px-5 waves-effect waves-light">
                                                                                Remove Permission(s)
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            </tfoot>
                                                        @endif
                                                    </table>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <!--end col-->
                                    <div class="col-xxl-6 col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <form action="{{route('add-permission')}}" method="post">
                                                    @csrf

                                                    <input type="hidden" value="{{$role->id}}" name="role_id">

                                                    <h4 class="card-title mb-0 flex-grow-1 pt-3 pb-3">Unassigned
                                                        Permissions</h4>
                                                    <div class="table-responsive">

                                                    <table  class="datatable table table-hover table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>
                                                                <p>
                                                                    <span class="px-5 font-weight-bold">Select All</span>
                                                                    <input type="checkbox" class="form-check-input"
                                                                           onClick="toggle(this)"/>
                                                                </p>
                                                            </th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach($role->missing_permissions() as $count => $permission)
                                                            <tr>
                                                                <td>{{++$count}}</td>
                                                                <td>{{$permission->name}}</td>
                                                                <td>
                                                                    <p class="text-center">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               name="permissions[]" value="{{$permission->id}}">
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        </tbody>
                                                        @if(count($role->missing_permissions())>0)
                                                            <tfoot>
                                                            <tr>
                                                                <td colspan="3">
                                                                    @if(\Illuminate\Support\Facades\Auth::user()->hasPermission("add-permission"))
                                                                        <div class="d-grid gap-2">
                                                                            <button type="submit"
                                                                                    class="btn btn-success px-5 btn-sm waves-effect waves-light">
                                                                                Add Permission(s)
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            </tfoot>
                                                        @endif
                                                    </table>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <!--end col-->
                                </div>
                                <!--end row-->
                            </div>
                        </div>
                        <!--end col-->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>

        function toggle(source) {
            checkboxes = document.getElementsByName('permissions[]');
            for (var i = 0, n = checkboxes.length; i < n; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        function toggle2(source) {
            checkboxes = document.getElementsByName('permissions1[]');
            for (var i = 0, n = checkboxes.length; i < n; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
@endsection
