<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\VanSalesVehicle;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VanSalesVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $vehicles = VanSalesVehicle::where('business_id', $business_id)
                        ->select(['id', 'name', 'plate_number', 'notes', 'is_active']);

            return Datatables::of($vehicles)
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\VanSalesVehicleController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="fas fa-edit"></i> '.__('messages.edit').'</button>';
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label label-success">'.__('van_sales.active').'</span>'
                        : '<span class="label label-danger">'.__('lang_v1.inactive').'</span>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('van_sales.vehicle.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('van_sales.vehicle.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            $input = $request->only(['name', 'plate_number', 'notes']);

            // A vehicle needs its own business_locations row so it can carry
            // stock and have a cash register — copy address fields (required,
            // non-nullable) and invoice scheme/layout (required FKs, no
            // sensible default of their own) from an existing location so a
            // vehicle isn't left with meaningless address data or dangling
            // foreign keys.
            $reference_location = BusinessLocation::where('business_id', $business_id)->first();
            $default_invoice_scheme = InvoiceScheme::getDefault($business_id);
            $default_invoice_layout = InvoiceLayout::where('business_id', $business_id)->first();

            $location = BusinessLocation::create([
                'business_id' => $business_id,
                'name' => __('van_sales.vehicle_location_name', ['name' => $input['name']]),
                'country' => $reference_location->country ?? 'N/A',
                'state' => $reference_location->state ?? 'N/A',
                'city' => $reference_location->city ?? 'N/A',
                'zip_code' => $reference_location->zip_code ?? '0000000',
                'invoice_scheme_id' => $reference_location->invoice_scheme_id ?? optional($default_invoice_scheme)->id,
                'invoice_layout_id' => $reference_location->invoice_layout_id ?? optional($default_invoice_layout)->id,
                // pos.js reads location.default_payment_accounts.<method>.account
                // unconditionally at checkout — without this, completing any
                // sale at the vehicle's location throws a JS error.
                'default_payment_accounts' => $reference_location->default_payment_accounts ?? json_encode([
                    'cash' => ['is_enabled' => 1, 'account' => null],
                    'card' => ['is_enabled' => 1, 'account' => null],
                    'cheque' => ['is_enabled' => 1, 'account' => null],
                    'bank_transfer' => ['is_enabled' => 1, 'account' => null],
                    'other' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_1' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_2' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_3' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_4' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_5' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_6' => ['is_enabled' => 1, 'account' => null],
                    'custom_pay_7' => ['is_enabled' => 1, 'account' => null],
                ]),
            ]);

            $vehicle = VanSalesVehicle::create([
                'business_id' => $business_id,
                'location_id' => $location->id,
                'name' => $input['name'],
                'plate_number' => $input['plate_number'],
                'notes' => $input['notes'],
                'is_active' => 1,
            ]);

            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $vehicle = VanSalesVehicle::where('business_id', $business_id)->findOrFail($id);

            return view('van_sales.vehicle.edit')
                ->with(compact('vehicle'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'plate_number', 'notes']);
                $input['is_active'] = $request->input('is_active') ? 1 : 0;

                $vehicle = VanSalesVehicle::where('business_id', $business_id)->findOrFail($id);
                $vehicle->update($input);

                $output = ['success' => true,
                    'msg' => __('lang_v1.updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
