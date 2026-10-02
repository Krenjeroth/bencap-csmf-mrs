<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListServicesRequest;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Services admin screen. A user limited to one office (an Admin with an
 * assigned office) only sees and changes that office's services.
 */
class ServiceController extends Controller
{
    public function index(ListServicesRequest $request): AnonymousResourceCollection
    {
        [$column, $direction] = $request->sortColumn();
        $scope = $request->user()->scopedOfficeId();

        $services = Service::query()
            ->with(['office:id,code,name', 'serviceType:id,type'])
            ->when($scope, fn ($q, $officeId) => $q->where('office_id', $officeId))
            ->when($request->validated('office_id'), fn ($q, $officeId) => $q->where('office_id', $officeId))
            ->when($request->validated('service_type_id'), fn ($q, $typeId) => $q->where('service_type_id', $typeId))
            ->when($request->validated('charter_year'), fn ($q, $year) => $q->where('charter_year', $year))
            ->when($request->validated('status'), fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->when($request->search(), fn ($q, $term) => $q->where('name', 'like', $term))
            // Default order follows the charter: office order, then service order.
            ->when($column === 'sort_order',
                fn ($q) => $q->orderBy(Office::select('sort_order')->whereColumn('offices.id', 'services.office_id'), $direction),
                fn ($q) => $q->orderBy($column, $direction))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return ServiceResource::collection($services);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->assertOfficeAllowed($request->user(), (int) $data['office_id']);
        $data['charter_year'] ??= now()->year;
        $data['sort_order'] ??= (int) Service::where('office_id', $data['office_id'])->where('charter_year', $data['charter_year'])->max('sort_order') + 1;

        $service = Service::create($data);

        return (new ServiceResource($service->load(['office', 'serviceType'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Service $service): ServiceResource
    {
        $this->assertOfficeAllowed($request->user(), $service->office_id, notFound: true);

        return new ServiceResource($service->load(['office', 'serviceType']));
    }

    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        $this->assertOfficeAllowed($request->user(), $service->office_id, notFound: true);
        $data = $request->validated();
        if (isset($data['office_id'])) {
            $this->assertOfficeAllowed($request->user(), (int) $data['office_id']);
        }

        $service->fill($data)->save();

        return new ServiceResource($service->load(['office', 'serviceType']));
    }

    /** Only a service no feedback refers to can be deleted; otherwise deactivate it. */
    public function destroy(Request $request, Service $service): Response
    {
        $this->assertOfficeAllowed($request->user(), $service->office_id, notFound: true);

        try {
            $service->delete();
        } catch (QueryException $e) {
            // Foreign keys from feedback (Sprint 3) are RESTRICT.
            throw ValidationException::withMessages([
                'service' => 'Feedback already refers to this service. Deactivate it instead, so reports stay complete.',
            ]);
        }

        return response()->noContent();
    }

    /** Office-limited users may only work with their own office's services. */
    private function assertOfficeAllowed(User $user, int $officeId, bool $notFound = false): void
    {
        $scope = $user->scopedOfficeId();
        if ($scope !== null && $scope !== $officeId) {
            if ($notFound) {
                abort(404);
            }
            throw ValidationException::withMessages(['office_id' => 'You can only manage services of your own office.']);
        }
    }
}
