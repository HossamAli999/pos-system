<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CashRegister;
use App\User;
use App\Utils\CashRegisterUtil;
use App\Utils\VanSalesUtil;
use App\VanSalesTrip;
use App\VanSalesVehicle;
use App\Variation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VanSalesTripController extends Controller
{
    /**
     * @var VanSalesUtil
     */
    protected $vanSalesUtil;

    /**
     * @var CashRegisterUtil
     */
    protected $cashRegisterUtil;

    public function __construct(VanSalesUtil $vanSalesUtil, CashRegisterUtil $cashRegisterUtil)
    {
        $this->vanSalesUtil = $vanSalesUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('van_sales.access') && ! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $can_manage = auth()->user()->can('van_sales.manage');

        if (request()->ajax()) {
            $trips = VanSalesTrip::where('van_sales_trips.business_id', $business_id)
                ->leftjoin('van_sales_vehicles as v', 'v.id', '=', 'van_sales_trips.van_sales_vehicle_id')
                ->leftjoin('users as u', 'u.id', '=', 'van_sales_trips.rep_id')
                ->leftjoin('business_locations as bl', 'bl.id', '=', 'van_sales_trips.source_location_id')
                ->select(
                    'van_sales_trips.id',
                    'v.name as vehicle_name',
                    'u.first_name as rep_first_name',
                    'u.last_name as rep_last_name',
                    'bl.name as source_location_name',
                    'van_sales_trips.status',
                    'van_sales_trips.opening_cash',
                    'van_sales_trips.started_at',
                    'van_sales_trips.closed_at'
                );

            if (! $can_manage) {
                $trips->where('van_sales_trips.rep_id', auth()->user()->id);
            }

            if (! empty(request()->status)) {
                $trips->where('van_sales_trips.status', request()->status);
            }

            return Datatables::of($trips)
                ->addColumn('rep_name', function ($row) {
                    return trim($row->rep_first_name.' '.$row->rep_last_name);
                })
                ->editColumn('status', function ($row) {
                    $labels = [
                        'out' => 'label-info',
                        'pending_approval' => 'label-warning',
                        'closed' => 'label-success',
                        'rejected' => 'label-danger',
                    ];
                    $class = $labels[$row->status] ?? 'label-default';

                    return '<span class="label '.$class.'">'.__('van_sales.status_'.$row->status).'</span>';
                })
                ->editColumn('started_at', function ($row) {
                    return ! empty($row->started_at) ? $row->started_at->format('Y-m-d H:i') : '';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\VanSalesTripController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-primary"><i class="fas fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('van_sales.trip.index')->with(compact('can_manage'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('van_sales.access') && ! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $vehicles = VanSalesVehicle::forDropdown($business_id);
        $reps = User::allUsersDropdown($business_id, false);

        // Exclude vehicle locations from the "source warehouse" picker —
        // a vehicle's own location shouldn't be selectable as where its
        // load-out stock comes from.
        $vehicle_location_ids = VanSalesVehicle::where('business_id', $business_id)->pluck('location_id')->toArray();
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        foreach ($vehicle_location_ids as $vehicle_location_id) {
            unset($business_locations[$vehicle_location_id]);
        }

        return view('van_sales.trip.create')
            ->with(compact('vehicles', 'reps', 'business_locations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('van_sales.access') && ! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        try {
            $products = [];
            foreach ($request->input('products', []) as $product) {
                if (empty($product['product_id']) || empty($product['variation_id']) || empty($product['quantity'])) {
                    continue;
                }

                $variation = Variation::find($product['variation_id']);

                $products[] = [
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity' => $product['quantity'],
                    'unit_price' => optional($variation)->sell_price_inc_tax ?? 0,
                ];
            }

            if (empty($products)) {
                $output = ['success' => false, 'msg' => __('van_sales.no_products_added')];

                return redirect()->back()->withInput()->with('status', $output);
            }

            $trip = $this->vanSalesUtil->startTrip($business_id, $user_id, [
                'van_sales_vehicle_id' => $request->input('van_sales_vehicle_id'),
                'rep_id' => $request->input('rep_id'),
                'source_location_id' => $request->input('source_location_id'),
                'opening_cash' => $request->input('opening_cash'),
                'products' => $products,
            ]);

            $output = ['success' => true,
                'msg' => __('van_sales.trip_started_successfully'),
            ];

            return redirect()->action([\App\Http\Controllers\VanSalesTripController::class, 'show'], [$trip->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong'),
            ];

            return redirect()->back()->withInput()->with('status', $output);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('van_sales.access') && ! auth()->user()->can('van_sales.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $trip = VanSalesTrip::where('business_id', $business_id)
            ->with(['vehicle', 'rep', 'source_location', 'cash_register', 'return_lines.product'])
            ->findOrFail($id);

        if (! auth()->user()->can('van_sales.manage') && $trip->rep_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliation = $this->vanSalesUtil->getTripReconciliation($trip);

        $sales = $trip->sales()->with('contact')->get();

        $register_details = null;
        if (! empty($trip->cash_register_id)) {
            $register_details = $this->cashRegisterUtil->getRegisterDetails($trip->cash_register_id);
        }

        return view('van_sales.trip.show')
            ->with(compact('trip', 'reconciliation', 'sales', 'register_details'));
    }

    /**
     * Rep submits counted cash + counted returned quantities for manager review.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submitForApproval(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $trip = VanSalesTrip::where('business_id', $business_id)->findOrFail($id);

        if (! auth()->user()->can('van_sales.manage') && $trip->rep_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $return_lines = [];
            foreach ($request->input('returns', []) as $line) {
                $return_lines[] = [
                    'product_id' => $line['product_id'],
                    'variation_id' => $line['variation_id'],
                    'qty_returned' => $line['qty_returned'] ?? 0,
                ];
            }

            $this->vanSalesUtil->submitForApproval(
                $trip,
                $request->input('counted_cash', 0),
                $return_lines,
                $request->input('rep_note')
            );

            $output = ['success' => true, 'msg' => __('van_sales.submitted_for_approval')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Manager approves the closing request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, $id)
    {
        if (! auth()->user()->can('van_sales.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $trip = VanSalesTrip::where('business_id', $business_id)->findOrFail($id);

        try {
            $this->vanSalesUtil->approveTrip($trip, auth()->user()->id, $request->input('manager_note'));
            $output = ['success' => true, 'msg' => __('van_sales.trip_approved')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Manager rejects the closing request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, $id)
    {
        if (! auth()->user()->can('van_sales.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $trip = VanSalesTrip::where('business_id', $business_id)->findOrFail($id);

        try {
            $this->vanSalesUtil->rejectTrip($trip, auth()->user()->id, $request->input('manager_note'));
            $output = ['success' => true, 'msg' => __('van_sales.trip_rejected')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];
        }

        return redirect()->back()->with('status', $output);
    }
}
