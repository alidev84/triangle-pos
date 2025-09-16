<?php

namespace Modules\People\Http\Controllers;

use Modules\People\DataTables\SuppliersDataTable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\People\Entities\Supplier;
use Illuminate\Support\Facades\App;

class SuppliersController extends Controller
{

    public function index(SuppliersDataTable $dataTable) {
        abort_if(Gate::denies('access_suppliers'), 403);

        return $dataTable->render('people::suppliers.index');
    }


    public function create() {
        abort_if(Gate::denies('create_suppliers'), 403);

        return view('people::suppliers.create');
    }


    public function store(Request $request) {
        abort_if(Gate::denies('create_suppliers'), 403);

        $request->validate([
            'supplier_name'  => 'required|string|max:255',
            'supplier_phone' => 'nullable|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'city'           => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:500',
        ]);

        Supplier::create([
            'supplier_name'  => $request->supplier_name,
            'supplier_phone' => $request->supplier_phone ?: '0300-0000000',
            'supplier_email' => $request->supplier_email ?: 'supplier@gmail.com',
            'city'           => $request->city ?: 'Rawalpindi',
            'country'        => $request->country ?: 'Pakistan',
            'address'        => $request->address ?: 'Rawalpindi, Pakistan'
        ]);

        toast('Supplier Created!', 'success');

        return redirect()->route('suppliers.index');
    }


    public function show(Supplier $supplier) {
        abort_if(Gate::denies('show_suppliers'), 403);
        
        // Load the payments relationship with the supplier
        $supplier->load(['payments' => function($query) {
            $query->latest('date');
        }]);
        
        // Load the purchases to calculate totals
        $supplier->load('purchases');
        
        return view('people::suppliers.show', compact('supplier'));
    }


    public function edit(Supplier $supplier) {
        abort_if(Gate::denies('edit_suppliers'), 403);

        return view('people::suppliers.edit', compact('supplier'));
    }


    public function update(Request $request, Supplier $supplier) {
        abort_if(Gate::denies('edit_suppliers'), 403);

        $request->validate([
            'supplier_name'  => 'required|string|max:255',
            'supplier_phone' => 'nullable|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'city'           => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:500',
        ]);

        $supplier->update([
            'supplier_name'  => $request->supplier_name,
            'supplier_phone' => $request->supplier_phone ?: '0300-0000000',
            'supplier_email' => $request->supplier_email ?: 'supplier@gmail.com',
            'city'           => $request->city ?: 'Rawalpindi',
            'country'        => $request->country ?: 'Pakistan',
            'address'        => $request->address ?: 'Rawalpindi, Pakistan'
        ]);

        toast('Supplier Updated!', 'info');

        return redirect()->route('suppliers.index');
    }


    public function destroy(Supplier $supplier) {
        abort_if(Gate::denies('delete_suppliers'), 403);

        $supplier->delete();

        toast('Supplier Deleted!', 'warning');

        return redirect()->route('suppliers.index');
    }
}
