@extends('layout.compact.app')
@section('content')
@if(session('success'))
<div class="alert alert-success" id="successMessage">
   {{ session('success') }}
</div>
@endif
<style>
   
   .modal-title {
      font-size: 17px;
      text-align: left;
      font-weight: 700;
   }

   .form-group .form-group {
      margin-bottom: 1rem;
   }

   .modal-body label {
      font-weight: 600;
      text-align: left;
      font-size: 13px;
      color: #4a4a4a;
      font-family: 'Poppins';
   }

   .modal-body .form-group {
      margin-bottom: 23px;
   }
   span.select2.select2-container.select2-container--default {
		z-index: 9 !important;
	}

    .customer-history-box .table {
        margin-bottom: 0;
    }

    .customer-history-box .table th {
        white-space: nowrap;
        font-size: 12px;
    }

    .customer-history-box .table td {
        font-size: 13px;
        vertical-align: middle;
    }

    .customer-history-box .history-section-title {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }
</style>

 
<section class="page-content">
   <div class="body_scroll">
      <div class="block-header">
         <h2>Edit Customer</h2>
      </div>
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
      <div class="container-fluid">
        
         <!-- Multi Column -->
         <div class="row clearfix">
            <div class="card">
               <div class="card-body">
                  <form method="POST" action="{{ route('update.customer', $customer->id) }}">
                     @csrf
                     @method('PUT')
                     
                     <div class="body">
                        <div class="row clearfix">
                           <div class="col-md-3">
                              <div class="form-group">
                                 <label for="customer_hold">Customer Hold Status<code>*</code></label>
                                 <input type="checkbox" name="customer_hold_status" value="hold" {{ $customer->customer_hold_status == 'hold' ? 'checked' : '' }}>
                                 <span style="color:red">{{ $customer->customer_hold_status == 'hold' ? 'Hold' : 'Unhold' }}</span>
                              </div>
                           </div>
                           <div class="col-md-3">
                              <div class="form-group">
                                 <label for="customer_name">Customer Name<code>*</code></label>
                                 <input type="text" class="form-control" id="customer_name"
                                    name="customer_name" value="{{ $customer->customer_name }}" >
                                    @if ($errors->has('customer_name'))
                                        <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                                    @endif
                              </div>
                           </div>
                           <div class="col-md-3">
                              <div class="form-group">
                                 <label for="customer_address">MC# /FF#</label>
                                 <input type="text" class="form-control" id="customer_address"
                                    name="customer_mc_ff_input"
                                    value="{{ $customer->customer_mc_ff }} {{ $customer->customer_mc_ff_input }}"
                                    >
                                 <!-- <label for="customer_mc_ff">MC# / FF#</label>
                                 <div class="d-flex">
                                    <select class="form-control select2 mr-2" id="customer_mc_ff" name="customer_mc_ff">
                                       <option value="NA" {{ $customer->customer_mc_ff == 'NA' || $customer->customer_mc_ff == '' ? 'selected' : '' }}>NA</option>
                                       <option value="MC" {{ $customer->customer_mc_ff == 'MC' ? 'selected' : '' }}>MC</option>
                                       <option value="FF" {{ $customer->customer_mc_ff == 'FF' ? 'selected' : '' }}>FF</option>
                                    </select>
                                    <input type="text" class="form-control select2" id="customer_mc_ff_input" name="customer_mc_ff_input" value="{{ $customer->customer_mc_ff_input }}">
                                 </div> -->
                              </div>
                           </div>
                           <div class="col-md-3">
                              <div class="form-group">
                                 <label for="customer_address">Customer Address <code>*</code></label>
                                 <input type="text" class="form-control" id="customer_address"
                                    name="customer_address" value="{{ $customer->customer_address }}"
                                    >
                                     @if ($errors->has('customer_address'))
                                        <span class="text-danger">{{ $errors->first('customer_address') }}</span>
                                    @endif
                              </div>
                           </div>
                           <div class="col-md-3">
                              <div class="form-group">
                                 <label for="customer_city">City <code>*</code></label>
                                 <input type="text" class="form-control" id="customer_city"
                                    name="customer_city" value="{{ $customer->customer_city }}">
                                    @if ($errors->has('customer_city'))
                                        <span class="text-danger">{{ $errors->first('customer_city') }}</span>
                                    @endif
                              </div>
                           </div>
                           
                           <div class="col-md-3 mt-2">
                                <div class="form-group mb-3">
									<label>Country <code>*</code></label>
									<div>
										<select class="form-control select2" name="customer_country"
											id="country">
											<option value="">Choose Country</option>
											@foreach($allcountry as $country)
											<option value="{{$country->name}}" data-id="{{$country->id}}"  @if($customer->customer_country == $country->name) selected @endif>{{$country->name}}</option>
											@endforeach
										</select>
									</div>
								</div>
                           </div>
						   
						   <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="customer_state">State <code>*</code></label>
                                 <div>
									<select class="form-control select2" name="customer_state"
										id="state" readonly>										
										@foreach($state as $states)
										@if($states['id'] == $customer->customer_state)
											<option value="{{$customer->customer_state}}" selected>{{$states['name']}}</option>
										@endif
										@endforeach
									</select>
								</div>
                              </div>
                           </div>
                           
                          
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="customer_zip">Zip <code>*</code></label>
                                 <input type="text" class="form-control" id="customer_zip"
                                    name="customer_zip" value="{{ $customer->customer_zip }}">
                                    @if ($errors->has('customer_zip'))
                                        <span class="text-danger">{{ $errors->first('customer_zip') }}</span>
                                    @endif
                              </div>
                           </div>
                           
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="customer_telephone">Customer Telephone <code>*</code></label>
                                 <input type="text" class="form-control" id="customer_telephone"
                                    name="customer_telephone" value="{{ $customer->customer_telephone }}"
                                    >
                                     @if ($errors->has('customer_telephone'))
                                        <span class="text-danger">{{ $errors->first('customer_telephone') }}</span>
                                    @endif
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                              
                                 <label>
                                    <a href="javascript:void(0)" data-customer-id="{{ $customer['id'] }}" data-bs-toggle="modal" data-bs-target="#customerDetailsModal">Amt Customer Aging (Invoice Date)</a>
                                 </label>
                              

                                 <input class="form-control" type="number" value="{{ $customerAging }}" name="approved_limit" readonly style="width: 100%; height: 30px !important; padding: 0px 0 0 10px;">
                              </div>
                           </div>


                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label><a href="javascript:void(0)" data-customer-id="{{ $customer['id'] }}" data-bs-toggle="modal" data-bs-target="#thirtydaysaging">Amt Invoices Over 30 Days</a></label>
                                 <input class="form-control" type="number" value="{{ $totalCustomerPayment }}" readonly name="approved_limit" style="width: 100%;height:30px !important;padding: 0px 0 0 10px;">
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label>Approved Credit Limits (OTR)</label>
                                 <input class="form-control" type="number" value="{{ $customer->approved_limit }}" name="approved_limit" style="width: 100%;height:30px !important;padding: 0px 0 0 10px;" readonly>
                                   
                                </div>
                           </div>
                        
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="adv_customer_credit_limit">Assigned Credit Limit
                                    <i class="fa fa-plus" data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#assigned-credit"></i>
                                 </label>
                                 <input type="text" class="form-control" id="adv_customer_credit_limit" name="adv_customer_credit_limit"  value="{{ $totalCreditLimit }}"  readonly>
                               
                                </div>
                           </div>
                                    <div class="col-md-3 mt-2">
                                        <div class="form-group">
                                            <label for="credit_log_user_filter">Credit Log User</label>
                                            <select class="form-control" id="credit_log_user_filter">
                                                <option value="all">All Brokers</option>
                                                @if($creditLimitHistory->contains(fn ($entry) => $entry['broker_id'] === 'legacy'))
                                                    <option value="legacy">Legacy / Unassigned</option>
                                                @endif
                                                @foreach($creditLogUsers->sort() as $creditLogUserId => $creditLogUserName)
                                                    <option value="{{ $creditLogUserId }}">{{ ucwords($creditLogUserName) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="used_amount">Total Exhausted Limit</label>
                                 <input type="text" class="form-control" id="total_exhausted_limit" name="used_amount" value="{{ number_format((float) ($totalExhaustedLimit ?? $usedAmount ?? 0), 2, '.', '') }}" required readonly>
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="remaining_credit">Remaining Credit Limit
                                 <i class="fa fa-plus" data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#assigned-credit-remaing"></i>
                              </label>
                                 <input type="text" class="form-control" id="remaining_credit" name="remaining_credit" value="{{ $remainingCredit }}" required readonly>
                                 <input type="hidden" class="form-control" id="remaining_credit_new" name="remaining_credit_new" value="{{ $remainingCredit }}" required readonly>
                             
                             </div>
                           </div>



                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="status">Status</label>
                                 <select type="text" class="form-control" id="statuses" name="status" required>
                                    <option value="">Please Select Status</option>
                                    <option value="Approved" {{ $customer->status == 'Approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="Not Approved" {{ $customer->status == 'Not Approved' ? 'selected' : '' }}>Not Approved</option>
                                 </select>
                              </div>
                           </div>
                          
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label>Assigned Broker</label>
                                 <select class="form-control" required name="user_id" id="user_id">
                                    <option class="hiddenOption" disabled>Select Broker</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                       {{ $user->id == $customer->user_id ? 'selected' : '' }}>
                                       {{ ucwords($user->name) }}
                                    </option>
                                    @endforeach
                                 </select>
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label>Commenter's Name<code>*</code></label>
                                 <input type="text" class="form-control" readonly name="commenter_name[]" id="commenter_name" value="{{Auth::user()->name}}">
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label>Total load Creation Amount<code>*</code></label>

                                 <input type="text" class="form-control" readonly id="total_load_creation_amount" value="{{ number_format((float) $loadcreateamount, 2, '.', '') }}">
                              </div>
                           </div>
                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label>Total Payment Received<code>*</code>
                                 <i class="fa fa-plus" data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#paymentmarkedlist"></i>
                              </label>
                                 <input type="text" class="form-control" readonly id="total_payment_received" value="{{ number_format((float) $receiving_amount, 2, '.', '') }}">
                              </div>
                           </div>

                           <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="invoice_credit_limit">Remaining Invoice Credit Limit</label>
								  <i class="fa fa-plus" data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#invoice-credit-limit"></i>
                                 <input type="text" class="form-control" id="invoice_credit_limit" name="invoice_credit_limit"  value="{{ $customer->invoice_credit_limit }}" readonly>
                              </div>
                           </div>
                            @php
                                $logs = json_decode($customer->remaining_credit_logs, true) ?? [];
                                $finalTotalCredit = 0;

                                if (is_array($logs)) {
                                    foreach ($logs as $log) {
                                        $finalTotalCredit += (float) ($log['credit_limit'] ?? 0);
                                    }
                                }

                                if ($finalTotalCredit <= 0) {
                                    $finalTotalCredit = (float) ($totalCreditLimit ?? $customer->adv_customer_credit_limit ?? 0);
                                }

                                // Sum of every entry logged behind the invoice credit limit modal
                                $invoiceCreditLogs = json_decode($customer->invoice_credit_limit_log, true) ?? [];
                                $totalInvoiceCreditLimit = 0;

                                if (is_array($invoiceCreditLogs)) {
                                    foreach ($invoiceCreditLogs as $invoiceLog) {
                                        $totalInvoiceCreditLimit += (float) ($invoiceLog['credit_limit'] ?? 0);
                                    }
                                }

                                if ($totalInvoiceCreditLimit <= 0) {
                                    $totalInvoiceCreditLimit = (float) ($customer->invoice_credit_limit ?? 0);
                                }

                                $combinedCreditLimit = $totalInvoiceCreditLimit + $finalTotalCredit;
                            @endphp

                            <div class="col-md-3 mt-2">
                                <div class="form-group">
                                    <label for="total_credit_limit">Total Credit Limit</label>
                                    <input type="text" class="form-control" id="total_credit_limit"
                                        value="{{ number_format($finalTotalCredit, 2, '.', '') }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mt-2">
                                <div class="form-group">
                                    <label for="total_invoice_credit_limit">Total Invoice Credit Limit</label>
                                    <input type="text" class="form-control" id="total_invoice_credit_limit"
                                        value="{{ number_format($totalInvoiceCreditLimit, 2, '.', '') }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mt-2">
                                <div class="form-group">
                                    <label for="total_combined_credit_limit">Total Invoice Credit Limit + Total Credit Limit</label>
                                    <input type="text" class="form-control" id="total_combined_credit_limit"
                                        value="{{ number_format($combinedCreditLimit, 2, '.', '') }}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-md-3 mt-2">
                              <div class="form-group">
                                 <label for="invoice_credit_limit">Invoiced Through</label>
                                    <select class="form-control" name="invoice_through" id="invoice_through">
                                        <option value="">Select Invoice Type</option>
                                        <option value="OTR" {{ $customer->invoice_through == 'OTR' ? 'selected' : '' }}>OTR</option>
                                        <option value="DIRECT" {{ $customer->invoice_through == 'DIRECT' ? 'selected' : '' }}>DIRECT</option>
                                    </select>
                              </div>
                           </div>

                            <div class="col-md-3 mt-2">
                                        <div class="form-group">
                                            <label for="pre_payment">Pre-Payment Flagged</label>
                                            <div class="form-check form-switch">
                                               <input class="form-check-input" type="checkbox" id="pre_payment" name="pre_payment" data-id="{{ $customer->id }}" value="1" {{ $customer->pre_payment == 1 ? 'checked' : '' }}>
                                               <label class="form-check-label" for="pre_payment">
                                                    <span id="pre_payment_label">
                                                        {{ $customer->pre_payment == 1 ? 'Pre-Payment' : 'Not Applicable' }}
                                                    </span>
                                                </label>

                                            </div>
                                        </div>
                            </div>
                            <script>
                                $(document).ready(function() {
                                    $('#pre_payment').on('change', function() {
                                        const label = $(this).is(':checked') ? 'Pre-Payment' : 'Not Applicable';
                                        $('#pre_payment_label').text(label);
                                    });
                                });
                            </script>


   


                           <div class="col-md-12 mt-2">
                              <div class="form-group">
                                 <label>Comment</label>
                                 <textarea name="comment_notes[]" class="form-control" cols="60" rows="5">{{ is_array($customer->comment_notes) ? implode("\n", json_decode($customer->comment_notes, true)) : $customer->comment_notes }}</textarea>
                              </div>
                           </div>
                           <div id="commentFields">
                           <!-- Initial comment fields here -->
                           </div>

                           <div class="col-md-12 mt-2">
                              <div class="form-group">
                                 <label>Private Comment</label>
                                 <textarea name="private_comment_notes[]" class="form-control" cols="60" rows="5">{{ is_array($customer->private_comment_notes) ? implode("\n", json_decode($customer->private_comment_notes, true)) : $customer->private_comment_notes }}</textarea>
                              </div>
                           </div>
                           

                        <div class="text-center mt-4">
						<span class="btn btn-warning" data-bs-toggle="modal" style="cursor:pointer;" data-bs-target="#view-documents-{{ $customer->id }}"> View Documents</span>
                           <button type="submit" class="btn btn-info">Update</button>
                           <a class="btn btn-danger" href="javascript:history.back()">Cancel</a>
                        </div>
                     </div>

<div class="card mt-4 customer-history-box">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">{{ $customer->customer_name }} - Broker & Credit History</h4>
        <span class="text-muted">{{ $brokerLoadHistory->sum('load_count') }} total loads</span>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <h5 class="history-section-title mb-2">Broker Summary</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Broker</th>
                            <th>Loads Created</th>
                            <th>Total Load Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brokerLoadHistory as $brokerHistory)
                            <tr data-history-user="{{ $brokerHistory['broker_id'] }}">
                                <td>{{ $brokerHistory['broker_name'] }}</td>
                                <td>{{ $brokerHistory['load_count'] }}</td>
                                <td>${{ number_format($brokerHistory['load_total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No load history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="history-section-title mb-2">Load Creation Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Load</th>
                            <th>Broker</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Aging Amount</th>
                            <th>Aging Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brokerLoadHistory as $brokerHistory)
                            @foreach($brokerHistory['loads'] as $historyLoad)
                                <tr data-history-user="{{ $brokerHistory['broker_id'] }}" data-load-amount="{{ (float) ($historyLoad->shipper_load_final_rate ?: $historyLoad->load_final_rate ?: 0) }}" data-received-amount="{{ (float) ($historyLoad->receiving_amount ?: 0) }}">
                                    <td>{{ optional($historyLoad->created_at)->format('m/d/Y h:i A') ?? 'N/A' }}</td>
                                    <td>{{ $historyLoad->load_number }}</td>
                                    <td>{{ $brokerHistory['broker_name'] }}</td>
                                    <td>{{ $historyLoad->invoice_status ?: 'Pending' }}</td>
                                    <td>${{ number_format((float) ($historyLoad->shipper_load_final_rate ?: $historyLoad->load_final_rate ?: 0), 2) }}</td>
                                    <td>${{ number_format(max(0, (float) ($historyLoad->shipper_load_final_rate ?: $historyLoad->load_final_rate ?: 0) - (float) ($historyLoad->receiving_amount ?: 0)), 2) }}</td>
                                    <td>{{ $historyLoad->invoice_date ? now()->diffInDays($historyLoad->invoice_date) : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No load history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h5 class="history-section-title mb-2">Credit Limit History</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Limit Type</th>
                            <th>Amount</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditLimitHistory as $limitHistory)
                            <tr data-history-user="{{ $limitHistory['broker_id'] }}" data-credit-type="{{ $limitHistory['type'] }}" data-credit-amount="{{ $limitHistory['credit_limit'] }}">
                                <td>{{ $limitHistory['credit_time'] ? date('m/d/Y h:i A', strtotime($limitHistory['credit_time'])) : 'N/A' }}</td>
                                <td>{{ $limitHistory['type'] }}</td>
                                <td>${{ number_format($limitHistory['credit_limit'], 2) }}</td>
                                <td>{{ $limitHistory['broker_name'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No credit-limit history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="invoice-credit-limit">
   <div class="modal-dialog">
	  <div class="modal-content">
		 <!-- Modal Header -->
		 <div class="modal-header" style="padding-left: 14px;">
			<h4 class="modal-title">Add invoice Credit Limit</h4>
			<button type="button" class="close" data-bs-dismiss="modal">&times;</button>
		 </div>

		 <!-- Modal Body -->
		 @php
			// Decode the credit limit log from the customer
			$invoice_credit_log = json_decode($customer->invoice_credit_limit_log, true) ?? [];
		 @endphp

		 <div class="modal-body invoice-model-body">
			<!-- Existing Credit Logs -->
			<div class="row form-row invoice-credit-entry">
				  <div class="col-md-4">
					 <div class="form-group">
						<label class="mb-0">Credit Limit</label>
					 </div>
				  </div>
				  <div class="col-md-4">
					 <div class="form-group">
						<label class="mb-0">Time & Date</label>
					 </div>
				  </div>
				  <div class="col-md-3">
					 <div class="form-group">
						<label class="mb-0">User</label>
					 </div>
				  </div>
			   </div>
			   @if (!empty($invoice_credit_log))
				  @foreach ($invoice_credit_log as $key => $credit)
                                    <div class="row form-row invoice-credit-log-entrys" data-index="{{ $key }}" data-credit-log-user="{{ $credit['user_id'] ?? 'legacy' }}">
						   <div class="col-md-4">
							  <div class="form-group">
								 <input type="number" 
										  class="form-control invoice-credit-limits" 
										  readonly 
										  value="{{ !empty($credit['credit_limit']) ? $credit['credit_limit'] : ''}}" 
										  placeholder="Enter invoice credit limit">
							  </div>
						   </div>
						   <div class="col-md-4">
							  <div class="form-group">
								 <input type="datetime-local" 
										  class="form-control credit-time" 
										  readonly 
										  value="{{ $credit['credit_time'] }}">
							  </div>
						   </div>
						   <div class="col-md-3">
							  <div class="form-group">
								 <input type="text" class="form-control" readonly
										value="{{ $credit['user_name'] ?? ($creditLogUsers->get((string) ($credit['user_id'] ?? '')) ?? (($credit['user_id'] ?? null) ? 'User ' . $credit['user_id'] : 'Legacy / Unassigned')) }}">
							  </div>
						   </div>
						   <div class="col-md-1">
							  <div class="form-group">
								 <a href="javascript:void(0);" class="edit-field" data-index="{{ $key }}">
									   <i class="fa fa-edit" style="margin-top: 11px;"></i>
								 </a>
							  </div>
						   </div>
					 </div>
				  @endforeach
			   @endif


			<!-- Add New Credit Limit -->
			<div class="row form-row invoice-credit-log-entry">
			   <div class="col-md-4">
				  <div class="form-group">
					 <input type="number" class="form-control invoice-credit-limit" placeholder="Enter invoice credit limit" name="invoice_credit_limits[]">
				  </div>
			   </div>
			   <div class="col-md-4">
				  <div class="form-group">
					 <input type="datetime-local" class="form-control" name="invoice_credit_time[]" readonly value='{{ now()->format("Y-m-d\TH:i") }}'>
				  </div>
			   </div>
			   <div class="col-md-1">
				  <div class="form-group">
					 <i class="fa fa-edit" style="margin-top: 11px;"></i>
				  </div>
			   </div>
			</div>

			<!-- Add More Button -->
		 </div>
		 <div class="text-center mt-1">
			<button type="button" class="btn btn-success" id="addMoreinvoiceLimit" style="font-size: 14px; padding: 8px 20px;">Add more limit</button>
		 </div>
	  </div>
   </div>
</div>
<div class="modal fade" id="view-documents-{{ $customer->id }}" tabindex="-1" aria-labelledby="view-documents" aria-hidden="true" style="z-index: 9999;">
	<div class="modal-dialog modal-lg" style="max-width: 800px;">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header" style="padding-left: 14px;">
				<h4 class="modal-title">View Documents</h4>
				<button type="button" class="close" data-bs-dismiss="modal">&times;</button>
				</div>


				<div class="modal-body">
				<!-- Existing Credit Logs -->

@php
    $alladoc = $customer->remittance;
    $docs = json_decode($alladoc, true);

    if ($docs) {
        // Sort by uploaded_at descending
        usort($docs, function($a, $b) {
            return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
        });
    }
@endphp




				@if(empty($docs))
					<p>No documents found.</p>
				@else

					<div class="accordion" id="accordionExample">
						@foreach($docs as $key => $all)
                                @php
                                    $file = $all['path'];
                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $fileName = basename($file); // Extract file name
                                    $uploadedAt = \Carbon\Carbon::parse($all['uploaded_at'])->format('M d, Y'); // Format date
                                    $note = $all['note'] ?? ''; // Optional note if exists

                                    
                                    
                        @endphp
						
						<div class="accordion-item">
							<h2 class="accordion-header" id="headingOne">
								<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
								    @if(!empty($fileName))
                                        File: {{ $fileName }}
                                    @endif

                                    @if(!empty($uploadedAt))
                                        Upload at: {{ $uploadedAt }}
                                    @endif

                                    @if(!empty($note))
                                        Note: {{ $note }}
                                    @endif
								</button>
							</h2>
							<div id="collapse{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
								<div class="accordion-body">
								   @php
										$file = $all['path']; 
										$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
									@endphp

									<div style="margin-bottom: 20px;">
										@if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
											<!-- Image Preview -->
											<img src="{{ asset('public/'.$file) }}" alt="Image" style="max-width: 500px;">
										@elseif($extension === 'pdf')
											<!-- PDF Preview -->
											<embed src="{{ asset('public/'.$file) }}" type="application/pdf" width="600" height="400">
										@elseif(in_array($extension, ['doc', 'docx']))
											<!-- Word Preview with Google Docs Viewer -->
											<iframe src="https://docs.google.com/gview?url={{ urlencode(asset('public/'.$file)) }}&embedded=true" 
													style="width:600px; height:500px;" frameborder="0"></iframe>
											<!-- Optional download link -->
											<br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
										@else
											<!-- Unsupported file -->
											<p>Unsupported file type.</p>
										@endif
									</div>
								</div>
							</div>
						</div>
						@endforeach
					</div>
				@endif

				</div>
			</div>
	</div>
</div>                   
<div class="modal" id="customerDetailsModal" style="z-index: 9999;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerDetailsModalLabel">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="customerModaltable" class="table table-striped table-bordered dt-responsive nowrap accounts-table" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Load Number</th>
                                <th>Customer Name</th>
                                <th>Invoice No.</th>
                                <th>Invoice Date</th>
                                <th>Agent</th>
                                <th>Customer Payment</th>
                                <th>Aging Days</th>
                            </tr>
                        </thead>
                        <tbody>
                              @foreach($loadDatacustomeraging as $data)
                              
                            <tr>
                              <td>{{$data['load_number']}}</td>
                              <td>{{$data['load_bill_to']}}</td>
                              <td>{{$data['invoice_number']}}</td>
                              <td>{{$data['invoice_date']}}</td>
                              <td>{{$data['agent']}}</td>
                              <td>{{$data['customer_payment']}}</td>
                              <td>{{ abs((int)$data['aging_days']) }}</td>
                                 </tr>
                              
                              @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="thirtydaysaging" tabindex="-1" aria-labelledby="thirtydaysagingModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">30 Days Aging</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-bs-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="thirtydaysagingtable" class="table table-striped table-bordered dt-responsive nowrap accounts-table">
                        <thead>
                            <tr>
                                <th>Load Number</th>
                                <th>Customer Name</th>
                                <th>Invoice No.</th>
                                <th>Invoice Date</th>
                                <th>Agent</th>
                                <th>Customer Payment</th>
                                <th>Aging Days</th>
                            </tr>
                        </thead>
                        <tbody id="thirtydaysaging-body">
                            @foreach($loadDataabove30days as $data)
                                 <tr>
                                    <td>{{$data['load_number']}}</td>
                                    <td>{{$data['load_bill_to']}}</td>
                                    <td>{{$data['invoice_number']}}</td>
                                    <td>{{$data['invoice_date']}}</td>
                                    <td>{{$data['agent']}}</td>
                                    <td>{{$data['customer_payment']}}</td>
                                    <td>{{ abs((int)$data['aging_days']) }}</td>
                                 </tr>
                              @endforeach
							
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="paymentmarkedlist" tabindex="-1" aria-labelledby="paymentmarkedlistModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-lg" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Mark List</h5>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                   
                    <table id="reciving-payment" class="table table-striped table-bordered dt-responsive nowrap accounts-table" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>payment Mark Date</th>
                            </tr>
                        </thead>
                        <tbody>
                           @foreach($dailyInvoiceTotals as $amounts)
                           @php
                                
                                @endphp
                              <tr>
                                <td>{{$amounts->total_amount}}</td>
                                <td>{{$amounts->date}}</td>
                              </tr>
                           @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
               <span>Total Payment Received : <strong>${{$receiving_amount}}</strong></span> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assigned-credit" tabindex="-1" aria-labelledby="assignedcreditModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-lg" style="max-width: 800px;">
            <div class="modal-content">
                                 <!-- Modal Header -->
                                 <div class="modal-header" style="padding-left: 14px;">
                                    <h4 class="modal-title">Add Credit Limit</h4>
                                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                                 </div>

                                 <!-- Modal Body -->
                                 @php
                                    // Decode the credit limit log from the customer
                                    $credit_log = json_decode($customer->credit_limit_log, true) ?? [];
                                                if (empty($credit_log)) {
                                                    $credit_log = json_decode($customer->remaining_credit_logs, true) ?? [];
                                                }
                                    $adv_customer_credit_limit = $customer->adv_customer_credit_limit ?? null;
                                 @endphp

                                 <div class="modal-body">
                                    <!-- Existing Credit Logs -->
                                    <div class="row form-row credit-log-entry">
                                          <div class="col-md-4">
                                             <div class="form-group">
                                                <label class="mb-0">Credit Limit</label>
                                             </div>
                                          </div>
                                          <div class="col-md-5">
                                             <div class="form-group">
                                                <label class="mb-0">Time & Date</label>
                                             </div>
                                          </div>
                                          <div class="col-md-3">
                                             <div class="form-group">
                                                <label class="mb-0">User</label>
                                             </div>
                                          </div>
                                       </div>
                                       @if (!empty($credit_log))
                                          @foreach ($credit_log as $key => $credit)
                                             <div class="row form-row credit-log-entry" data-index="{{ $key }}" data-credit-log-user="{{ $credit['user_id'] ?? 'legacy' }}">
                                                   <div class="col-md-4">
                                                      <div class="form-group">
                                                         <input type="number" 
                                                                  class="form-control credit-limit" 
                                                                  readonly 
                                                                  value="{{ !empty($credit['credit_limit']) ? $credit['credit_limit'] : $customer->adv_customer_credit_limit }}" 
                                                                  placeholder="Enter credit limit">
                                                      </div>
                                                   </div>
                                                   <div class="col-md-4">
                                                      <div class="form-group">
                                                         <input type="datetime-local" 
                                                                  class="form-control credit-time" 
                                                                  readonly 
                                                                  value="{{ $credit['credit_time'] }}">
                                                      </div>
                                                   </div>
                                                   <div class="col-md-3">
                                                      <div class="form-group">
                                                         <input type="text" class="form-control" readonly
                                                                  value="{{ $credit['user_name'] ?? ($creditLogUsers->get((string) ($credit['user_id'] ?? '')) ?? (($credit['user_id'] ?? null) ? 'User ' . $credit['user_id'] : 'Legacy / Unassigned')) }}">
                                                      </div>
                                                   </div>
                                                   <div class="col-md-1">
                                                      <div class="form-group">
                                                         <a href="javascript:void(0);" class="edit-field" data-index="{{ $key }}">
                                                               <i class="fa fa-edit" style="margin-top: 11px;"></i>
                                                         </a>
                                                      </div>
                                                   </div>
                                             </div>
                                          @endforeach
                                       @endif


                                    <!-- Add New Credit Limit -->
                                    <div class="row form-row new-credit-log-entry">
                                       <div class="col-md-4">
                                          <div class="form-group">
                                             <input type="number" class="form-control credit-limit" placeholder="Enter credit limit" name="new_credit_limit[]">
                                          </div>
                                       </div>
                                       <div class="col-md-4">
                                          <div class="form-group">
                                             <input type="datetime-local" class="form-control" name="new_credit_time[]" readonly value="{{ now()->format('Y-m-d\TH:i') }}">
                                          </div>
                                       </div>
                                       <div class="col-md-1">
                                          <div class="form-group">
                                             <i class="fa fa-edit" style="margin-top: 11px;"></i>
                                          </div>
                                       </div>
                                    </div>

                                    <!-- Add More Button -->
                                 </div>
                                 <div class="text-center mt-1">
                                    <button type="button" class="btn btn-success" id="addMoreLimit" style="font-size: 14px; padding: 8px 20px;">Add more limit</button>
                                 </div>
                </div>
    </div>
</div>


<div class="modal fade" id="assigned-credit-remaing" tabindex="-1" aria-labelledby="assignedcreditremaingModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-lg" style="max-width: 800px;">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header" style="padding-left: 14px;">
            <h4 class="modal-title">Set Remaining Credit Limit</h4>
            <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>

            <!-- Modal Body -->
            @php
            $credit_log = json_decode($customer->remaining_credit_logs, true);
            $credit_log = is_array($credit_log)
                ? array_values(array_filter($credit_log, function ($credit) {
                    return is_array($credit) && is_numeric($credit['credit_limit'] ?? null);
                }))
                : [];

            if (empty($credit_log) && (float) ($customer->remaining_credit ?? 0) > 0) {
                $credit_log[] = [
                    'credit_limit' => $customer->remaining_credit,
                    'credit_time' => optional($customer->updated_at)->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i'),
                ];
            }
            @endphp

            <div class="modal-body modal-assigned-credit-remaing">
                            <div class="row form-row new-remaning-credit-log-entry">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="number" class="form-control remaining-credit-limit" step="any" placeholder="Enter Remaining credit limit" name="new_remaing_credit_limit[]">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <input type="datetime-local" class="form-control" name="new_remaing_credit_time[]" readonly value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
            </div>
            <!-- Existing Credit Logs -->
            <div class="row form-row credit-log-entry">
                    <div class="col-md-4">
                        <div class="form-group">
                        <label class="mb-0">Remaining Credit Limit</label>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                        <label class="mb-0">Time & Date</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                        <label class="mb-0">User</label>
                        </div>
                    </div>
                </div>
                @if (!empty($credit_log))
                    @foreach ($credit_log as $key => $credit)
                        <div class="row form-row credit-log-entry" data-index="{{ $key }}" data-credit-log-user="{{ $credit['user_id'] ?? 'legacy' }}">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="number" name=""
                                            class="form-control remaining-credit-limit" 
                                            readonly 
                                            value="{{ !empty($credit['credit_limit']) ? $credit['credit_limit'] : '' }}" 
                                            placeholder="Enter Remaing credit limit">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="datetime-local" 
                                            class="form-control remaining-credit-time" 
                                            readonly 
                                            value="{{ $credit['credit_time'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="text" class="form-control" readonly
                                            value="{{ $credit['user_name'] ?? ($creditLogUsers->get((string) ($credit['user_id'] ?? '')) ?? (($credit['user_id'] ?? null) ? 'User ' . $credit['user_id'] : 'Legacy / Unassigned')) }}">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <a href="javascript:void(0);" class="edit-field" data-index="{{ $key }}">
                                        <i class="fa fa-edit" style="margin-top: 11px;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @else
                    <div class="row form-row credit-log-entry">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="number" 
                                            class="form-control remaining-credit-limit" 
                                            readonly name="" step="any" 
                                            value="{{ $customer->remaining_credit }}" 
                                            placeholder="Enter Remaing credit limit">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <input type="datetime-local" 
                                            class="form-control remaining-credit-time" 
                                            name="new_remaing_credit_time[]"
                                            readonly 
                                            value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                            
                        </div>

                @endif 


            <!-- Add New Credit Limit -->
            <!-- <div class="row form-row new-remaning-credit-log-entry">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="number" class="form-control remaining-credit-limit" step="any" placeholder="Enter Remaing credit limit" name="new_remaing_credit_limit[]">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <input type="datetime-local" class="form-control" name="new_remaing_credit_time[]" readonly value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
            </div> -->

            <!-- Add More Button -->
            </div>
            <div class="text-center mt-1">
            <button type="button" class="btn btn-success" id="addMoreremaingLimit" style="font-size: 14px; padding: 8px 20px;">Add more limit</button>
            </div>
        </div>
    </div>
</div>


                  </form>

               </div>
            </div>
         </div>
      </div>
   </div>
</section>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
$(document).ready(function () {
$('#customerModaltable, #thirtydaysagingtable').DataTable({
			responsive: true,
			dom: 'Bfrtip',
			pageLength: 200, 
			buttons: ['copy', 'excel', 'pdf', 'colvis'],
			searching: true,
			paging: false,
		   
		});
$('#reciving-payment').DataTable({
			responsive: true,
			dom: 'Bfrtip',
			pageLength: 200, 
			buttons: ['copy', 'excel', 'pdf', 'colvis'],
			searching: true,
			paging: false,
			order: [[1, 'desc']],
		   
		});
	});
</script>
<script>
$(document).ready(function () {
    // Initialize Select2 once
    $('#country').select2({ width: '100%', dropdownParent: $('body') });
    $('#state').select2({ width: '100%', dropdownParent: $('body') });

    // Handle change event
    $('#country').on('change', function () {
        let countryId = $(this).find('option:selected').data('id');

        if (countryId) {
            $.ajax({
                url: '/account/get-states/' + countryId, // Adjust route if needed
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    $('#state').empty().prop('disabled', false);

                    // Add default placeholder
                    $('#state').append('<option value="">Choose State</option>');

                    // Assuming `data` is an array of objects like [{id: 1, name: 'Texas'}, ...]
                        $('#state').append(
                            $(data)
                        );
                  
                    // Refresh Select2
                    $('#state').trigger('change');
                }
            });
        } else {
            $('#state').empty().append('<option value="">Choose State</option>');
        }
    });
}); 
</script>
<script>
   $(document).ready(function() {
      $('#addComment').click(function() {
         var html = '<div class="col-md-12">';
         html += '<div class="form-group">';
         html += '<label>Commenter\'s Name</label>';
         html += '<select style="font-family: \'Poppins\', sans-serif;font-weight: 400;font-size: 9px;line-height: 0.2em;color: #666;width: 100%;height:30px;padding: 0px 0 0 10px;" class="form-control" required name="commenter_name[]" id="commenter_name">';
         html += '<option value="Please Select">Please Select</option>';
         html += '<option value="Adam Smith">Adam Smith</option>';
         html += '<option value="Amren">Amren</option>';
         html += '</select>';
         html += '</div>';
         html += '</div>';
         html += '<div class="col-md-12">';
         html += '<div class="form-group">';
         html += '<label>Comment</label>';
         html +=
            '<textarea name="comment_notes[]" class="form-control" cols="60" rows="5"></textarea>';
         html += '</div>';
         html += '</div>';

         $('#commentFields').append(html);
      });
   });
</script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const editIcons = document.querySelectorAll('.edit-field');

    editIcons.forEach(icon => {
        icon.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Find the parent row
            const parentRow = this.closest('.form-row');
            const index = this.getAttribute('data-index');
            
            // Find inputs in the same row
            const creditLimitInput = parentRow.querySelector('.credit-limit');
            const creditTimeInput = parentRow.querySelector('.credit-time');
            
            // Remove readonly attributes
            creditLimitInput.removeAttribute('readonly');
            creditTimeInput.removeAttribute('readonly');

            // Focus on the first input field
            creditLimitInput.focus();

            // Save changes on blur or pressing Enter
            creditLimitInput.addEventListener('blur', function () {
                saveChanges(index, creditLimitInput.value, creditTimeInput.value);
            });

            creditTimeInput.addEventListener('blur', function () {
                saveChanges(index, creditLimitInput.value, creditTimeInput.value);
            });
        });
    });

    function saveChanges(index, creditLimit, creditTime) {
    fetch('/save-credit-log', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            index: index,
            credit_limit: creditLimit,
            credit_time: creditTime
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Changes saved successfully!');
            // Make fields readonly again
            document.querySelector(`.form-row[data-index="${index}"] .credit-limit`).setAttribute('readonly', true);
            document.querySelector(`.form-row[data-index="${index}"] .credit-time`).setAttribute('readonly', true);
        } else {
            alert(data.message || 'Failed to save changes.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.errors ? JSON.stringify(error.errors) : 'An unexpected error occurred.');
    });
}
});

</script>


<script>
   $(document).ready(function () {
      let originalUsedAmount = parseFloat($('#used_amount').val()) || 0;

      // Function to get the current date and time
      function getCurrentDateTime() {
         const now = new Date();
         return now.toISOString().slice(0, 16);
      }

      // Function to update calculations
      function updateCreditCalculations() {
         let totalCreditLimit = 0;

         // Sum all credit limits
         $('.credit-limit').each(function () {
            const value = parseFloat($(this).val());
            if (!isNaN(value)) {
                  totalCreditLimit += value;
            }
         });

         // Update Assigned Credit Limit
         $('#adv_customer_credit_limit').val(totalCreditLimit.toFixed(2));

      }

     
      
         // Function to update calculations
         function updateremaingCreditCalculations() {
                const originalRemainingCredit = parseFloat($('#remaining_credit_new').val()) || 0;
                let newRemainingCredit = 0;

                $('.new-remaning-credit-log-entry .remaining-credit-limit').each(function () {
                    const value = parseFloat($(this).val());
                    if (!isNaN(value)) {
                        newRemainingCredit += value;
                    }
                });

                $('#remaining_credit').val(Math.max(0, originalRemainingCredit + newRemainingCredit).toFixed(2));
         }
		 
		 
      // Add more credit limit rows dynamically
      $('#addMoreLimit').click(function () {
         const newRow = `
            <div class="row form-row">
               <div class="col-md-6">
                  <div class="form-group">
                     <input type="number" name="new_credit_limit[]" class="form-control credit-limit" placeholder="Enter credit limit">
                  </div>
               </div>
               <div class="col-md-5">
                  <div class="form-group">
                     <input type="datetime-local" name="new_credit_time[]" class="form-control" readonly value="${getCurrentDateTime()}">
                  </div>
               </div>
               <div class="col-md-1">
                  <div class="form-group">
                     <i class="fa fa-trash text-danger delete-row" style="cursor:pointer; margin-top: 8px;"></i>
                  </div>
               </div>
            </div>`;
         $('#assigned-credit .modal-body').append(newRow);
      });

      // Update calculations when a credit-limit field is changed
      $(document).on('input', '.credit-limit', updateCreditCalculations);

      

      // Delete dynamically added rows
      $(document).on('click', '.delete-row', function () {
         $(this).closest('.form-row').remove();
         updateCreditCalculations();
      });


     
      // Add more remaing credit limit rows dynamically
      $('#addMoreremaingLimit').click(function () {
         const newRow = `
            <div class="row form-row new-remaning-credit-log-entry">
               <div class="col-md-6">
                  <div class="form-group">
                     <input type="number" name="new_remaing_credit_limit[]" class="form-control remaining-credit-limit" step="any" placeholder="Enter Remaing credit limit">
                  </div>
               </div>
               <div class="col-md-5">
                  <div class="form-group">
                     <input type="datetime-local" name="new_remaing_credit_time[]" class="form-control" readonly value="${getCurrentDateTime()}">
                  </div>
               </div>
            </div>`;
         $('.modal-assigned-credit-remaing').append(newRow);
      });

      $(document).on('input', '.remaining-credit-limit', updateremaingCreditCalculations);


       // Function to update calculations
         function updateinvoiceCreditCalculations() {

            let totalCredit = 0;

            // Loop through all inputs with class 'invoice-credit-limit' and sum their values
            $('.invoice-credit-limit').each(function () {
                  let value = parseFloat($(this).val());
                  if (!isNaN(value)) {
                     totalCredit += value;
                  }
            });
 

            // Get remaining credit from server-side variable (make sure this renders correctly)
            let existingRemainingCredit = parseFloat('{{ $customer->invoice_credit_limit ?? 0 }}') || 0;

            // Calculate remaining credit
            let remainingCredit = !isNaN(totalCredit) ? totalCredit + existingRemainingCredit : existingRemainingCredit;

            // Update remaining credit field
            $('#invoice_credit_limit').val(remainingCredit.toFixed(2));

         }

         $(document).on('input', '.invoice-credit-limit', updateinvoiceCreditCalculations);


   });
</script>
<script>
   // Add More Credit Limit functionality
   document.getElementById('addMoreinvoiceLimit').addEventListener('click', function () {
      const newEntry = document.querySelector('.invoice-credit-log-entry').cloneNode(true);
      newEntry.querySelectorAll('input').forEach(input => input.value = '');
      document.querySelector('.invoice-model-body').appendChild(newEntry);
   });
</script>
<script>
$(document).on('change', '.remittance-upload', function (e) {
    const input = $(this);
    const customerId = input.data('customer-id');
    let files = e.target.files;
    let formData = new FormData();

    for (let i = 0; i < files.length; i++) {
        formData.append('remittance[]', files[i]);
    }

    formData.append('customer_id', customerId);

    $.ajax({
        url: '/account/customer/upload-remittance',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: formData,
        processData: false,
        contentType: false,
        success: function () {
            alert('Upload successful');
            location.reload(); // This is fine to refresh the file list
        },
        error: function (xhr) {
            console.error(xhr.responseText);
        }
    });
});


// Load & show files
function showRemittanceFiles(customerId) {
    $('#remittanceAccordion').html('<div class="text-center p-3">Loading...</div>');

    $.ajax({
        url: '/account/customer/remittance/files',
        method: 'POST',
        data: {
            customer_id: customerId,
            _token: '{{ csrf_token() }}'
        },
        success: function (response) {
            let accordionContent = '';
            if (response.files.length === 0) {
                accordionContent = '<div class="text-center p-3">No files found.</div>';
            } else {
                accordionContent += `<div class="list-group">`;

            response.files.forEach((file, index) => {
                if (typeof file !== 'string' || !file) return; // Skip if file is not a valid string

                const fileName = file.split('/').pop();
                const collapseId = `collapseFile${index}`;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(fileExtension);
                const isPDF = fileExtension === 'pdf';

                accordionContent += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-link text-start w-100 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                                <i class="bi bi-caret-down-fill me-2"></i>${fileName}
                            </button>
                            <button class="btn btn-sm btn-danger ms-2" onclick="deleteRemittanceFile('${file}', ${customerId})">Delete</button>
                        </div>
                        <div class="collapse" id="${collapseId}">
                            <div class="mt-2 ps-3">
                                ${isImage ? `<img src="/${file}" class="img-fluid" style="max-height: 300px;">` : ''}
                                ${isPDF ? `<iframe src="/${file}" width="100%" height="400px" style="border:none;"></iframe>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });



                accordionContent += `</div>`;
            }
            $('#remittanceAccordion').html(accordionContent);
            $('#remittanceModal').modal('show');
        },
        error: function () {
            $('#remittanceAccordion').html('<div class="text-center p-3 text-danger">Failed to load files.</div>');
        }
    });
}


// Delete file
function deleteRemittanceFile(filePath, customerId) {
    if (!confirm("Are you sure you want to delete this file?")) return;

    $.ajax({
        url: '/account/customer/remittance/delete',
        method: 'POST',
        data: {
            file: filePath,
            customer_id: customerId,
            _token: '{{ csrf_token() }}'
        },
        success: function () {
            // Refresh file list
            showRemittanceFiles(customerId);

            // Also remove the "View Remittance Files" button if no files remain
            $.post('/customer/remittance/files', {
                customer_id: customerId,
                _token: '{{ csrf_token() }}'
            }, function (res) {
                if (res.files.length === 0) {
                    $(`#view-button-${customerId}`).parent().html('<td>No Remittance File</td>');
                }
            });
        },
        error: function () {
            alert('Delete failed.');
        }
    });
}


function showRemittanceFiles(customerId) {
    $('#remittanceAccordion').html('<div class="text-center p-3">Loading...</div>');
   
    const range = document.getElementById('filterRange')?.value || 'all';
    const specificDate = document.getElementById('filterDate')?.value || '';

    let startDate = '';
    let endDate = '';

    const now = new Date();
    if (range !== 'all') {
        switch (range) {
            case '1m':
                startDate = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                break;
            case '3m':
                startDate = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
                break;
            case '6m':
                startDate = new Date(now.getFullYear(), now.getMonth() - 6, now.getDate());
                break;
            case '1y':
                startDate = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
                break;
            case '2y':
                startDate = new Date(now.getFullYear() - 2, now.getMonth(), now.getDate());
                break;
            case 'last_year':
                startDate = new Date(now.getFullYear() - 1, 0, 1);
                endDate = new Date(now.getFullYear() - 1, 11, 31);
                break;
        }

        // Convert to YYYY-MM-DD
        const formatDate = (d) => d.toISOString().split('T')[0];
        if (startDate) startDate = formatDate(startDate);
        if (endDate) endDate = formatDate(endDate);
    }

    $.ajax({
        url: '/account/customer/remittance/filter',
        method: 'POST',
        data: {
            customer_id: customerId,
            start_date: startDate,
            end_date: endDate,
            specific_date: specificDate,
            _token: '{{ csrf_token() }}'
        },
        success: function (response) {
            let accordionContent = '';
            if (response.files.length === 0) {
                accordionContent = '<div class="text-center p-3">No files found.</div>';
            } else {
                accordionContent += `<div class="list-group">`;

                response.files.forEach((file, index) => {
                    const fileName = file.path.split('/').pop();
                    const collapseId = `collapseFile${index}`;
                    const fileExtension = fileName.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(fileExtension);
                    const isPDF = fileExtension === 'pdf';

                    accordionContent += `
                        <div class="list-group-item remittance-item" data-uploaded-at="${file.uploaded_at}">
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-link text-start w-100 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" aria-controls="${collapseId}">
                                    <i class="bi bi-caret-down-fill me-2"></i>${fileName}
                                    <small class="text-muted ms-2">(${file.uploaded_at})</small>
                                </button>
                            </div>
                            <div class="collapse" id="${collapseId}">
                                <div class="mt-2 ps-3">
                                    ${isImage ? `<img src="/public/${file.path}" class="img-fluid" style="max-height: 300px;">` : ''}
                                    ${isPDF ? `<iframe src="/public/${file.path}" width="100%" height="400px" style="border:none;"></iframe>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                accordionContent += `</div>`;
            }

            $('#remittanceAccordion').html(accordionContent);
            $('#remittanceModal').modal('show');
        },
        error: function () {
            $('#remittanceAccordion').html('<div class="text-center p-3 text-danger">Failed to load files.</div>');
        }
    });
}

</script>



<script>
function filterFiles() {
  const range = document.getElementById('filterRange').value;
  const specificDate = document.getElementById('filterDate').value;
  const fileItems = document.querySelectorAll('.remittance-item');

  const now = new Date();
  let rangeStart = null;
  let rangeEnd = null;

  switch (range) {
    case '1m':
      rangeStart = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
      break;
    case '3m':
      rangeStart = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
      break;
    case '6m':
      rangeStart = new Date(now.getFullYear(), now.getMonth() - 6, now.getDate());
      break;
    case '1y':
      rangeStart = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
      break;
    case '2y':
      rangeStart = new Date(now.getFullYear() - 2, now.getMonth(), now.getDate());
      break;
    case 'last_year':
      rangeStart = new Date(now.getFullYear() - 1, 0, 1); // Jan 1 last year
      rangeEnd = new Date(now.getFullYear() - 1, 11, 31); // Dec 31 last year
      break;
    case 'all':
    default:
      rangeStart = null;
      rangeEnd = null;
  }

  fileItems.forEach(item => {
    const uploadDateStr = item.getAttribute('data-uploaded-at')?.split(' ')[0];
    if (!uploadDateStr) return;

    const uploadDate = new Date(uploadDateStr);
    let show = true;

    // Filter by specific date
    if (specificDate) {
      const selectedDate = new Date(specificDate);
      show = uploadDate.toDateString() === selectedDate.toDateString();
    } else if (range !== 'all') {
      if (range === 'last_year') {
        if (uploadDate < rangeStart || uploadDate > rangeEnd) {
          show = false;
        }
      } else if (rangeStart && uploadDate < rangeStart) {
        show = false;
      }
    }

    item.style.display = show ? '' : 'none';
  });
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('filterDate').addEventListener('change', filterFiles);
  document.getElementById('filterRange').addEventListener('change', filterFiles);
});
</script>
<script>
$(document).ready(function () {

    $('#pre_payment').on('change', function () {

        let customer_id = $(this).data('id');
        let value = $(this).is(':checked') ? 1 : 0;

        // Change label instantly
        $('#pre_payment_label').text(value ? 'Pre-Payment' : 'Not Applicable');

        $.ajax({
            url: "{{ route('customer.update.prepayment') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                customer_id: customer_id,
                pre_payment: value
            },
            success: function (response) {
                console.log('Saved');
            },
            error: function () {
                alert('Something went wrong!');
            }
        });

    });

});
</script>

<script>
$(function () {
    const originalCreditValues = {
        assigned: $('#adv_customer_credit_limit').val(),
        remaining: $('#remaining_credit').val(),
        invoice: $('#invoice_credit_limit').val(),
        exhausted: $('#total_exhausted_limit').val(),
        paymentReceived: $('#total_payment_received').val(),
        total: $('#total_credit_limit').val(),
        totalInvoice: $('#total_invoice_credit_limit').val(),
        combined: $('#total_combined_credit_limit').val()
    };

    $('#credit_log_user_filter').on('change', function () {
        const selectedUser = $(this).val();

        $('[data-credit-log-user]').each(function () {
            const rowUser = String($(this).attr('data-credit-log-user'));
            $(this).toggle(selectedUser === 'all' || rowUser === selectedUser);
        });

        $('[data-history-user]').each(function () {
            const rowUser = String($(this).attr('data-history-user'));
            $(this).toggle(selectedUser === 'all' || rowUser === selectedUser);
        });

        if (selectedUser === 'all') {
            $('#adv_customer_credit_limit').val(originalCreditValues.assigned);
            $('#remaining_credit').val(originalCreditValues.remaining);
            $('#remaining_credit_new').val(originalCreditValues.remaining);
            $('#invoice_credit_limit').val(originalCreditValues.invoice);
            $('#total_exhausted_limit').val(originalCreditValues.exhausted);
            $('#total_payment_received').val(originalCreditValues.paymentReceived);
            $('#total_credit_limit').val(originalCreditValues.total);
            $('#total_invoice_credit_limit').val(originalCreditValues.totalInvoice);
            $('#total_combined_credit_limit').val(originalCreditValues.combined);
            return;
        }

        let assigned = 0;
        let remaining = 0;
        let invoice = 0;
        let exhausted = 0;
        let paymentReceived = 0;
        let matchedCreditRows = 0;
        $('[data-history-user][data-credit-type]').each(function () {
            if (String($(this).attr('data-history-user')) !== selectedUser) {
                return;
            }

            matchedCreditRows++;
            const amount = parseFloat($(this).attr('data-credit-amount')) || 0;
            const type = $(this).attr('data-credit-type');
            if (type === 'Assigned Credit') assigned += amount;
            if (type === 'Remaining Credit') remaining += amount;
            if (type === 'Invoice Credit') invoice += amount;
        });

        if (matchedCreditRows === 0) {
            assigned = parseFloat(originalCreditValues.total) || parseFloat(originalCreditValues.assigned) || 0;
            remaining = parseFloat(originalCreditValues.remaining) || 0;
            invoice = parseFloat(originalCreditValues.totalInvoice) || parseFloat(originalCreditValues.invoice) || 0;
        }

        $('[data-history-user][data-load-amount]').each(function () {
            if (String($(this).attr('data-history-user')) !== selectedUser) {
                return;
            }

            const loadAmount = parseFloat($(this).attr('data-load-amount')) || 0;
            const receivedAmount = parseFloat($(this).attr('data-received-amount')) || 0;
            exhausted += Math.max(0, loadAmount - receivedAmount);
            paymentReceived += receivedAmount;
        });

        $('#adv_customer_credit_limit').val(assigned.toFixed(2));
        $('#remaining_credit').val(remaining.toFixed(2));
        $('#remaining_credit_new').val(remaining.toFixed(2));
        $('#invoice_credit_limit').val(invoice.toFixed(2));
        $('#total_exhausted_limit').val(exhausted.toFixed(2));
        $('#total_payment_received').val(paymentReceived.toFixed(2));
        $('#total_credit_limit').val(assigned.toFixed(2));
        $('#total_invoice_credit_limit').val(invoice.toFixed(2));
        $('#total_combined_credit_limit').val((assigned + invoice).toFixed(2));
    });
});
</script>

@endsection
