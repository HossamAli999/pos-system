<?php

return [
    // Menu / general
    'van_sales' => 'Van Sales',
    'trips' => 'Trips',
    'all_trips' => 'All trips',
    'trip' => 'Trip',
    'trip_details' => 'Trip details',
    'vehicles' => 'Vehicles',
    'all_vehicles' => 'All vehicles',
    'pending_approvals' => 'Pending approvals',

    // Vehicle
    'add_vehicle' => 'Add vehicle',
    'edit_vehicle' => 'Edit vehicle',
    'name' => 'Name',
    'plate_number' => 'Plate number',
    'notes' => 'Notes',
    'active' => 'Active',
    'vehicle_location_name' => 'Vehicle: :name',

    // Trip
    'start_trip' => 'Start trip',
    'vehicle' => 'Vehicle',
    'rep' => 'Rep',
    'source_location' => 'Source location',
    'opening_cash' => 'Opening cash',
    'closing_cash' => 'Closing cash',
    'closed_at' => 'Closed at',
    'started_at' => 'Started at',
    'approved_by' => 'Approved by',
    'loadout_products' => 'Loadout products',
    'product' => 'Product',
    'quantity' => 'Quantity',
    'search_and_add_product' => 'Search and add a product',
    'no_products_yet' => 'No products added to the loadout yet.',
    'no_products_added' => 'Please add at least one product.',
    'trip_started_successfully' => 'Trip started successfully. Stock has been loaded onto the vehicle.',

    // Status
    'status_out' => 'Out',
    'status_pending_approval' => 'Pending approval',
    'status_closed' => 'Closed',
    'status_rejected' => 'Rejected',

    // Reconciliation
    'reconciliation' => 'Reconciliation',
    'qty_loaded' => 'Qty loaded',
    'qty_remaining' => 'Qty remaining (system)',
    'qty_sold' => 'Qty sold',
    'qty_returned_counted' => 'Qty returned (counted)',
    'counted_cash' => 'Counted cash',
    'rep_note' => 'Rep note',
    'manager_note' => 'Manager note',
    'submit_for_approval' => 'Submit for approval',
    'submitted_for_approval' => 'Submitted for approval. A manager will review and close the trip.',
    'rep_submitted_returns' => "Rep's submitted count",
    'approve' => 'Approve',
    'reject' => 'Reject',
    'trip_approved' => 'Trip approved and closed. Stock and cash have been reconciled.',
    'trip_rejected' => 'Trip sent back to the rep for recounting.',
    'awaiting_manager_approval' => 'Awaiting manager approval.',

    // Cash register
    'cash_register' => 'Cash register',
    'register_status' => 'Register status',
    'closing_amount' => 'Closing amount',
    'sales_during_trip' => 'Sales made during this trip',

    // Errors
    'rep_has_open_register' => 'This rep already has an open cash register / trip. Close it before starting a new one.',
    'trip_not_open' => 'This trip is not currently out — it cannot be submitted for closing.',
    'trip_not_pending' => 'This trip is not pending approval.',
    'closed_via_trip' => 'Closed via Van Sales trip #:id',
    'shortage_note' => 'Van Sales shortage/damage adjustment for trip #:id',

    // Permissions (role screen labels)
    'permission_access' => 'Start trips and sell during own trip',
    'permission_manage' => 'Manage vehicles and view all trips',
    'permission_approve' => 'Approve or reject trip closing requests',
];
