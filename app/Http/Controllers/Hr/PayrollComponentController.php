<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\PayrollComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayrollComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $components = PayrollComponent::where('business_id', $business_id)
                ->select(['id', 'name', 'type', 'is_taxable']);

            return DataTables::of($components)
                ->editColumn('type', function ($row) {
                    return $row->type == 'earning' ? __('hr.earning') : __('hr.deduction');
                })
                ->editColumn('is_taxable', function ($row) {
                    return $row->is_taxable ? __('lang_v1.yes') : __('lang_v1.no');
                })
                ->addColumn('action', function ($row) {
                    return '<button data-href="'.action([\App\Http\Controllers\Hr\PayrollComponentController::class, 'edit'], [$row->id]).'" class="btn btn-xs btn-primary btn-modal" data-container=".payroll_component_modal"><i class="glyphicon glyphicon-edit"></i> '.__('messages.edit').'</button>
                        &nbsp;
                        <button data-href="'.action([\App\Http\Controllers\Hr\PayrollComponentController::class, 'destroy'], [$row->id]).'" class="btn btn-xs btn-danger delete_payroll_component_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr.payroll_components.index');
    }

    public function create()
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        return view('hr.payroll_components.create');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'type']);
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['is_taxable'] = $request->boolean('is_taxable');

            PayrollComponent::create($input);

            $output = ['success' => true, 'msg' => __('hr.payroll_component_added_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function edit($id)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $payroll_component = PayrollComponent::where('business_id', $business_id)->findOrFail($id);

            return view('hr.payroll_components.edit')->with(compact('payroll_component'));
        }
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $payroll_component = PayrollComponent::where('business_id', $business_id)->findOrFail($id);
            $payroll_component->fill($request->only(['name', 'type']));
            $payroll_component->is_taxable = $request->boolean('is_taxable');
            $payroll_component->save();

            $output = ['success' => true, 'msg' => __('hr.payroll_component_updated_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            PayrollComponent::where('business_id', $business_id)->findOrFail($id)->delete();

            $output = ['success' => true, 'msg' => __('hr.payroll_component_deleted_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }
}
