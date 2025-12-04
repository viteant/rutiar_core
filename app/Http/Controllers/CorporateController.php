<?php

namespace App\Http\Controllers;

use App\Models\Corporate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('viewAny', Corporate::class);

        $query = Corporate::query()
            ->where('is_active', true);

        if (! $user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        } else {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        }

        $corporates = $query->get();

        return response()->json(['data' => $corporates]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', Corporate::class);

        $isSuperAdmin = $user->isSuperAdmin();

        $baseRules = [
            'name'          => ['required', 'string', 'max:150'],
            'tax_id'        => ['nullable', 'string', 'max:50'],
            'contact_name'  => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'max:150', 'email'],
            'is_active'     => ['sometimes', 'boolean'],
        ];

        if ($isSuperAdmin) {
            $rules = array_merge($baseRules, [
                'company_id' => ['required', 'integer', 'exists:companies,id'],
            ]);
        } else {
            $rules = $baseRules;
        }

        $validated = $request->validate($rules);

        if (! $isSuperAdmin) {
            $validated['company_id'] = $user->company_id;
        }

        $corporate = Corporate::create($validated);

        return response()->json(['data' => $corporate], 201);
    }

    public function show(Request $request, Corporate $corporate): JsonResponse
    {
        $this->authorize('view', $corporate);

        return response()->json(['data' => $corporate]);
    }

    public function update(Request $request, Corporate $corporate): JsonResponse
    {
        $this->authorize('update', $corporate);

        $rules = [
            'name'          => ['sometimes', 'required', 'string', 'max:150'],
            'tax_id'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_name'  => ['sometimes', 'nullable', 'string', 'max:150'],
            'contact_email' => ['sometimes', 'nullable', 'string', 'max:150', 'email'],
            'is_active'     => ['sometimes', 'boolean'],
        ];

        $validated = $request->validate($rules);

        $corporate->fill($validated);
        $corporate->save();

        return response()->json(['data' => $corporate]);
    }

    public function destroy(Request $request, Corporate $corporate): JsonResponse
    {
        $this->authorize('delete', $corporate);

        if ($corporate->is_active) {
            $corporate->is_active = false;
            $corporate->save();
        }

        return response()->json(null, 204);
    }
}
