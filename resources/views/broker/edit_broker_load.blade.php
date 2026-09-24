@extends('layout.compact.app')
@section('content')


<style>
    #bolDownloadArea h3 {
    font-family: Arial, sans-serif;
}

#bolDownloadArea {
    zoom: 0.85;
}

    ul#navTabs,
    ul#navTabs1 {
        background: #a6ce3a;
        padding: 10px;
        margin-bottom: 10px;
    }

    button.close.close-modal-btn {
        background: red;
        color: #fff;
        border: navajowhite;
        border-radius: 10px;
        padding: 0px 10px;
    }
    #shipperForms {
        padding: 0;
    }

#mc-success-message{
    padding: 10px;
    background-color: rgb(212, 237, 218);
    color: rgb(21, 87, 36);
    margin-bottom: 10px;
    border: 1px solid rgb(195, 230, 203);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}

#mc-error-message{
    padding: 10px;
    background-color: rgb(243, 118, 129);
    color: rgb(87, 21, 26);
    margin-bottom: 10px;
    border: 1px solid rgb(243, 118, 129);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}
.form-check-input[type=checkbox] {
    border-radius: .25em;
    border: 2px solid;
}
</style>

<div id="mc-success-message" style="display: none;"></div>
<div id="mc-error-message" style="display: none;"></div>

