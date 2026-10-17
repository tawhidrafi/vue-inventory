<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->withCount('salesOrders')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('company_name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return CustomerResource::collection($query->get());
        }

        return CustomerResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(CustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer->loadCount('salesOrders'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer->loadCount('salesOrders'));
    }

    public function destroy(Customer $customer)
    {
        if ($customer->salesOrders()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a customer that has sales orders.',
            ], 422);
        }

        $customer->delete();

        return response()->noContent();
    }
}
