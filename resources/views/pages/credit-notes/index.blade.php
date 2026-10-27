@extends('layouts.dashboard',[
    'page_name'=>'Credit/Debit Notes Report',
    'section_name'=>'Credit/Debit Notes',
    'activity_name'=>'Credit/Debit Notes Report'
])
@section('content')
    <div class="row gutters">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <h5 class="card-header">
                    <div class="row my-2">
                        <div class="col-md-4">Credit/Debit Notes Report</div>
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary fetch-from-sage"
                                data-fetch-url="{{ url('check-sage-uninterfaced-credit-notes') }}"
                                data-fetch-title="Fetch credit notes from Sage?"
                                data-fetch-loading="Fetching credit notes from Sage..."
                                data-fetch-success="Credit note fetch completed.">
                                Fetch from Sage
                            </button>
                        </div>
                    </div>
                </h5>
                <div class="card-body px-4">
                    <div class="table-responsive">
                        <table  class="datatable table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>credit_note_id</th>
                                <th>customet tpin</th>
                                <th>sage_crn_number	</th>
                                <th>status	</th>
                                <th>resultCd	</th>
                                <th>resultMsg	</th>
                                <th>resultDt	</th>
                                <th>rcptNo	</th>
                                <th>intrlData	</th>
                                <th>rcptSign	</th>
                                <th>vsdcRcptPbctDate</th>
                                <th>sdcId	</th>
                                <th>mrcNo	</th>
                                <th>qrCodeUrl	</th>
                                <th>updated_at       </th>
                            </tr>
                            </thead>
                            <tbody>

                                @foreach ($data as $row)
                                    <tr>
                                        <td>{{ $row->credit_note_id	}}</td>
                                        <td>{{ $row->customerTPIN }}</td>
                                        <td>{{ $row->sage_crn_number	}}</td>
                                        <td>
                                            {{ $row->status		}}
                                            @if($row->status=='Pending')
                                            <div class="d-grid gap-2">
                                                <a href="" data-bs-toggle="modal" data-bs-target="#edit-crdn{{$row->id}}"
                                                class="btn btn-sm px-5 btn-dark">Reinterface - {{ $row->sage_invoice_number	}}</a>
                                            </div>
                                            <!-- Modal -->
                                            <div class="modal fade edit-crdn{{$row->id}}"
                                                id="edit-crdn{{$row->id}}" tabindex="-1"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <form action="{{route('reinterface-credit-note')}}" method="post">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{$row->id}}">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="crdnModalLabel">Reinterface CRDN - {{ $row->sage_crn_number	}}
                                                                    </h5>
                                                                <button type="button" class="close" data-bs-dismiss="modal"
                                                                        aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-danger"
                                                                        data-bs-dismiss="modal">Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Interface
                                                                </button>
                                                            </div>
                                                        </form>

                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        </td>
                                        <td>{{ $row->resultCd	}}</td>
                                        <td>{{ $row->resultMsg	}}</td>
                                        <td>{{ $row->resultDt	}}</td>
                                        <td>{{ $row->rcptNo		}}</td>
                                        <td>{{ $row->intrlData	}}</td>
                                        <td>{{ $row->rcptSign	}}</td>
                                        <td>{{ $row->vsdcRcptPbctDate	}}</td>
                                        <td>{{ $row->sdcId	}}</td>
                                        <td>{{ $row->mrcNo	}}</td>
                                        <td>{{ $row->qrCodeUrl	}}</td>
                                        <td>{{ $row->updated_at	}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.fetch-from-sage').forEach(function (button) {
                button.addEventListener('click', function () {
                    var fetchUrl = button.getAttribute('data-fetch-url');
                    var loadingText = button.getAttribute('data-fetch-loading');
                    var successText = button.getAttribute('data-fetch-success');

                    Swal.fire({
                        title: button.getAttribute('data-fetch-title'),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Fetch',
                        cancelButtonText: 'Cancel'
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        button.disabled = true;
                        Swal.fire({
                            title: loadingText,
                            allowOutsideClick: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });

                        fetch(fetchUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json, text/plain, */*',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(function (response) {
                                return response.text().then(function (text) {
                                    var data = null;
                                    try {
                                        data = text ? JSON.parse(text) : null;
                                    } catch (e) {
                                        data = null;
                                    }

                                    if (!response.ok) {
                                        throw new Error((data && data.message) || text || 'Fetch failed.');
                                    }

                                    return data || text;
                                });
                            })
                            .then(function (data) {
                                var message = successText;
                                if (data && typeof data === 'object') {
                                    message = data.message || data.resultMsg || successText;
                                }

                                Swal.fire({
                                    title: 'Success',
                                    text: message,
                                    icon: 'success'
                                }).then(function () {
                                    window.location.reload();
                                });
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    title: 'Fetch failed',
                                    text: error.message,
                                    icon: 'error'
                                });
                            })
                            .finally(function () {
                                button.disabled = false;
                            });
                    });
                });
            });
        });
    </script>
@endsection