<div class="page-content">
    <div class="container-fluid">
        @if(session('error'))
        <div class="alert alert-danger" id="error-alert">
            {{ session('error') }}
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success" id="success-alert">
            {{ session('success') }}
        </div>
        @endif
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Load</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">Edit Load</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <form method="POST" action="{{ route('broker.load.update', encrypt($post->id)) }}" id="myFormLoad" enctype="multipart/form-data">
                        @csrf
                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 18px;text-align: left;font-weight: 700;margin-left: 0;">Edit Load</h3>
                        </div>
                        <div class="card-body text-left">
                            <div class="row">
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Load Number
                                        </label>
                                        <input class="form-control" name="load_number" value="{{ $post->load_number }}"
                                            title="Load number generated automatically" disabled style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Bill To <code>*</code></label>
                                        <select id="load_bill_to" name="load_bill_to" class="form-control mySelect2" data-placeholder="Select Customer" required>
                                            <option value="">Select Customer</option>
                                            @foreach($allCustomers as $customerOption)
                                                <option value="{{ $customerOption->customer_name }}" 
                                                    data-id="{{ $customerOption->id }}"
                                                    data-available-credit="{{ (float) get_customer_available_credit_limit($customerOption) }}"
                                                    data-remaining-credit="{{ (float) ($customerOption->remaining_credit ?? 0) }}"
                                                    data-invoice-credit-limit="{{ (float) ($customerOption->invoice_credit_limit ?? 0) }}"
                                                    {{ $post->load_bill_to == $customerOption->customer_name ? 'selected' : '' }}>
                                                    {{ $customerOption->customer_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" id="customer_id" name="customer_id" class="form-control" value="{{ $post->customer_id }}">
                                <input type="hidden" id="customer_name" name="customer_name" class="form-control" value="{{ $post->load_bill_to }}">

                                <script>
                                    $(document).ready(function () {
                                        var originalCustomerId = '{{ $post->customer_id }}';
                                        
                                        if ($.fn.select2) {
                                            $('#load_bill_to').select2({
                                                placeholder: 'Select Customer',
                                                allowClear: true
                                            });
                                        }

                                        $('#load_bill_to').on('change', function () {
                                            var selectedCustomer = $(this).find('option:selected');
                                            var customerId = selectedCustomer.data('id');
                                            var customerName = selectedCustomer.val();

                                            $('#customer_id').val(customerId || '');
                                            $('#customer_name').val(customerName || '');
                                            
                                            // Update credit limits for the form validation
                                            currentInvoiceLimit = parseFloat(selectedCustomer.data('invoice-credit-limit')) || 0;
                                            currentRemainingLimit = parseFloat(selectedCustomer.data('remaining-credit')) || 0;
                                            
                                            // Validate credit when customer changes
                                            validateBrokerCustomerCredit(customerId, originalCustomerId);
                                            
                                            // Revalidate charges with new customer's limits
                                            validateCustomerChargeLimits(null);
                                        });
                                        
                                        // Validate on page load
                                        validateBrokerCustomerCredit(originalCustomerId, originalCustomerId);
                                    });
                                    
                                    function validateBrokerCustomerCredit(selectedCustomerId, originalCustomerId) {
                                        var selectedOption = $('#load_bill_to').find('option:selected');
                                        var availableCredit = parseFloat(selectedOption.data('available-credit')) || 0;
                                        var invoiceCreditLimit = parseFloat(selectedOption.data('invoice-credit-limit')) || 0;
                                        var finalRate = parseFloat($('#shipper_load_final_rate').val()) || 0;
                                        var $message = $('#creditlimitcheck');

                                        if (!selectedCustomerId || !finalRate || finalRate <= 0) {
                                            $message.html('');
                                            return;
                                        }

                                        // If customer changed to a NEW one
                                        if (selectedCustomerId && selectedCustomerId != originalCustomerId) {
                                            if (finalRate > availableCredit) {
                                                var shortage = finalRate - availableCredit;
                                                $message.html('<small style="color: #dc3545; font-weight: 600;">⚠️ New Customer - Insufficient Credit! Available: $' + availableCredit.toFixed(2) + ' | Need: $' + finalRate.toFixed(2) + ' | Shortage: $' + shortage.toFixed(2) + '</small>');
                                            } else {
                                                var available = availableCredit - finalRate;
                                                $message.html('<small style="color: #28a745; font-weight: 600;">✓ New Customer - Credit OK! Available after: $' + available.toFixed(2) + '</small>');
                                            }
                                        } else if (selectedCustomerId == originalCustomerId) {
                                            // Same customer - normal validation
                                            if (finalRate > availableCredit) {
                                                var shortage = finalRate - availableCredit;
                                                $message.html('<small style="color: #dc3545; font-weight: 600;">⚠️ Insufficient Credit! Available: $' + availableCredit.toFixed(2) + ' | Need: $' + finalRate.toFixed(2) + ' | Shortage: $' + shortage.toFixed(2) + '</small>');
                                            } else {
                                                var available = availableCredit - finalRate;
                                                $message.html('<small style="color: #0c7ce6; font-weight: 600;">✓ Available: $' + available.toFixed(2) + '</small>');
                                            }
                                        }
                                    }
                                </script>

                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Dispatcher <code>*</code></label>
                                        
                                        <input class="form-control" name="load_dispatcher"  value="{{ $post->user?->name }}" required readonly
                                            style="width: 100%;">
                                    </div>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>CMT Agent</label>
                                        <input class="form-control" name="cmt_agent" id="load_cmt_agent" value="{{ $post->cmt_agent }}"
                                            style="width: 100%;" autocomplete="off" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <input class="form-control" name="load_status" id="load_status" value="{{ $post->load_status }}" value="{{ $post->load_status }}"
                                            readonly style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>W/O # </label>
                                        <input class="form-control" name="load_workorder" style="width: 100%;" value="{{ $post->load_workorder }}" 
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Customer r/f# </label>
                                        <input class="form-control" name="customer_refrence_number" style="width: 100%;" value="{{ $post->customer_refrence_number }}"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Payment Type
                                            <code>*</code></label>
                                        <select class="form-control" required name="load_payment_type"
                                            style="width: 100%;">
                                            <option value="">Select payment Type</option>
                                            <option value="Prepaid" {{ $post->load_payment_type == 'Prepaid' ? 'selected' : '' }}>Prepaid</option>
                                                    <option value="Postpaid" {{ $post->load_payment_type == 'Postpaid' ? 'selected' : '' }}>Postpaid</option>
                                                </select>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Load type<code>*</code> </label>
										
                                        <div class="purple">
                                            <select class="form-control" required name="load_type_two" id="load_type_two"
                                                style="width: 100%;">
                                                <option value="">Select</option>
												<option value="OTR" {{ $post->load_type_two == 'OTR' ? 'selected' : '' }}>OTR</option>
												<option value="DRAYAGE" {{ $post->load_type_two == 'DRAYAGE' ? 'selected' : '' }}>DRAYAGE</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Shipment Type<code>*</code></label>
                                        <select class="form-control" required name="load_type" id="load_type" style="width: 100%;">
                                            <option value="">Select Shipment Type</option>
                                            @foreach($shipmentType as $shipment)
                                            <option value="{{$shipment->name}}" {{ $post->load_type == $shipment->name ? 'selected' : '' }}>{{$shipment->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Currency</label>
										
                                        <select class="form-control" name="load_currency" style="width: 100%;">
                                            <option selected="selected">Select
                                                Currency
                                            </option>
                                            <option value="$" {{ $post->load_currency == '$' ? 'selected' : '' }}>$</option>
                                                    <option value="CAD" {{ $post->load_currency == 'CAD' ? 'selected' : '' }}>CAD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>Equipment Type
                                            <code>*</code></label>
                                        <select class="form-control mySelect2" name="load_equipment_type" id="load_equipment_type" style="width: 100%;" required>
                                            <option value="">Select Equipment </option>
                                            @foreach($equipmentType as $equipment)
                                            <option value="{{$equipment->name}}" {{ $post->load_equipment_type == $equipment->name ? 'selected' : '' }}>{{$equipment->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Customer </h3>
                        </div>
                        <div class="card-body" id="CustomerForms">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="form-group" id="shipper_rate_div">
                                        <label>Customer Base Rate
                                            <code>*</code></label>
                                        <input type="number"
    class="form-control number value"
    name="load_shipper_rate"
    value="{{ $post->load_shipper_rate }}"
    autocomplete="off"
    id="load_shipper_rate"
    required
    {{ $post->cpr_check == 'Verified' ? 'readonly' : '' }}
    style="width: 100%;">
                                        
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>F.S.C Rate % <input hidden type="checkbox"
                                                name="calculate_fsc_percentage" id="calculate_fsc_percentage"></label>
                                        <input class="form-control number percent" name="load_fsc_rate"
                                            autocomplete="off" id="load_fsc_rate" value="{{ $post->load_fsc_rate }}" style="width: 100%;" required @if($post->load_shipper_rate > 0 || !empty($post->load_shipper_rate)) readonly @endif>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label class="other_charge d-flex">Customer Other Charges &nbsp; <i
                                                class="fa fa-plus" style="color: #0c7ce6;" data-bs-toggle="modal"
                                                data-bs-target="#myModal" id="load_shipper_other_charges"></i>
                                        </label>
                                        <input id="totalChargeAmount" class="form-control number percent"
                                            style="width: 100%;" name="shipper_total_other_charge"  readonly>
                                        <input type="hidden" id="old_shipper_load_final_rate" name="old_shipper_load_final_rate" value="{{ $post->shipper_load_final_rate ?? 0 }}">
                                    </div>

                                    <div class="modal close_shipper_other_charges_form p-0" id="myModal">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">

                                                <!-- Modal Header -->
                                                <div class="modal-header">
                                                    <h4 class="card-header"
                                                        style="font-size: 17px;text-align: left;font-weight: 700;">
                                                        Customer Other Charges</h4>
                                                    <button type="button" class="close close-modal-btn"
                                                        style="font-size: 23px;top: 30px;"  data-bs-dismiss="modal" aria-label="Close">&times;</button>
                                                </div>
                                                <!-- Modal Body -->
                                                <div class="modal-body">
                                                    <div class="container">
                                                        @php
                                                        $shipperCharges =
                                                        json_decode($post->shipper_load_other_charge, true);
                                                        if (json_last_error() !== JSON_ERROR_NONE ||
                                                        !is_array($shipperCharges)) {
                                                        // Handle JSON error or invalid data
                                                        $shipperCharges = [];
                                                        }

                                                        $carrierCharges =
                                                        json_decode($post->carrier_load_other_charge, true);
                                                        if (json_last_error() !== JSON_ERROR_NONE ||
                                                        !is_array($carrierCharges)) {
                                                        // Handle JSON error or invalid data
                                                        $carrierCharges = [];
                                                        }
                                                        @endphp
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="shipperchargeType">Charge Type:</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group mt-3">
                                                                    <label>For Invoice:</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group">
                                                                    <label>Amount:</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        @foreach($shipperCharges as $index => $shipperCharge)
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control"
                                                                                name="shipperchargeType[{{ $index }}]"
                                                                                value="{{ htmlspecialchars($shipperCharge['type'] ?? '') }}"
                                                                                placeholder="Enter Charge Type">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="form-group mt-3">
                                                                            <input type="checkbox" class="form-check-input for_invoice"
                                                                                name="for_invoice[{{ $index }}]"
                                                                                value="on"
                                                                                {{ isset($shipperCharge['for_invoice']) && $shipperCharge['for_invoice'] === 'on' ? 'checked' : '' }}>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="form-group">
                                                                            <input type="text" step="0.01" min="0" class="form-control shipperchargeAmount"
                                                                                name="shipperchargeAmount[{{ $index }}]"
                                                                                value="{{ $shipperCharge['amount'] ?? 0 }}"
                                                                                placeholder="Enter Amount">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-1" style="margin-top: 21px;">
                                                                        <button class="remove-row" type="button" style="background:unset;border:none; color:red;">
                                                                            <i class="fa fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                            </div>
                                                        @endforeach




                                                        <!-- Add a button to dynamically add more rows if needed -->
                                                        <div id="dynamic-field-container">
                                                            <!-- Rows will be dynamically added here -->
                                                        </div>


                                                        <!-- Container for new rows -->
                                                        <div id="chargeRowsContainer"></div>
                                                    </div>
                                                </div>
                                                <div class="text-center mb-2 mt-2">
                                                    <button type="button" class="btn btn-success"
                                                                id="addChargeBtn">Add New Charges</button>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>Final Customer Rate <code>*</code></label>
                                        <input type="text" class="readonly form-control" name="shipper_load_final_rate"
                                            autocomplete="off" id="shipper_load_final_rate"
                                            style="background-color:#e9ecef;" value="{{ $post->shipper_load_final_rate ?? 0 }}"  required />
                                        <p id="creditlimitcheck"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Carrier</h3>
                        </div>
                        <div class="card-body" id="CarrierForms">
@php
$readonly = ($post->cpr_check == 'Verified') ? 'readonly' : '';
@endphp
                            <div class="row">
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier <code>*</code></label>
                                        <input type="text" id="load_carrier" name="load_carrier" class="form-control" value="{{ $post->load_carrier }}"
                                            style="width: 100%;" autocomplete="off" placeholder="Select carrier" {{ $readonly }}>
                                        <input type="hidden"  name="carrier_id" id="carrier_id" value="{{ $post->carrier_id }}">
                                        <!-- Dropdown to show the carrier suggestions -->
                                        <ul id="carrier-list" class="list-group"
                                            style="position: absolute; z-index: 1000; width: 100%; display: none;"></ul>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>MC No <code>*</code></label>
                                        <input class="form-control" required name="load_mc_no" id="carrier_mc_ff_input"
                                            style="width: 100%;" placeholder="Enter MC Number" autocomplete="off"  value="{{ $post->load_mc_no }}" {{ $readonly }}>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>DOT No</label>
                                        <input class="form-control" name="carrier_dot" id="carrier_dot"
                                            style="width: 100%;" placeholder="Enter DOT Number" autocomplete="off" value="{{ $post->carrier_dot }}" >
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier Phone<code>*</code></label>
                                        <input type="text" id="load_carrier_phone" name="load_carrier_phone" value="{{ $post->load_carrier_phone }}"
                                            class="form-control" style="width: 100%;" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Advance Payment</label>
                                        <input type="number" class="form-control" name="load_advance_payment"  value="{{ $post->load_advance_payment }}" 
                                            autocomplete="off" style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Billing Type</label>
                                        <select class="form-control" name="load_billing_type" style="width: 100%;">
                                             <option selected="selected" value="{{ $post->load_billing_type }}">
                                                        {{ $post->load_billing_type }}</option>
                                                    <option>Factoring</option>
                                                    <option>Direct Billing</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier Rate
                                            <code>*</code></label>
                                        <input type="text" class="form-control" id="load_carrier_fee"
                                            name="load_carrier_fee" required autocomplete="off"  value="{{ $post->load_carrier_fee }}" required @if($post->cpr_check == 'Verified') readonly @endif>
                                        <span id="error_load_carrier_fee" style="color: red;font-size: 9px !important; display: none;">Only numbers
                                            and decimals allowed</span>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>FSC Rate %</label>
                                        <input type="number" name="load_billing_fsc_rate" id="load_billing_fsc_rate"
                                            class="form-control" autocomplete="off" value="{{ $post->load_billing_fsc_rate }}" style="width: 100%;" required @if($post->cpr_check == 'Verified') readonly @endif>
                                    </div>
                                </div>
                                @php
                                    $carrierCharges = json_decode($post->carrier_load_other_charge, true);
                                    @endphp
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label class="other_charge">Carrier Other Charges <i class="fa fa-plus"
                                        data-bs-toggle="modal" data-bs-target="#otherChargesModal"></i></label>
                                        <input type="text" class="form-control" style="width: 100%;"
                                            name="carrier_total_other_charge" id="carrier_total_other_charge"
                                            readonly>
                                    </div>

                                    <!-- Modal -->
                                    <div class="modal" id="otherChargesModal">
                                        <div class="modal-dialog modal-lg" style="max-width: 840px;">
                                            <div class="modal-content" id="model_content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel" style="font-size: 17px;text-align: left;margin-left: 9px;font-weight: 700;">Carrier Other Charges</h5>
                                                    <button type="button" class="close" data-bs-dismiss="modal" style="font-size: 23px;top: 30px;">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="container">
                                                        @php
                                                        // Decode the JSON data and check for errors
                                                        $carrierCharges =
                                                        json_decode($post->carrier_load_other_charge, true);
                                                        if (json_last_error() !== JSON_ERROR_NONE ||
                                                        !is_array($carrierCharges)) {
                                                        // Handle JSON error or invalid data
                                                        $carrierCharges = [];
                                                        }
                                                        @endphp
                                                        <div class="row charge-row mb-3">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>Charge Type:</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="form-group">
                                                                    <label>Amount:</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Fetched fields with values from the database -->
                                                        @foreach($carrierCharges as $index => $carrierCharge)
                                                            <div class="row charge-row mb-3">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <input type="text"
                                                                            class="form-control typeofcharge"
                                                                            placeholder="Enter Charges Type"
                                                                            name="shipper_type_charge[]"
                                                                            value="{{ htmlspecialchars($carrierCharge['type'] ?? '') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-5">
                                                                    <div class="form-group">
                                                                        <input type="text"
                                                                            class="form-control otheramount"
                                                                            placeholder="Enter Amount"
                                                                            name="shipper_other_charge[]"
                                                                            value="{{ number_format((float)($carrierCharge['amount'] ?? 0), 2, '.', '') }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-1" style="margin-top: 8px;">
                                                                    <button type="button"
                                                                        style="background:unset;border:none"
                                                                        class="remove-charge">
                                                                        <i class="fa fa-trash"
                                                                            style="margin-top: 15px; color:red;"
                                                                            aria-hidden="true"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach


                                                        <!-- Container for dynamically added fields -->
                                                        <div id="inputs"></div>
                                                    </div>

                                                    <div class="modal-footer mt-3">
                                                        <button type="button"
                                                            class="btn btn-success create-input">Add New Charges</button>
                                                    </div>
                                                </div>

                                                <!-- Hidden template row for cloning -->
                                                <div id="chargeRowTemplatecarrier" style="display:none;">
                                                    <div class="row charge-row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <input type="text"
                                                                    class="form-control typeofcharge"
                                                                    placeholder="Enter Charges Type"
                                                                    name="shipper_type_charge[]">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <div class="form-group">
                                                                <input type="text"
                                                                    class="form-control otheramount"
                                                                    placeholder="Enter Amount"
                                                                    name="shipper_other_charge[]">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1" style="margin-top: 21px;">
                                                            <button class="closebtn"
                                                                style="border: none; background: unset; color:red;">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Final Carrier Fee</label>
                                        <input class="form-control" readonly name="load_final_carrier_fee"
                                            id="load_final_carrier_fee"  value="{{ $post->load_final_carrier_fee }}" style="width: 100%;">
                                    </div>
                                </div>
                            </div>
                        </div>

                         @php
                            // Decode and initialize Shipper data with fallbacks
                            $shipperallData = json_decode($post->load_shipperr, true) ?? [];
                            $shipperQty = json_decode($post->load_shipper_qty, true) ?? [];
                            $shipperWeight = json_decode($post->load_shipper_weight, true) ?? [];
                            $shipperDescription = json_decode($post->load_shipper_discription, true) ?? [];
                            $shipperType = json_decode($post->load_shipper_commodity_type, true) ?? [];
                            $shipperNotes = json_decode($post->load_shipper_shipping_notes, true) ?? [];
                            $shipperContact = json_decode($post->load_shipper_contact, true) ?? [];
                            $shipperLocation = json_decode($post->load_shipper_location, true) ?? [];
                            $shipperAppointment = json_decode($post->load_shipper_appointment, true) ?? [];
                            $shipperCommodity = json_decode($post->load_shipper_commodity, true) ?? [];
                            $shipperValue = json_decode($post->load_shipper_value, true) ?? [];
                            $shipperPoNumber = json_decode($post->load_shipper_po_numbers, true) ?? [];
                            $shipperCounter = count($shipperallData) + 1;
                        @endphp

                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Shipper <i id="addBtn" class="fa fa-plus"></i> <a href="{{route('shipper')}}" target="_blank" style="font-size: 12px;">Add Shipper</a></h3>
                        </div>
                        <div class="card-body" id="shipperForms">
                            
							<ul class="nav nav-tabs" id="navTabs">

                              @php
                                $i = 0;
                              @endphp
                                @if(count($shipperallData) > 0)
                                @foreach ($shipperallData as $shipper)
                                @php
                                    $key = $i++;
                                @endphp
                                
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#shipperForm{{$key + 1}}" role="tab"
                                        aria-controls="shipperForm{{$key + 1}}" aria-selected="true">Shipper {{$key + 1}}
                                        <i class="fa fa-trash remove" data-id="shipperForm{{$key + 1}}" style="margin-top: 1px;margin-left: 4px;"></i>
                                    </a>
                                </li>
                                @endforeach
								@else
								@php
                                    $key = 0;
                                @endphp
                                
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#shipperForm{{$key + 1}}" role="tab"
                                        aria-controls="shipperForm{{$key + 1}}" aria-selected="true">Shipper {{$key + 1}}
                                        <i class="fa fa-trash remove" data-id="shipperForm{{$key + 1}}" style="margin-top: 1px;margin-left: 4px;"></i>
                                    </a>
                                </li>
								@endif
                            </ul>
                            <div class="tab-content" id="tabContent">
                              @php
                                $s = 0;
                              @endphp
							    @if(count($shipperallData) > 0)
                                @foreach ($shipperallData as $shipper)
                                
                                @php
                                    $key = $s++;
                                @endphp
                                <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}" id="shipperForm{{$key + 1}}" role="tabpanel"
                                    aria-labelledby="formTab1">
                                    <div class="row shipper-form">
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper <code>*</code>
                                                    <!-- <a href="" target="blank" style="background: none; border: none;">
                                                        <i class="fa fa-plus"></i>Add New
                                                    </a> -->
                                                </label>
                                                <select class="form-control load_shipper" name="load_shipper_{{ $key + 1 }}" id="load_shipper_{{ $key + 1 }}"
                                                    required autocomplete="off" style="width: 100%;" >
                                                    <option value="">Select shipper</option>
                                                    @foreach($shipperdata as $shippers)
                                                        <option value="{{$shippers->shipper_name ?? ''}}"  data-name="{{$shippers->shipper_name ?? ''}}" data-address="{{$shippers->shipper_address ?? ''}}" data-city="{{$shippers->shipper_city ?? ''}}" data-state="{{$shippers->shipper_state ?? ''}}" data-country="{{$shippers->shipper_country ?? ''}}" data-zip="{{$shippers->shipper_zip ?? ''}}"  @if($shipper['name'] == $shippers->shipper_name) selected @endif >{{$shippers->shipper_name}}</option>
                                                    @endforeach
                                                </select>
                                                <span class="customerErrorMessage"
                                                    style="color: red; display: none;">Select Shipper From the
                                                    List</span>
                                                <div id="shipperList" class="form-control" style="display: none;"
                                                    readonly></div>

                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper Location</label>
                                                <input class="form-control load_shipper_location" readonly name="load_shipper_location_{{ $key + 1 }}"  value="{{ $shipperLocation[$key]['location'] ?? '' }}" id="load_shipper_location_{{ $key + 1 }}" autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Pickup Date Appointment <code>*</code></label>
                                                <input class="form-control" required type="datetime-local" name="load_shipper_appointment_{{ $key + 1 }}" style="width: 100%;" value="{{ $shipperAppointment[$key]['appointment'] ?? '' }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <input class="form-control"  name="load_shipper_description_{{ $key + 1 }}" value="{{ $shipperDescription[$key]['description'] ?? 'NA' }}"
                                                    autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Type</label>
                                                <input class="form-control" name="load_shipper_commodity_type_{{ $key + 1 }}"
                                                    style="width: 100%;" autocomplete="off" value="{{ isset($shipperType[$key]['commodity_type']) ? $shipperType[$key]['commodity_type'] : ($shipperType[$key]['type'] ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Name <code>*</code></label>
                                                <input class="form-control" id="load_shipper_commodity_{{ $key + 1 }}"
                                                    name="load_shipper_commodity_{{ $key + 1 }}" autocomplete="off" type="text" value="{{ isset($shipperCommodity[$key]['commodity_name']) ? $shipperCommodity[$key]['commodity_name'] : ($shipperCommodity[$key]['commodity'] ?? '') }}" 
                                                    style="width: 100%;"> 
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="number" class="form-control" autocomplete="off"
                                                  id="load_shipper_qty_{{ $key + 1 }}"  name="load_shipper_qty_{{ $key + 1 }}" style="width: 100%;"  value="{{ isset($shipperQty[$key]['shipper_qty']) ? $shipperQty[$key]['shipper_qty'] : ($shipperQty[$key]['qty'] ?? '0') }}" >
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Weight (lbs)</label>
                                                <input class="form-control" type="number" autocomplete="off"
                                                    name="load_shipper_weight_{{ $key + 1 }}" id="load_shipper_weight_{{ $key + 1 }}"
                                                    style="width: 100%;"  value="{{ isset($shipperWeight[$key]['shipper_weight']) ? $shipperWeight[$key]['shipper_weight'] : ($shipperWeight[$key]['weight'] ?? '0') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Value($)<code>*</code></label>
                                                <input type="number" class="form-control" id="load_shipper_value_{{ $key + 1 }}"
                                                    autocomplete="off" name="load_shipper_value_{{ $key + 1 }}" required
                                                    style="width: 100%;" value="{{ isset($shipperValue[$key]['shipper_value']) ? $shipperValue[$key]['shipper_value'] : ($shipperValue[$key]['value'] ?? '0') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipping Notes</label>
                                                <input class="form-control" name="load_shipper_shipping_notes_{{ $key + 1 }}"
                                                    autocomplete="off" style="width: 100%;" value="{{ $shipperNotes[$key]['shipping_notes'] ?? 'NA' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>PO Numbers</label>
                                                <input class="form-control" name="load_shipper_po_numbers_{{ $key + 1 }}"
                                                    autocomplete="off" style="width: 100%;"  value="{{ isset($shipperPoNumber[$key]['shipping_po_numbers']) ? $shipperPoNumber[$key]['shipping_po_numbers'] : $shipperPoNumber[$key]['po_number'] ?? '0' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Contact Number</label>
                                                <input class="form-control" type="number" autocomplete="off" name="load_shipper_contact_{{ $key + 1 }}" value="{{ $shipperContact[$key]['shipping_contact'] ?? $shipperContact[$key]['contact'] ?? '0' }}" style="width: 100%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
								@else
								@php
                                    $key = 0;
                                @endphp
                                <div class="tab-pane fade {{ $key == 0 ? 'show active' : '' }}" id="shipperForm{{$key + 1}}" role="tabpanel"
                                    aria-labelledby="formTab1">
                                    <div class="row shipper-form">
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper <code>*</code>
                                                   
                                                </label>
                                                <select class="form-control load_shipper" name="load_shipper_{{ $key + 1 }}" id="load_shipper_{{ $key + 1 }}"
                                                    required autocomplete="off" style="width: 100%;" >
                                                    <option value="">Select shipper</option>
                                                    @foreach($shipperdata as $shippers)
                                                        <option value="{{$shippers->shipper_name ?? ''}}"  data-name="{{$shippers->shipper_name ?? ''}}" data-address="{{$shippers->shipper_address ?? ''}}" data-city="{{$shippers->shipper_city ?? ''}}" data-state="{{$shippers->shipper_state ?? ''}}" data-country="{{$shippers->shipper_country ?? ''}}" data-zip="{{$shippers->shipper_zip ?? ''}}">{{$shippers->shipper_name}}</option>
                                                    @endforeach
                                                </select>
                                                <span class="customerErrorMessage"
                                                    style="color: red; display: none;">Select Shipper From the
                                                    List</span>
                                                <div id="shipperList" class="form-control" style="display: none;"
                                                    readonly></div>

                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper Location</label>
                                                <input class="form-control load_shipper_location" readonly name="load_shipper_location_{{ $key + 1 }}"  value="{{ $shipperLocation[$key]['location'] ?? '' }}" id="load_shipper_location_{{ $key + 1 }}" autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label> Appointment</label>
                                                <input class="form-control" type="datetime-local"
                                                    name="load_shipper_appointment_{{ $key + 1 }}" style="width: 100%;" value="{{ $shipperAppointment[$key]['appointment'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <input class="form-control"  name="load_shipper_description_{{ $key + 1 }}" value="{{ $shipperDescription[$key]['description'] ?? 'NA' }}"
                                                    autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Type</label>
                                                <input class="form-control" name="load_shipper_commodity_type_{{ $key + 1 }}"
                                                    style="width: 100%;" autocomplete="off" value="{{ isset($shipperType[$key]['commodity_type']) ? $shipperType[$key]['commodity_type'] : ($shipperType[$key]['type'] ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Name <code>*</code></label>
                                                <input class="form-control" id="load_shipper_commodity_{{ $key + 1 }}"
                                                    name="load_shipper_commodity_{{ $key + 1 }}" autocomplete="off" type="text" value="{{ isset($shipperCommodity[$key]['commodity_name']) ? $shipperCommodity[$key]['commodity_name'] : ($shipperCommodity[$key]['commodity'] ?? '') }}" 
                                                    style="width: 100%;"> 
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="number" class="form-control" autocomplete="off"
                                                  id="load_shipper_qty_{{ $key + 1 }}"  name="load_shipper_qty_{{ $key + 1 }}" style="width: 100%;"  value="{{ isset($shipperQty[$key]['shipper_qty']) ? $shipperQty[$key]['shipper_qty'] : ($shipperQty[$key]['qty'] ?? '0') }}" >
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Weight (lbs)</label>
                                                <input class="form-control" type="number" autocomplete="off"
                                                    name="load_shipper_weight_{{ $key + 1 }}" id="load_shipper_weight_{{ $key + 1 }}"
                                                    style="width: 100%;"  value="{{ isset($shipperWeight[$key]['shipper_weight']) ? $shipperWeight[$key]['shipper_weight'] : ($shipperWeight[$key]['weight'] ?? '0') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Value($)<code>*</code></label>
                                                <input type="number" class="form-control" id="load_shipper_value_{{ $key + 1 }}"
                                                    autocomplete="off" name="load_shipper_value_{{ $key + 1 }}" required
                                                    style="width: 100%;" value="{{ isset($shipperValue[$key]['shipper_value']) ? $shipperValue[$key]['shipper_value'] : ($shipperValue[$key]['value'] ?? '0') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipping Notes</label>
                                                <input class="form-control" name="load_shipper_shipping_notes_{{ $key + 1 }}"
                                                    autocomplete="off" style="width: 100%;" value="{{ $shipperNotes[$key]['shipping_notes'] ?? 'NA' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>PO Numbers</label>
                                                <input class="form-control" name="load_shipper_po_numbers_{{ $key + 1 }}"
                                                    autocomplete="off" style="width: 100%;"  value="{{ isset($shipperPoNumber[$key]['shipping_po_numbers']) ? $shipperPoNumber[$key]['shipping_po_numbers'] : $shipperPoNumber[$key]['po_number'] ?? '0' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Contact Number</label>
                                                <input class="form-control" type="number" autocomplete="off" name="load_shipper_contact_{{ $key + 1 }}" value="{{ $shipperContact[$key]['shipping_contact'] ?? $shipperContact[$key]['contact'] ?? '0' }}" style="width: 100%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               
								@endif
                            </div>
                        </div>

                        @php
                            $counter = 1; // Start counter from 1
                            $consigneeallData = json_decode($post->load_consignee, true) ?? [];
                            $consigneeQty = json_decode($post->load_consignee_qty, true) ?? [];
                            $consigneeWeight = json_decode($post->load_consignee_weight, true) ?? [];
                            $consigneeDescription = json_decode($post->load_consignee_discription, true) ?? [];
                            $consigneeType = json_decode($post->load_consignee_type, true) ?? [];
                            $consigneeNotes = json_decode($post->load_consigneer_notes, true) ?? [];
                            $consigneeLocation = json_decode($post->load_consignee_location, true) ?? [];
                            $consigneeAppointment = json_decode($post->load_consignee_appointment, true) ?? [];
                            $consigneeCommodity = json_decode($post->load_consignee_commodity, true) ?? [];
                            $consigneeValue = json_decode($post->load_consignee_value, true) ?? [];
                            $consigneePoNumber = json_decode($post->load_consignee_po_numbers, true) ?? [];
                            $consigneeContact = json_decode($post->load_consignee_contact, true) ?? [];
                            $consigneeCounter = count($consigneeallData);
                        @endphp
                        <div class="card-header pb-0">
                            
                                <h3 class="card-title"
                                    style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                    Consignee <i id="addBtnconsignee" class="fa fa-plus"></i> <a href="{{route('Consignee')}}" target="_blank" style="font-size: 12px;">Add Consignee</a>
                                </h3>
                            
                        </div>
                        <div class="card-body1" id="consigneeSections">
                            <ul class="nav nav-tabs" id="navTabs1">
                                @php
                                $a = 0;
                              @endphp
							  @if(count($consigneeallData) > 0)
                                @foreach ($consigneeallData as $key => $consignee)
                               @php
                                    $key = $a++;
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#consigneeSections{{$key + 1}}" role="tab"
                                        aria-controls="consigneeSections{{$key + 1}}" aria-selected="true">Consignee {{$key +1}}
                                    <i class="fa fa-trash remove" data-id="consigneeSections{{$key + 1}}" style="margin-top: 1px;margin-left: 4px;"></i>
                                    </a>
                                </li>
                                @endforeach
								@else
								@php
                                    $key = 0;
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#consigneeSections{{$key + 1}}" role="tab"
                                        aria-controls="consigneeSections{{$key + 1}}" aria-selected="true">Consignee {{$key +1}}
                                    <i class="fa fa-trash remove" data-id="consigneeSections{{$key + 1}}" style="margin-top: 1px;margin-left: 4px;"></i>
                                    </a>
                                </li>
								@endif
                            </ul>
                            <div class="tab-content" id="tabContent1">
                              @php
                                $j = 0;
                              @endphp
							  @if(count($consigneeallData) > 0)
                                 @foreach ($consigneeallData as $key => $consignee)
                                 @php
								
                                    $key = $j++;
									
                                @endphp
								
                                <div class="tab-pane fade show {{ $key == 0 ? 'active' : '' }}" id="consigneeSections{{$key + 1}}" role="tabpanel"
                                    aria-labelledby="formTab1">

	<div class="consignee-section mt-3">
		<div class="row">
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee <code>*</code>
					  
					</label>
					<select class="form-control load_consignee" name="load_consignee_{{ $key + 1 }}" autocomplete="off"
						id="load_consignee_{{ $key + 1 }}" required style="width: 100%;">
						<option value="">Select Consignee</option>
						@foreach($consigneedata as $consignees)
						<option value="{{$consignees->consignee_name ?? ''}}" data-name="{{$consignees->consignee_name ?? ''}}" data-address="{{$consignees->consignee_address ?? ''}}" data-city="{{$consignees->consignee_city ?? ''}}" data-state="{{$consignees->consignee_state ?? ''}}" data-country="{{$consignees->consignee_country ?? ''}}" data-zip="{{$consignees->consignee_zip ?? ''}}" @if($consignee['name'] == $consignees->consignee_name) selected @endif >{{$consignees->consignee_name}}</option>
						@endforeach
					</select>
					<span class="customerErrorMessage"
						style="color: red; display: none;">Select Consignee From the
						List</span>
					<div id="consigneeList" class="form-control" style="display: none;"
						readonly></div>

				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee Location</label>
					<input class="form-control load_consignee_location" name="load_consignee_location_{{ $key + 1 }}"
						autocomplete="off" id="load_consignee_location_{{ $key + 1 }}" value="{{ $consigneeLocation[$key]['location'] ?? '' }}"
						style="width: 100%;" readonly>
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Delivery Date Appointment <code>*</code></label>
					<input class="form-control" type="datetime-local" value="{{ $consigneeAppointment[$key]['appointment'] ?? '' }}"
						name="load_consignee_appointment_{{ $key + 1 }}" style="width: 100%;" required>
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Description</label>
					<input class="form-control" autocomplete="off"
						name="load_consignee_description_{{ $key + 1 }}" style="width: 100%;" value="{{ $consigneeDescription[$key]['description'] ?? '' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Commodity Type </label>
					<input class="form-control" name="load_consignee_type_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;" value="{{ $consigneeType[$key]['consignee_type'] ?? '' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Commodity Name <code>*</code></label>
					<input class="form-control" name="load_consignee_commodity_{{ $key + 1 }}"
						id="load_consignee_commodity_{{ $key + 1 }}" value="{{ $consigneeCommodity[$key]['consignee_commodity'] ?? '' }}"  autocomplete="off" type="text"
						style="width: 100%;">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Qty</label>
					<input type="number" class="form-control" name="load_consignee_qty_{{ $key + 1 }}" id="load_consignee_qty_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;" value="{{ $consigneeQty[$key]['consignee_qty'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Weight (lbs)</label>
					<input class="form-control" type="number" id="load_consignee_weight_{{ $key + 1 }}"
						autocomplete="off" name="load_consignee_weight_{{ $key + 1 }}"
						style="width: 100%;"  value="{{ $consigneeWeight[$key]['consignee_weight'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Value($)<code>*</code></label>
					<input type="number" class="form-control"
						name="load_consignee_value_{{ $key + 1 }}" autocomplete="off"
						id="load_consignee_value_{{ $key + 1 }}" required style="width: 100%;" value="{{ $consigneeValue[$key]['consignee_value'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee
						Notes</label>
					<textarea class="form-control" name="load_consignee_notes_{{ $key + 1 }}"
						autocomplete="off"
						style="width: 100%; height: 31px !important;font-size: 12px;" value="{{ $consigneeNotes[$key]['load_consignee_notes'] ?? '' }}">{{ $consigneeNotes[$key]['load_consignee_notes'] ?? '' }}</textarea>
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>PO Numbers</label>
					<input class="form-control" name="load_consignee_po_numbers_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;"  value="{{ $consigneePoNumber[$key]['consignee_po_number'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Contact Number</label>
					<input class="form-control" type="number" autocomplete="off"
						name="load_consignee_contact_{{ $key + 1 }}" style="width: 100%;" value="{{ $consigneeContact[$key]['consignee_contact'] ?? '0' }}">
				</div>
			</div>
		</div>
	</div>
</div>
@endforeach
@else
								@php
                                    $key = 0;	
                                @endphp
								
                                <div class="tab-pane fade show {{ $key == 0 ? 'active' : '' }}" id="consigneeSections{{$key + 1}}" role="tabpanel"
                                    aria-labelledby="formTab1">

	<div class="consignee-section mt-3">
		<div class="row">
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee <code>*</code>
					  
					</label>
					<select class="form-control load_consignee" name="load_consignee_{{ $key + 1 }}" autocomplete="off"
						id="load_consignee_{{ $key + 1 }}" required style="width: 100%;">
						<option value="">Select Consignee</option>
						@foreach($consigneedata as $consignees)
						<option value="{{$consignees->consignee_name ?? ''}}" data-name="{{$consignees->consignee_name ?? ''}}" data-address="{{$consignees->consignee_address ?? ''}}" data-city="{{$consignees->consignee_city ?? ''}}" data-state="{{$consignees->consignee_state ?? ''}}" data-country="{{$consignees->consignee_country ?? ''}}" data-zip="{{$consignees->consignee_zip ?? ''}}" >{{$consignees->consignee_name}}</option>
						@endforeach
					</select>
					<span class="customerErrorMessage"
						style="color: red; display: none;">Select Consignee From the
						List</span>
					<div id="consigneeList" class="form-control" style="display: none;"
						readonly></div>

				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee Location</label>
					<input class="form-control load_consignee_location" name="load_consignee_location_{{ $key + 1 }}"
						autocomplete="off" id="load_consignee_location_{{ $key + 1 }}" value="{{ $consigneeLocation[$key]['location'] ?? '' }}"
						style="width: 100%;" readonly>
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Appointment</label>
					<input class="form-control" type="datetime-local" value="{{ $consigneeAppointment[$key]['appointment'] ?? '' }}"
						name="load_consignee_appointment_{{ $key + 1 }}" style="width: 100%;">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Description</label>
					<input class="form-control" autocomplete="off"
						name="load_consignee_description_{{ $key + 1 }}" style="width: 100%;" value="{{ $consigneeDescription[$key]['description'] ?? '' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Commodity Type </label>
					<input class="form-control" name="load_consignee_type_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;" value="{{ $consigneeType[$key]['consignee_type'] ?? '' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Commodity Name <code>*</code></label>
					<input class="form-control" name="load_consignee_commodity_{{ $key + 1 }}"
						id="load_consignee_commodity_{{ $key + 1 }}" value="{{ $consigneeCommodity[$key]['consignee_commodity'] ?? '' }}"  autocomplete="off" type="text"
						style="width: 100%;">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Qty</label>
					<input type="number" class="form-control" name="load_consignee_qty_{{ $key + 1 }}" id="load_consignee_qty_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;" value="{{ $consigneeQty[$key]['consignee_qty'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Weight (lbs)</label>
					<input class="form-control" type="number" id="load_consignee_weight_{{ $key + 1 }}"
						autocomplete="off" name="load_consignee_weight_{{ $key + 1 }}"
						style="width: 100%;"  value="{{ $consigneeWeight[$key]['consignee_weight'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Value($)<code>*</code></label>
					<input type="number" class="form-control"
						name="load_consignee_value_{{ $key + 1 }}" autocomplete="off"
						id="load_consignee_value_{{ $key + 1 }}" required style="width: 100%;" value="{{ $consigneeValue[$key]['consignee_value'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Consignee
						Notes</label>
					<textarea class="form-control" name="load_consignee_notes_{{ $key + 1 }}"
						autocomplete="off"
						style="width: 100%; height: 31px !important;font-size: 12px;" value="{{ $consigneeNotes[$key]['load_consignee_notes'] ?? '' }}">{{ $consigneeNotes[$key]['load_consignee_notes'] ?? '' }}</textarea>
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>PO Numbers</label>
					<input class="form-control" name="load_consignee_po_numbers_{{ $key + 1 }}"
						autocomplete="off" style="width: 100%;"  value="{{ $consigneePoNumber[$key]['consignee_po_number'] ?? '0' }}">
				</div>
			</div>
			<div class="col-md-2 mb-2">
				<div class="form-group">
					<label>Contact Number</label>
					<input class="form-control" type="number" autocomplete="off"
						name="load_consignee_contact_{{ $key + 1 }}" style="width: 100%;" value="{{ $consigneeContact[$key]['consignee_contact'] ?? '0' }}">
				</div>
			</div>
		</div>
	</div>
</div>

@endif
</div>

                        </div>
						<a class="upload btn btn-warning" href="{{route('files.upload', $post->id)}}" target="blank"><i class="fa fa-upload dynamic-data" aria-hidden="true" style="margin:0 10px; font-size: 20px;"></i>Upload</a>
                        <!-- BOL Button -->
<a class="upload btn btn-warning" data-bs-toggle="modal" data-bs-target="#bolModal">
    <i class="fa fa-bold" aria-hidden="true" style="margin:0 10px; font-size:20px;"></i>
    Bill Of Lading
</a>

<!-- Modal -->
<div class="modal fade" id="bolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title">Bill Of Lading</h5>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="downloadBOL()">
                        Download PDF
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="enableEdit()">
                        Edit
                    </button>

                    <button type="button" class="btn btn-success d-none" id="saveBolBtn" onclick="saveBOL()">
                        Save
                    </button>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- PDF Area -->
                <div id="bolDownloadArea" class="p-4 bg-white">

                    <!-- Top -->
                    <div class="row mb-4">

<div class="col-md-8">

                               @php
                                    $logoPath = public_path('images/cargo.png');

                                    if (file_exists($logoPath)) {
                                        $logoBase64 = base64_encode(file_get_contents($logoPath));
                                    } else {
                                        $logoBase64 = '';
                                        // Optional: dd($logoPath); // Check the resolved path
                                    }
                                @endphp

    <div style="
        display:flex;
        align-items:flex-start;
        gap:15px;
    ">

        <!-- Logo -->
        <div>
            <img 
                src="data:image/png;base64,{{ $logoBase64 }}"
                alt="logo"
                style="
                    width:80px;
                    display:block;
                "
            >
        </div>

        <!-- Content -->
        <div style="line-height:1.5;">

            <h3 style="
                margin:0 0 4px 0;
                font-size:24px;
                font-weight:700;
                letter-spacing:0.5px;
            ">
                CARGO CONVOY INC
            </h3>

            <div style="
                font-size:13px;
                color:#333;
            ">
                7119 PENNSYLVANIA AVE,<br>
                Upper Darby, PA, USA 19082
            </div>

            <div style="
                margin-top:4px;
                font-size:13px;
                font-weight:500;
            ">
                Phone: +1 (267) 513-0604
            </div>

        </div>

    </div>

</div>

                        <div class="col-md-4">

                            <table class="table table-bordered">
                                <tr>
                                    <th>Load Number</th>
                                    <td>
                                        <input class="editable-field border-0 w-100"
                                            data-field="load_number"
                                            value="{{ $post->load_number }}" style="font-weight: 900; color: #555;"
                                            readonly>
                                    </td>
                                </tr>

                                <tr>
                                    <th>BOL Number</th>
                                    <td>
                                        <input class="editable-field border-0 w-100"
                                            data-field="bol_number"
                                            value="{{ $post->load_workorder ?? '' }}" style="font-weight: 900; color: #555;"
                                            readonly>
                                    </td>
                                </tr>

                                <tr>
                                    <th>Ship Date</th>
                                        @php
                                            $shipper_appointment =
                                            json_decode($post->load_shipper_appointment,true);
                                        @endphp
                                    <td>
                                        <input class="editable-field border-0 w-100"
                                            value="{{ isset($shipper_appointment[0]['appointment']) ? \Carbon\Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '' }}"
                                            readonly style="font-weight: 900; color: #555;">
                                    </td>
                                </tr>

                                <tr>
                                    <th>Delivery Date</th>
                                    <td>
                                    @php
                                            $consignee_appointment =
                                            json_decode($post->load_consignee_appointment,true);
                                    @endphp
@php
$formattedDate = '-';

if ($consignee_appointment && isset($consignee_appointment[0]['appointment'])) {

    try {
        $formattedDate = \Carbon\Carbon::parse(
            $consignee_appointment[0]['appointment']
        )->format('m-d-Y');

    } catch (\Exception $e) {
        $formattedDate = '-';
    }
}
@endphp

<input class="editable-field border-0 w-100"
    value="{{ $formattedDate }}" style="font-weight: 900; color: #555;"
    readonly>
                                    </td>
                                </tr>
                            </table>

                        </div>

                    </div>

                    <!-- Shipper & Consignee -->
                    <div class="row mb-4">

                        <div class="col-md-6">
                            <div class="border p-3 h-100">
                                <h6 class="fw-bold">Shipper</h6>
@php
    $shippers = json_decode($post->load_shipperr, true);

    $shipperText = '';

    if($shippers && is_array($shippers)) {

        foreach($shippers as $item) {

            $shipperText .= ($item['name'] ?? '') . "\n";

            if(!empty($item['location'])) {
                $shipperText .= $item['location'] . "\n";
            }

            $shipperText .= "\n";
        }
    }
@endphp

<textarea class="form-control editable-field border-0"
    data-field="shipper"
    rows="6"
    readonly>{{ trim($shipperText) }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border p-3 h-100">
                                <h6 class="fw-bold">Consignee</h6>
@php
    $consignees = json_decode($post->load_consignee, true);

    $consigneeText = '';

    if($consignees && is_array($consignees)) {

        foreach($consignees as $item) {

            $consigneeText .= ($item['name'] ?? '') . "\n";

            if(!empty($item['location'])) {
                $consigneeText .= $item['location'] . "\n";
            }

            $consigneeText .= "\n";
        }
    }
@endphp
                                <textarea class="form-control editable-field border-0"
                                    data-field="consignee"
                                    rows="5"
                                    readonly>{{ trim($consigneeText) }}</textarea>
                            </div>
                        </div>

                    </div>


                                        <div class="row mb-4">

                        <div class="col-md-6">
                            <div class="border p-3 h-100">
                                <h6 class="fw-bold">3rd Party Billing</h6>

                                <textarea class="form-control editable-field border-0"
                                    rows="5"
                                    readonly></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border p-3 h-100">
                                <h6 class="fw-bold">Transportation Company</h6>

                                <textarea class="form-control editable-field border-0"
                                rows="5"
                                data-field="carrier_name"
                                readonly>{{ "MC #: " . ($post->load_mc_no ?? '') . "\n\nCarrier Name: " . ($post->load_carrier ?? '') }}</textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Freight -->
                    <div class="mb-4">

<table class="table table-bordered">

    <thead class="table-grey">
        <tr>
            <th># of pieces</th>
            <th>Description of the goods, marks, exceptions</th>
            <th>Weight in LBS.</th>
            <th>Type</th>
            <th>NMFC</th>
            <th>HM</th>
            <th>Class</th>
        </tr>
    </thead>

    <tbody id="freightTableBody">

        <tr>

            <td>
                <input type="text"
                    class="form-control editable-field unit-number"
                    value="#Unit 1">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="Description">
            </td>

            <td>
                <input type="number"
                    class="form-control editable-field weight-field"
                    placeholder="Weight"
                    onkeyup="updateTotals()"
                    onchange="updateTotals()">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="Type">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="NMFC">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="HM">
            </td>

            <td>

                <div class="d-flex gap-2">

                    <input type="text"
                        class="form-control editable-field"
                        placeholder="Class">

                    <button type="button"
                        class="btn btn-danger btn-sm pdf-hide"
                        onclick="removeRow(this)">
                        ×
                    </button>

                </div>

            </td>

        </tr>

    </tbody>

    <!-- Footer -->
    <tfoot>

        <tr>

            <td class="text-center">
                <strong>Total Pieces</strong><br>

                <span id="totalPieces">1</span>
            </td>

            <td colspan="2" class="text-center">

                <strong>Total Weight</strong><br>

                <span id="totalWeight">0.00</span>

            </td>

            <td colspan="4" class="text-center">

                <strong>Emergency Response Phone</strong><br>

                24/7 Dispatch Support

            </td>

        </tr>

    </tfoot>

</table>
                        <div class="mt-3">
<button type="button"
    class="btn btn-success pdf-hide"
    onclick="addFreightRow()">
    + Add Unit
</button>
</div>

                    </div>

                    <!-- Notes -->
<table class="table table-bordered mb-3">

    <tr>

        <!-- Left Side -->
        <td style="width:70%; vertical-align:top;">

            <h6 class="fw-bold mb-2">Notes:</h6>

            <textarea class="form-control editable-field border-0"
                rows="7"
                readonly>{{ $post->notes ?? '' }}</textarea>

        </td>

        <!-- Right Side -->
        <td style="width:30%; padding:0;">

            <table class="table table-bordered mb-0 h-100">

                <tr>
                    <td>
                        <strong>C.O.D. Amount:</strong> <input type="text"
                class="form-control editable-field border-0" value="$0.00"
                readonly>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>C.O.D. Fee:</strong> <input type="text"
                class="form-control editable-field border-0" value="Collect"
                readonly>
                    </td>
                </tr>

                <tr>
                    <td>
                        <strong>Declared Value:</strong> <input type="text"
                class="form-control editable-field border-0" value=" $0.00"
                readonly>
                    </td>
                </tr>

                <tr>
                    <td style="font-size:11px;">
                        If at consignor's risk, write or stamp here
                    </td>
                </tr>

            </table>

        </td>

    </tr>

</table>

<table class="table table-bordered mb-3">

    <!-- Row 1 -->
    <tr>

        <th style="width:20%;">
            <strong>Shipper</strong>
        </th>

        <th style="width:20%;">
            <strong>Carrier</strong>
        </th>

        <th style="width:20%;">
            <strong>Date</strong>
        </th>

        <!-- Full Blank Column -->
        <th rowspan="4" style="width:40%; vertical-align:top;">
            <strong>Number Of Pieces Received</strong>
        </th>

    </tr>

    <!-- Row 2 -->
    <tr>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

    </tr>

    <!-- Row 3 -->
    <tr>

        <th>
            <strong>Per</strong>
        </th>

        <th>
            <strong>Per</strong>
        </th>

        <th>
            <strong>Time</strong>
        </th>

    </tr>

    <!-- Row 4 -->
    <tr>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

        <td>
            <input type="text"
                class="form-control editable-field border-0"
                readonly>
        </td>

    </tr>

</table>

<table class="table table-bordered mb-3">

<tr>
    <th>Consignee Name</th>
    <th>Date</th>
    <th>Signature</th>
    <th>Number Of Pieces Received</th>
</tr>

<tr>
    <td>
        <input type="text"
            class="form-control editable-field border-0"
            readonly>
    </td>
        <td>
        <input type="text"
            class="form-control editable-field border-0"
            readonly>
    </td>
        <td>
        <input type="text"
            class="form-control editable-field border-0"
            readonly>
    </td>
        <td>
        <input type="text"
            class="form-control editable-field border-0"
            readonly>
    </td>
</tr>

</table>


                </div>

            </div>

        </div>
    </div>
</div>
                        <input type="submit" class="btn btn-info" value="update Load">
                       
                    </form>

                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->

        @endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

function initChargeRowValidation() {

    $(document).on("input", "input[name^='shipperchargeType']", function () {
        let row = $(this).closest(".row");
        let amountField = row.find(".shipperchargeAmount");

        if ($(this).val().trim() !== "") {
            amountField.prop("disabled", false);
            row.find(".type-warning").remove(); // remove warning if exists
        } else {
            amountField.prop("disabled", true).val("");
        }
    });

    // When user focuses or tries to type in Amount
    $(document).on("input", ".shipperchargeAmount", function () {
        let row = $(this).closest(".row");
        let typeField = row.find("input[name^='shipperchargeType']");

        if (typeField.val().trim() === "") {
			
            // show warning only once
            if (row.find(".type-warning").length === 0) {
                row.find(".col-md-6").append(
                    '<small class="text-danger type-warning">First enter the type</small>'
                );
				
            }
			$(this).val('');
        }
    });

}

// Call once on page load
$(document).ready(function () {
    initChargeRowValidation();
});

function initOtherChargeValidation() {

    // When user types in Charge Type
    $(document).on("input", ".typeofcharge", function () {

        let row = $(this).closest(".charge-row");
        let amountField = row.find(".otheramount");

        if ($(this).val().trim() !== "") {
            amountField.prop("disabled", false);
            row.find(".type-warning").remove();
        } else {
            amountField.prop("disabled", true).val("");
        }
    });

    // When user types OR focuses in Amount field without entering Type
    $(document).on("input", ".otheramount", function () {

        let row = $(this).closest(".charge-row");
        let typeField = row.find(".typeofcharge");

        if (typeField.val().trim() === "") {

            if (row.find(".type-warning").length === 0) {
                row.find(".col-md-6").append(
                    '<small class="text-danger type-warning">First enter the type</small>'
                );
            }

            $(this).val("");
            //$(this).prop("disabled", true);
        }
    });

    // Disable amount fields initially
    //$(".otheramount").prop("disabled", true);
}

$(document).ready(function () {
    initOtherChargeValidation();
});


/*****************shipper************************/
    $(document).ready(function () {
    $('#addBtn').on('click', function (e) {
        e.preventDefault();

        const liCount = $('#navTabs li').length;
        const nextcount = liCount + 1;

        // 1. Add new tab
        const newTab = `
            <li class="nav-item" role="presentation">
                <a class="nav-link" style="padding: 1px 11px; border-radius: 10px;" 
                   id="formTab${nextcount}" data-bs-toggle="tab" href="#shipperForm${nextcount}" 
                   role="tab" aria-controls="shipperForm${nextcount}" aria-selected="false">
                   Shipper ${nextcount}
                   <i class="fa fa-trash remove" data-id="shipperForm${nextcount}" style="margin-top: 1px;margin-left: 4px;"></i>
                </a>
                
            </li>
        `;
        $('#navTabs').append(newTab);

        // 2. Clone form and update
        const newContent = $('#shipperForm1').clone();
        newContent
            .attr({
                id: `shipperForm${nextcount}`,
                'aria-labelledby': `formTab${nextcount}`,
                'data-count': `_${nextcount}`
            })
            .removeClass('show active');

        // Update input/select/textarea IDs and names
        newContent.find('input, select, textarea, div[id]').each(function () {
            let name = $(this).attr('name');
            let id = $(this).attr('id');
			if (name) {
				let updatedName = name.replace(/_\d+$/, `_${nextcount}`);
				$(this).attr('name', updatedName).val('');
			}
			
			if (id) {
				let updatedName = id.replace(/_\d+$/, `_${nextcount}`);
				$(this).attr('id', updatedName).val('');
			}
        });

        $('#tabContent').append(newContent);
    });

    // 🔁 Delegated event for dynamic .load_shipper change
    $(document).on('change', '.load_shipper', function () {
        const parentForm = $(this).closest('.tab-pane'); // more robust
        const selectedOption = $(this).find('option:selected');
        const selectedOptionval = $(this).find('option:selected').val();
        const name = selectedOption.data('name');
        const address = selectedOption.data('address');
        const city = selectedOption.data('city');
        const state = selectedOption.data('state');
        const country = selectedOption.data('country');
        const zip = selectedOption.data('zip');

        const fullAddress = `${address}, ${city}, ${state}, ${zip}, ${country}`;

        if(selectedOptionval !== ''){
            parentForm.find('.load_shipper_location').val(fullAddress);
        }else{
            parentForm.find('.load_shipper_location').val('');
        }
    });
});



        /*****************consignee************************/
        $(document).ready(function () {
            $('#addBtnconsignee').on('click', function (e) {
                e.preventDefault();

                const liCount = $('#navTabs1 li').length;
                const nextcounts = liCount + 1;

                // 1. Add new tab
                const newTab = `
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" style="padding: 1px 11px; border-radius: 10px;" 
                            id="formTab${nextcounts}" data-bs-toggle="tab" href="#consigneeSections${nextcounts}" 
                            role="tab" aria-controls="consigneeSections${nextcounts}" aria-selected="false">
                            Consignee ${nextcounts}
                            <i class="fa fa-trash remove" data-id="consigneeSections${nextcounts}" style="margin-top: 1px;margin-left: 4px;"></i>
                        </a>
                    </li>`;
                $('#navTabs1').append(newTab);

                // 2. Clone first form
                const newContent = $('#consigneeSections1').clone();
                newContent
                    .attr({
                        id: `consigneeSections${nextcounts}`,
                        'aria-labelledby': `formTab${nextcounts}`,
                        'data-count': `_${nextcounts}`
                    })
                    .removeClass('show active'); // hide by default

                // 3. Update fields inside the clone
                newContent.find('input, select, textarea, div[id]').each(function () {
                    let name = $(this).attr('name');
                    let id = $(this).attr('id');
					if (name) {
						let updatedName = name.replace(/_\d+$/, `_${nextcounts}`);
						$(this).attr('name', updatedName).val('');
					}
					if (id) {
						let updatedName = id.replace(/_\d+$/, `_${nextcounts}`);
						$(this).attr('id', updatedName).val('');
					}
                   
                });

                $('#tabContent1').append(newContent);
            });

            // ✅ Delegate change event for dynamically added `.load_consignee`
            $(document).on('change', '.load_consignee', function () {
                const parentForm = $(this).closest('.tab-pane');
                const selectedOption = $(this).find('option:selected');
                const selectedOptionval = $(this).find('option:selected').val();
                const name = selectedOption.data('name');
                const address = selectedOption.data('address');
                const city = selectedOption.data('city');
                const state = selectedOption.data('state');
                const country = selectedOption.data('country');
                const zip = selectedOption.data('zip');

                const fullAddress = `${address}, ${city}, ${state}, ${zip}, ${country}`;
                
                if(selectedOptionval !== ''){
                    parentForm.find('.load_consignee_location').val(fullAddress);
                }else{
                    parentForm.find('.load_consignee_location').val('');
                }
            });
        });

$(document).ready(function () {
    setInterval(() => {
        $('.nav-item .remove').on('click', function() {
            var $parentLi = $(this).closest('li');
            var removeId = $(this).data('id');
            
            $parentLi.remove(); // Remove the parent <li>
            $('#' + removeId).remove(); // Remove the element with ID from data-id
        });
    }, 500);
    
});

</script>
<script>

let currentInvoiceLimit = {{ (float) ($loadCustomer->invoice_credit_limit ?? 0) }};
let currentRemainingLimit = {{ (float) ($loadCustomer->remaining_credit ?? 0) }};
const oldInvoiceChargeTotal = {{ (float) $invoicechargestotal }};
const oldFinalRate = {{ (float) ($post->shipper_load_final_rate ?? 0) }};
const oldRemainingUsed = oldFinalRate - oldInvoiceChargeTotal;

    $(document).ready(function () {
        function showCreditLimitError(message) {
            $('#mc-error-message').text(message).stop(true, true).fadeIn();
            $('#creditlimitcheck').html('<small style="color: #dc3545; font-weight: 600;">' + message + '</small>');
        }

        function clearCreditLimitError() {
            $('#mc-error-message').text('').stop(true, true).fadeOut();
        }

        function validateCustomerChargeLimits(source) {
            let invoiceTotal = 0;
            let totalCharges = 0;

            $('.shipperchargeAmount').each(function () {
                const amount = parseFloat($(this).val()) || 0;
                totalCharges += amount;
                if ($(this).closest('.row').find('.for_invoice').is(':checked')) {
                    invoiceTotal += amount;
                }
            });

            const availableForEditedLoad = Math.max(0, currentInvoiceLimit + oldInvoiceChargeTotal);
            const availableRemainingForEditedLoad = Math.max(0, currentRemainingLimit + oldRemainingUsed);
            const loadShipperRate = parseFloat($('#load_shipper_rate').val()) || 0;
            const loadFscAmount = (parseFloat($('#load_fsc_rate').val()) || 0) * loadShipperRate / 100;
            const newRemainingUsed = loadShipperRate + loadFscAmount + totalCharges - invoiceTotal;

            if (invoiceTotal > availableForEditedLoad + 0.001) {
                const $source = $(source);
                if ($source.hasClass('for_invoice')) {
                    $source.prop('checked', false);
                    $source.closest('.row').find('.shipperchargeAmount').val('0').trigger('input');
                } else if ($source.hasClass('shipperchargeAmount')) {
                    $source.val('0');
                    $source.trigger('input');
                }

                showCreditLimitError(
                    'Insufficient invoice credit limit. Available invoice limit: $' + availableForEditedLoad.toFixed(2)
                );
                return false;
            }

            if (newRemainingUsed > availableRemainingForEditedLoad + 0.001) {
                const $source = $(source);
                if ($source.hasClass('shipperchargeAmount') && !$source.closest('.row').find('.for_invoice').is(':checked')) {
                    $source.val('0');
                    $source.trigger('input');
                }

                showCreditLimitError(
                    'Insufficient remaining credit limit. Available remaining limit: $' + availableRemainingForEditedLoad.toFixed(2)
                );
                return false;
            }

            clearCreditLimitError();
            return true;
        }

        $(document).on('input change', '.shipperchargeAmount, .for_invoice', function () {
            validateCustomerChargeLimits(this);
        });

        $('#myFormLoad').on('submit', function (event) {
            if (!validateCustomerChargeLimits(null)) {
                event.preventDefault();
            }
        });

        $('#shipper_load_final_rate').on('keydown paste input change', function (e) {
            if (e.type === 'keydown' || e.type === 'paste') {
                e.preventDefault();
            }
            // Validate credit when rate changes
            var originalCustomerId = '{{ $post->customer_id }}';
            var selectedCustomerId = $('#load_bill_to').find('option:selected').data('id');
            validateBrokerCustomerCredit(selectedCustomerId, originalCustomerId);
        });
    });


    $(document).ready(function () {
            
            updateTotalshipper();


            function checkedvalueshipper(){
                let total = 0;

                // Loop through each checked checkbox with class 'for_invoice'
                $('.for_invoice:checked').each(function() {
                    // Find the closest .row parent
                    const row = $(this).closest('.row');

                    // Find the shipperchargeAmount input within that row
                    const amountInput = row.find('.shipperchargeAmount');

                    // Get the value and convert to float
                    const value = parseFloat(amountInput.val()) || 0;

                    // Add to total
                    total += value;
                });

                return total;

                // Output the total (you can display it or use it however needed)
                console.log("Total of checked amounts:", total);
            }

            

            function updateTotalshipper() {
                var total = 0;

                $('.shipperchargeAmount').each(function (index, inputBox) {
                    var amount = parseFloat($(inputBox).val()) || 0;
                    total += amount;
                });

                $('#totalChargeAmount').val(total.toFixed(2));

                var loadShipperRate = parseFloat($('#load_shipper_rate').val()) || 0;
                total += loadShipperRate;

                var loadFscRate = parseFloat($('#load_fsc_rate').val()) || 0;
                var fscAmount = (loadFscRate / 100) * loadShipperRate;
                total += fscAmount;

                $('#shipper_load_final_rate').val(total.toFixed(2));

                // Display credit deduction breakdown
                displayCreditDeduction(loadShipperRate, fscAmount, total);

                var customer_id = $('#customer_id').val();

                var checkedtotal = checkedvalueshipper();
                var newRemainingUsed = total - checkedtotal;
                var invoiceIncrease = Math.max(0, checkedtotal - oldInvoiceChargeTotal);
                var remainingIncrease = Math.max(0, newRemainingUsed - oldRemainingUsed);
                
                 $.ajax({
                        url: '{{ route('edit.check.remaing.limit') }}',
                        method: 'GET',
                        data: {
                            load_id: "{{$post->load_number}}",
                            customer_id: $('#customer_id').val() || "{{$post->customer_id }}",
                            finalrate: remainingIncrease + invoiceIncrease,
                            invoice_amount: invoiceIncrease,
                            remaining_amount: remainingIncrease,
                            _token: '{{ csrf_token() }}'
                        },  
                        success: function(response) {
                          
                               if (response.success) {

                                   showInvoiceLimitError(response.message);

                                // Hide after 10 seconds
                                setTimeout(function() {
                                    $('#mc-error-message').text('').fadeOut();
                                }, 2000); 
                               
                                // Keep the calculated Final Customer Rate visible; only disable submit
                                $('#myFormLoad').find('input[type="submit"], button[type="submit"]').prop('disabled', true).addClass('disabled');
                            } else {
                                // Credit OK - re-enable submit
                                $('#myFormLoad').find('input[type="submit"], button[type="submit"]').prop('disabled', false).removeClass('disabled');
                            }
                               
                        },
                        
                    });

            }

            function displayCreditDeduction(baseRate, fscAmount, totalDeduction) {
                var $creditDisplay = $('#creditlimitcheck');
                if (!$creditDisplay.length) return;

                // Calculate Non-Invoice Charges (For Invoice unchecked)
                var nonInvoiceCharges = 0;
                $('.shipperchargeAmount').each(function (index) {
                    var amount = parseFloat($(this).val()) || 0;
                    var isInvoice = $('[name="for_invoice[' + index + ']"]').is(':checked');
                    if (!isInvoice && amount > 0) {
                        nonInvoiceCharges += amount;
                    }
                });

                var deductionBreakdown = 'Base: $' + parseFloat(baseRate || 0).toFixed(2);
                if (fscAmount > 0) {
                    deductionBreakdown += ' + F.S.C: $' + parseFloat(fscAmount || 0).toFixed(2);
                }
                if (nonInvoiceCharges > 0) {
                    deductionBreakdown += ' + Charges: $' + parseFloat(nonInvoiceCharges || 0).toFixed(2);
                }
                // var displayText = 'Final Deduction: $' + parseFloat(totalDeduction || 0).toFixed(2) + ' (' + deductionBreakdown + ')';

                $creditDisplay.html('<small style="color: #0066cc; font-weight: 500;">' + displayText + '</small>');

                // Log detailed calculations to console for background tracking
                var fscRate = parseFloat($('#load_fsc_rate').val() || 0);
                console.log('Credit Deduction Breakdown:', {
                    'Base Rate': '$' + parseFloat(baseRate || 0).toFixed(2),
                    'FSC Rate %': fscRate + '%',
                    'FSC Amount': '$' + parseFloat(fscAmount || 0).toFixed(2),
                    'Non-Invoice Charges': '$' + parseFloat(nonInvoiceCharges || 0).toFixed(2),
                    'Total Final Rate': '$' + parseFloat(totalDeduction || 0).toFixed(2)
                });
            }

            $(document).on('input', '.shipperchargeAmount, #load_shipper_rate, #load_fsc_rate',
                function () {
                    updateTotalshipper();
                });

        });

        // Robust standalone handler to ALWAYS recalculate Final Customer Rate
        // (independent of any AJAX/credit-check flow) so the base rate always reflects.
        $(document).on('input keyup change', '#load_shipper_rate, #load_fsc_rate, .shipperchargeAmount', function () {
            var baseRate = parseFloat($('#load_shipper_rate').val()) || 0;
            var fscRate = parseFloat($('#load_fsc_rate').val()) || 0;
            var chargesTotal = 0;
            $('.shipperchargeAmount').each(function () {
                chargesTotal += parseFloat($(this).val()) || 0;
            });
            var finalRate = baseRate + ((fscRate / 100) * baseRate) + chargesTotal;
            $('#shipper_load_final_rate').val(finalRate.toFixed(2));
            $('#totalChargeAmount').val(chargesTotal.toFixed(2));
        });
        
    </script>

<script>
let rowIndex = {{ count($shipperCharges) }}; // Start index after existing rows

$(document).ready(function () {
    $('#addChargeBtn').click(function () {
        const newRow = `
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <input type="text" class="form-control" name="shipperchargeType[${rowIndex}]" placeholder="Enter Charge Type">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group mt-3">
                    <input type="checkbox" class="form-check-input for_invoice" name="for_invoice[${rowIndex}]" value="on">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <input type="text" class="form-control shipperchargeAmount" name="shipperchargeAmount[${rowIndex}]" placeholder="Enter Amount" min=0>
                </div>
            </div>
            <div class="col-md-1" style="margin-top: 11px;">
                <button class="remove-row" type="button" style="background:unset;border:none">
                    <i style="margin-top: 15px; color:red;" class="fa fa-trash"></i>
                </button>
            </div>
        </div>`;

        $('#chargeRowsContainer').append(newRow);
		initChargeRowValidation();
        rowIndex++; // Increment index for next row
    });

     function removerowcalculateTotalCharge() {
        var total = 0;
        // Loop through all the charge-amount inputs
        $('.shipperchargeAmount').each(function() {

            var value = parseFloat($(this).val());
            if (!isNaN(value)) {
                total += value;
            }
        });
       
        // Update the total charge field
        $('#totalChargeAmount').val(total.toFixed(2));


        var cust_rate = $('#load_shipper_rate').val();
        var final_rate = parseFloat(cust_rate) + parseFloat(total);
        $('#shipper_load_final_rate').val(final_rate);
    }
    // Remove row
    $(document).on('click', '.remove-row', function () {
        $(this).closest('.row').remove();
        removerowcalculateTotalCharge();
     
    });
});



$(document).ready(function () {
    const $loadType = $('#load_type_two');
    const $shippmentloadType = $('#load_type');

    function RestrictionOTR() {
       
        if ($loadType.val() === "OTR") {
            // $('#load_shipper_rate').removeAttr('readonly');
            // $('#load_fsc_rate').removeAttr('readonly');
            // $('#load_carrier_fee').removeAttr('readonly');
            // $('#load_billing_fsc_rate').removeAttr('readonly');
        }
    }

    // function RestrictionOTRonload() {
    //     const shipperRate = parseFloat($('#load_shipper_rate').val()) || 0;

    //     if ($loadType.val() === "OTR") {
    //         $('#load_shipper_rate').removeAttr('readonly');
    //         $('#load_fsc_rate').removeAttr('readonly');
    //         $('#load_carrier_fee').removeAttr('readonly');
    //         $('#load_billing_fsc_rate').removeAttr('readonly');
    //     } else if ($loadType.val() === "DRAYAGE") {
    //         if (shipperRate > 0) {
    //             $loadType.attr('readonly', true).css('pointer-events', 'none').css('background-color', '#e9ecef');

	// 			$('#load_shipper_rate').attr('readonly', true);
	// 			$('#load_fsc_rate').attr('readonly', true);
	// 			$('#load_carrier_fee').attr('readonly', true);
	// 			$('#load_billing_fsc_rate').attr('readonly', true);
    //         }
    //     }
    // }

    function RestrictionOTRonload() {
    const shipperRate = parseFloat($('#load_shipper_rate').val()) || 0;
    const loadTypeVal = $loadType.val();
    const shippmentloadTypeVal = $shippmentloadType.val();

    if (loadTypeVal === "OTR") {
        // $('#load_shipper_rate').removeAttr('readonly');
        // $('#load_fsc_rate').removeAttr('readonly');
        // $('#load_carrier_fee').removeAttr('readonly');
        // $('#load_billing_fsc_rate').removeAttr('readonly');

    } else if (shippmentloadTypeVal === "TONU") {
        // Allow editing shipper rate for TONU
        $('#load_shipper_rate').removeAttr('readonly');

    } else if (loadTypeVal === "DRAYAGE") {
        if (shipperRate > 0) {
            $loadType
                .attr('readonly', true)
                .css('pointer-events', 'none')
                .css('background-color', '#e9ecef');

            // $('#load_shipper_rate').attr('readonly', true);
            // $('#load_fsc_rate').attr('readonly', true);
            // $('#load_carrier_fee').attr('readonly', true);
            // $('#load_billing_fsc_rate').attr('readonly', true);
        }
    }
}

    RestrictionOTRonload();

    RestrictionOTR();
    
});
</script>
    
<script>
    $(document).ready(function () {
        // Function to calculate total charges
        function calculateTotal() {
            let total = 0;
            $('.otheramount').each(function () {
                let amount = parseFloat($(this).val()) || 0;
                total += amount;
            });
            $('#carrier_total_other_charge').val(total.toFixed(2));
            var load_carrier_fee = $('#load_carrier_fee').val();
			var load_billing_fsc_rate = $('#load_billing_fsc_rate').val();
			total += (load_billing_fsc_rate / 100) * load_carrier_fee;

            var final_rate_carrier = parseFloat(load_carrier_fee) + parseFloat(total);

            $('#load_final_carrier_fee').val(final_rate_carrier);
        }

        // Function to add a new input row
        $('.create-input').click(function () {
            // Clone the hidden template row
            var inputRow = $('#chargeRowTemplatecarrier').clone();
            // Remove the id attribute and display the row
            inputRow.removeAttr('id').show();
            // Append the new row to the container
            $('#inputs').append(inputRow);
            // Recalculate total amount after adding a new row
            calculateTotal();
        });

        // Function to remove an input row
        $(document).on('click', '.closebtn, .remove-charge', function () {
            $(this).closest('.row').remove();
            // Recalculate total amount after removing a row
            calculateTotal();
        });

        // Recalculate total amount when the page loads or when inputs are changed
        $(document).on('input', '.otheramount', function () {
            calculateTotal();
        });

        // Initial calculation on page load
        calculateTotal();
    });

</script>

<script>
$(document).ready(function () {
    $('.load_consignee').on('change', function () {

        var mainid = $(this).parent().parent().parent().parent().parent().attr('id');
        var name = $('#'+mainid+' .load_consignee').find('option:selected').data('name');
        var address = $('#'+mainid+' .load_consignee').find('option:selected').data('address');
        var city = $('#'+mainid+' .load_consignee').find('option:selected').data('city');
        var state = $('#'+mainid+' .load_consignee').find('option:selected').data('state');
        var country = $('#'+mainid+' .load_consignee').find('option:selected').data('country');
        var zip = $('#'+mainid+' .load_consignee').find('option:selected').data('zip');
      
        var countryParts = country.split(' ');
        var countryName = countryParts.slice(1).join(' ');

        var fullAddress = address + ', ' + city + ', ' +
            state + ', ' + zip + ', ' + country;

        //$('#'+mainid+' input[name="load_consignee"]').val(name);
        $('#'+mainid+' .load_consignee_location').val(fullAddress);
    });
});

$(document).ready(function () {
    $('.load_shipper').on('change', function () {
        var mainid = $(this).parent().parent().parent().parent().parent().attr('id');
        var name = $('#'+mainid+' .load_shipper').find('option:selected').data('name');
        var address = $('#'+mainid+' .load_shipper').find('option:selected').data('address');
        var city = $('#'+mainid+' .load_shipper').find('option:selected').data('city');
        var state = $('#'+mainid+' .load_shipper').find('option:selected').data('state');
        var country = $('#'+mainid+' .load_shipper').find('option:selected').data('country');
        var zip = $('#'+mainid+' .load_shipper').find('option:selected').data('zip');

        var countryParts = country.split(' ');
        var countryName = countryParts.slice(1).join(' ');

        var fullAddress = address + ', ' + city + ', ' +
            state + ', ' + zip + ', ' + country;

        //$('#'+mainid+' input[name="load_shipper"]').val(name);
        $('#'+mainid+' .load_shipper_location').val(fullAddress);
    });
});
</script>

    <script>
        $(document).ready(function () {


            // Function to calculate and update the total amount
            function updateTotalcarrier() {

                var total = 0;

                // Iterate through each charge input box
                $('[name="inputBox2[]"], [name="shipper_other_charge[]"]').each(function (index, inputBox) {
                    var amount = parseFloat($(inputBox).val()) || 0;
                    total += amount;
                });

                // Add load_carrier_fee to the total
                var loadCarrierFee = parseFloat($('#load_carrier_fee').val()) || 0;
                total += loadCarrierFee;

                // Get the billing FSC rate
                var billingFSCRate = parseFloat($('#load_billing_fsc_rate').val()) || 0;

                // Calculate the percentage of load_carrier_fee based on billing FSC rate
                var fscAmount = (loadCarrierFee * billingFSCRate) / 100;

                // Add the calculated FSC amount to the total
                total += fscAmount;

                // Set the sum in load_final_carrier_fee
                $('#load_final_carrier_fee').val(total.toFixed(2));

                var customer_rate = $('#shipper_load_final_rate').val();
            
                if(total > customer_rate){
                      $('#mc-error-message').text("Final Carrier Fee not graterthe Shipper Final rate").fadeIn();
                        $('#load_carrier_fee').val(0);
                        $('#load_final_carrier_fee').val(0);
                        // Hide after 10 seconds
                        setTimeout(function() {
                            $('#mc-error-message').text('').fadeOut();
                        }, 2000); 
                }
            }

            // Handle input changes to update the total
            $(document).on('input',
                '[name="inputBox2[]"], [name="shipper_other_charge[]"], #load_carrier_fee, #load_billing_fsc_rate',
                function () {
                   
                    updateTotalcarrier();
                });
        });
</script>
<script>
    $(document).ready(function () {
    // Function to fetch carrier suggestions based on any input (name, MC number, DOT number)
    function fetchCarrierSuggestions(field, inputValue) {
        $.ajax({
            url: '{{ route('fetch.carrier.suggestions') }}',
            method: 'POST',
            data: {
                field: field,          // Specify the field (name, MC, DOT)
                inputValue: inputValue, // User input
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                let carrierList = $('#carrier-list');
                carrierList.empty(); 

                if (response.length > 0) {
                    response.forEach(function (carrier) {
                        carrierList.append('<li class="list-group-item carrier-item" data-id="' + carrier.id + '">' + carrier.carrier_name + ' - MC: ' + carrier.mcNumber + ', DOT: ' + carrier.dotNumber + '</li>');
                    });
                    carrierList.show();
                } else {
                    carrierList.hide();
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching carrier suggestions: ", error);
            }
        });
    }


    // Function to fetch full carrier details once a carrier is selected
    function fetchCarrierDetails(carrierId) {
        $.ajax({
            url: '{{ route('fetch.carrier.details') }}',
            method: 'POST',
            data: {
                carrierId: carrierId, 
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response) {
                    $('#carrier_id').val(response.id);
                    $('#load_carrier').val(response.carrier_name);
                    $('#carrier_mc_ff_input').val(response.mcNumber);
                    $('#carrier_dot').val(response.dotNumber);
                    $('#load_carrier_phone').val(response.phone);
                }
                $('#carrier-list').hide(); 
            },
            error: function (xhr, status, error) {
                console.error("Error fetching carrier details: ", error);
            }
        });
    }


    // Event handler for input fields to trigger carrier suggestions
    function handleInputChange() {
        let inputValue = $(this).val();
        let field = $(this).attr('id');

        if (inputValue.length >= 3) {
            fetchCarrierSuggestions(field, inputValue);
        } else {
            $('#carrier-list').hide();
        }
    }

    // Attach the event to all relevant fields (carrier name, MC number, DOT number)
    $('#load_carrier, #carrier_mc_ff_input, #carrier_dot').on('input', handleInputChange);

    // Handle selection of a carrier from the suggestion list
    $(document).on('click', '.carrier-item', function () {
        let carrierId = $(this).data('id');
        fetchCarrierDetails(carrierId); // Fetch the full carrier details
    });

    // Hide the suggestion list when clicking outside the input fields
    $(document).click(function (e) {
        if (!$(e.target).closest('#load_carrier, #carrier_mc_ff_input, #carrier_dot, #carrier-list').length) {
            $('#carrier-list').hide();
        }
    });
});
</script>

<script>
$(document).ready(function() {
  $('#load_shipper_commodity_1').on('input', function() {
    $('#load_consignee_commodity_1').val($(this).val());
  });
  $('#load_shipper_qty_1').on('input', function() {
    $('#load_consignee_qty_1').val($(this).val());
  });
  $('#load_shipper_weight_1').on('input', function() {
    $('#load_consignee_weight_1').val($(this).val());
  });
  $('#load_shipper_value_1').on('input', function() {
    $('#load_consignee_value_1').val($(this).val());
  });
});
</script>


<script>
$(document).on('change', 'input[name^="load_consignee_appointment_"]', function () {

    let deliveryInput = $(this);
    let row = deliveryInput.closest('.row');

    let pickupInput = row.find('input[name^="load_shipper_appointment_"]');

    let pickupValue = pickupInput.val();
    let deliveryValue = deliveryInput.val();

    if (pickupValue && deliveryValue) {

        let pickupDate = new Date(pickupValue);
        let deliveryDate = new Date(deliveryValue);

        // Delivery must be greater than pickup
        if (deliveryDate <= pickupDate) {
            alert("Delivery date & time must be later than Pickup date & time.");
            deliveryInput.val('');
        }
    }

});
</script>

<script>
$(document).on('change', 'input[name^="load_shipper_appointment_"]', function () {

    let pickupInput = $(this);
    let row = pickupInput.closest('.row');

    let deliveryInput = row.find('input[name^="load_consignee_appointment_"]');

    deliveryInput.attr('min', pickupInput.val());

});
</script>

<!-- html2pdf -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>

function enableEdit() {

    document.querySelectorAll('.editable-field').forEach(function(el) {

        el.removeAttribute('readonly');

        el.style.border = '1px solid #ccc';
        el.style.padding = '5px';

    });

    // Show the Save button (saves to session for PDF download)
    document.getElementById('saveBolBtn').classList.remove('d-none');

}

</script>

<script>

function saveBOL() {
    var bolData = {};
    
    document.querySelectorAll('.editable-field').forEach(function(el) {
        var fieldName = el.getAttribute('data-field') || el.getAttribute('name') || el.id || ('field_' + Math.random());
        if (fieldName) {
            bolData[fieldName] = el.value;
        }
    });

    // Store in session via AJAX (not in DB)
    $.ajax({
        url: '/broker/load/{{ $post->id }}/bol/save',
        method: 'POST',
        data: {
            bol_data: bolData,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                alert('BOL data saved for PDF download!');
                document.getElementById('saveBolBtn').classList.add('d-none');
                document.querySelectorAll('.editable-field').forEach(function(el) {
                    el.setAttribute('readonly', true);
                    el.style.border = 'none';
                });
            }
        },
        error: function(xhr) {
            alert('Error saving BOL data. Please try again.');
        }
    });
}

</script>

<script>
function addFreightRow() {

    let tableBody = document.getElementById('freightTableBody');

    let rowCount = tableBody.rows.length + 1;

    let row = `
        <tr>

            <td>
                <input type="text"
                    class="form-control editable-field unit-number"
                    value="#Unit ${rowCount}">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="Description">
            </td>

            <td>
                <input type="number"
                    class="form-control editable-field weight-field"
                    placeholder="Weight"
                    onkeyup="updateTotals()"
                    onchange="updateTotals()">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="Type">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="NMFC">
            </td>

            <td>
                <input type="text"
                    class="form-control editable-field"
                    placeholder="HM">
            </td>

            <td>

                <div class="d-flex gap-2">

                    <input type="text"
                        class="form-control editable-field"
                        placeholder="Class">

                    <button type="button"
                        class="btn btn-danger btn-sm pdf-hide"
                        onclick="removeRow(this)">
                        ×
                    </button>

                </div>

            </td>

        </tr>
    `;

    tableBody.insertAdjacentHTML('beforeend', row);

    updateTotals();

}

function removeRow(button) {

    button.closest('tr').remove();

    reArrangeUnits();

    updateTotals();

}

function reArrangeUnits() {

    let units = document.querySelectorAll('.unit-number');

    units.forEach((unit, index) => {

        unit.value = '#Unit ' + (index + 1);

    });

}

function updateTotals() {

    let weightFields = document.querySelectorAll('.weight-field');

    let totalWeight = 0;

    weightFields.forEach(field => {

        let value = parseFloat(field.value);

        if (!isNaN(value)) {

            totalWeight += value;

        }

    });

    document.getElementById('totalWeight').innerText =
        totalWeight.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    document.getElementById('totalPieces').innerText =
        document.querySelectorAll('#freightTableBody tr').length;

}

updateTotals();

</script>

<script>


async function downloadBOL() {

    // Hide buttons/elements that shouldn't appear in PDF
    document.querySelectorAll('.pdf-hide').forEach(el => el.style.display = 'none');

    var element = document.getElementById('bolDownloadArea');
    
    // Temporarily override styles for clean PDF output
    element.style.zoom = '1';
    element.style.padding = '0';
    element.style.margin = '0';
    
    // Replace input/textarea values with plain text for clean PDF
    var inputs = element.querySelectorAll('input.editable-field, textarea.editable-field');
    var originals = [];
    inputs.forEach(function(input) {
        var span = document.createElement('span');
        span.className = 'pdf-temp-value';
        span.style.display = 'block';
        span.style.minHeight = '20px';
        span.style.padding = '2px 4px';
        span.style.fontSize = '12px';
        span.style.fontWeight = '600';
        span.style.color = '#333';
        span.style.whiteSpace = 'pre-wrap';
        span.style.wordBreak = 'break-word';
        span.textContent = input.value || '';
        originals.push({ input: input, parent: input.parentNode, next: input.nextSibling });
        input.parentNode.replaceChild(span, input);
    });

    var opt = {
        margin:       [5, 8, 5, 8],
        filename:     'BOL-{{ $post->load_number }}.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'letter', orientation: 'portrait' },
        pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
    };

    await html2pdf().set(opt).from(element).save();

    // Restore original inputs
    originals.forEach(function(item) {
        var spans = item.parent ? item.parent.querySelectorAll('.pdf-temp-value') : [];
        spans.forEach(function(span) {
            span.parentNode.replaceChild(item.input, span);
        });
    });

    // Restore styles
    element.style.zoom = '0.8';
    element.style.padding = '20px';
    
    // Restore hidden elements
    document.querySelectorAll('.pdf-hide').forEach(el => el.style.display = '');

}

</script>
<script>
    $(document).on('input', '.shipperchargeAmount', function () {

        var value = $(this).val().trim();

        // Check if user enters minus (-) or any negative amount
        if (value.includes('-')) {

            alert('Minus amount is not allowed in Other Charges.');

            $(this).val(0);
            return;
        }

        // Check numeric value less than 0
        var amount = parseFloat(value);

        if (!isNaN(amount) && amount < 0) {

            alert('Less than 0 amount is not allowed in Other Charges.');

            $(this).val(0);
        }
    });
</script>
<style>

#bolDownloadArea{
    width:100%;
    background:#fff;
    color:#222;
    font-family:Arial, sans-serif;
    padding:15px;
    font-size:12px;
    line-height:1.4;
}

#bolDownloadArea table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:10px;
}

#bolDownloadArea th{
    background:#f3f3f3;
    font-weight:700;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.3px;
    padding:6px 8px;
}

#bolDownloadArea th,
#bolDownloadArea td{
    border:1px solid #000 !important;
    padding:6px 8px;
    vertical-align:top;
    font-size:12px;
}

#bolDownloadArea h3{
    color:#111;
    font-weight:800;
    font-size:22px;
    margin:0 0 4px 0;
}

#bolDownloadArea h6{
    font-size:13px;
    margin-bottom:6px;
    font-weight:700;
}

#bolDownloadArea textarea,
#bolDownloadArea input{
    background:transparent !important;
    box-shadow:none !important;
    font-size:12px;
    color:#333;
    border:none !important;
    padding:2px 4px;
    font-weight:600;
}

#bolDownloadArea .border{
    border:1px solid #000 !important;
}

#bolDownloadArea .row{
    margin-bottom:8px;
}

#bolDownloadArea .col-md-6,
#bolDownloadArea .col-md-8,
#bolDownloadArea .col-md-4{
    padding:4px 8px;
}

.table-grey th{
    background:#e9ecef !important;
}

.pdf-temp-value{
    width:100%;
    min-height:24px;
    padding:4px;
    white-space:pre-wrap;
    word-break:break-word;
    line-height:1.5;
}

.company-header{
    border-bottom:2px solid #000;
    padding-bottom:10px;
    margin-bottom:20px;
}

.signature-box{
    height:80px;
}

@media print {

    .pdf-hide{
        display:none !important;
    }

}

</style>