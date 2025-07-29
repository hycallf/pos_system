@extends('layouts.app')

@section('title', 'Create Customer')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-lg-12">
                    @include('utils.alerts')
                    <div class="form-group">
                        <button class="btn btn-primary">Create Customer <i class="bi bi-check"></i></button>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_name">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_name" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="customer_email" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_phone">Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_phone" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <select name="country" id="country" class="form-control" required>
                                            <option value="" selected disabled>Select Country</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="state">State / Province</label>
                                        <select name="state" id="state" class="form-control" required disabled>
                                            <option value="" selected disabled>Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <select name="city" id="city" class="form-control" required disabled>
                                            <option value="" selected disabled>Select City</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" name="address" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            // API Endpoint
            const API_URL = "https://api.countrystatecity.in/v1/";
            const API_KEY = "{{ env('COUNTRYSTATECITY_API_KEY') }}";
            // Headers untuk API
            var headers = new Headers();
            headers.append("X-CSCAPI-KEY", API_KEY);

            var requestOptions = {
                method: 'GET',
                headers: headers,
                redirect: 'follow'
            };

            // Fungsi untuk memuat negara
            function loadCountries() {
                fetch(API_URL + "countries", requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let countryOptions = "<option value='' selected disabled>Select Country</option>";
                        data.forEach(country => {
                            countryOptions +=
                                `<option value="${country.iso2}" data-name="${country.name}">${country.name}</option>`;
                        });
                        $('#country').html(countryOptions);
                    })
                    .catch(error => console.log('error', error));
            }

            // Fungsi untuk memuat provinsi/negara bagian
            function loadStates(countryCode) {
                $('#state').html("<option value='' selected disabled>Loading...</option>").prop('disabled', false);
                fetch(`${API_URL}countries/${countryCode}/states`, requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let stateOptions = "<option value='' selected disabled>Select State</option>";
                        data.forEach(state => {
                            stateOptions +=
                                `<option value="${state.iso2}" data-name="${state.name}">${state.name}</option>`;
                        });
                        $('#state').html(stateOptions);
                        $('#city').html("<option value='' selected disabled>Select City</option>").prop(
                            'disabled', true);
                    })
                    .catch(error => console.log('error', error));
            }

            // Fungsi untuk memuat kota
            function loadCities(countryCode, stateCode) {
                $('#city').html("<option value='' selected disabled>Loading...</option>").prop('disabled', false);
                fetch(`${API_URL}countries/${countryCode}/states/${stateCode}/cities`, requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let cityOptions = "<option value='' selected disabled>Select City</option>";
                        data.forEach(city => {
                            cityOptions += `<option value="${city.iso2}">${city.name}</option>`;
                        });
                        $('#city').html(cityOptions);
                    })
                    .catch(error => console.log('error', error));
            }

            // Event listener saat negara dipilih
            $('#country').on('change', function() {
                const countryCode = $(this).val();
                const selectedCountry = $(this).find('option:selected').data('name');
                loadStates(countryCode);
            });

            // Event listener saat provinsi/negara bagian dipilih
            $('#state').on('change', function() {
                const countryCode = $('#country').val();
                const selectedCountry = $('#country').find('option:selected').data('name');
                const stateCode = $(this).val();
                const selectedState = $(this).find('option:selected').data('name');

                loadCities(countryCode, stateCode);
            });

            // Memuat daftar negara saat halaman pertama kali dibuka
            loadCountries();
        });
    </script>
@endpush
