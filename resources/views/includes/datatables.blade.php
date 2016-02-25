@section('datatablescss')
<!-- DataTables -->
        <link rel="stylesheet" href="{{ url('js/plugins/datatables/dataTables.bootstrap.css') }}">
@endsection

@section('datatablesjs')
<!-- DataTables -->
        <script src="{{ url('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ url('js/plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
    @if (isset($datatables))
        <script>
            $(function () {
        @foreach ($datatables as $table)
                $('#{{ $table }}').DataTable();
        @endforeach
            });
        </script>
    @endif
@endsection
