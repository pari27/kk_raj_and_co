<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmployeeDesignationRequest;
use App\Http\Requests\Admin\UpdateEmployeeDesignationRequest;
use App\Models\EmployeeDesignation;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeDesignationController extends Controller
{
    public function index(): View
    {
        $designations = EmployeeDesignation::query()
            ->where('is_deleted', false)
            ->with('employees')
            ->latest('created_at')
            ->get();

        return view('admin.designations.index', compact('designations'));
    }

    public function store(StoreEmployeeDesignationRequest $request): RedirectResponse
    {
        $designation = EmployeeDesignation::create([
            'name' => $request->validated()['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->log($designation, 'Created');

        return redirect()
            ->route('admin.designations.index')
            ->with('status', "\"{$designation->name}\" was created.");
    }

    public function update(UpdateEmployeeDesignationRequest $request, EmployeeDesignation $designation): RedirectResponse
    {
        $before = $designation->name;

        $designation->update([
            'name' => $request->validated()['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($before !== $designation->name) {
            $this->log($designation, 'Updated', "Name \"{$before}\" → \"{$designation->name}\"");
        }

        return redirect()
            ->route('admin.designations.index')
            ->with('status', "\"{$designation->name}\" was updated.");
    }

    public function toggleActive(EmployeeDesignation $designation): RedirectResponse
    {
        $designation->update(['is_active' => ! $designation->is_active]);

        $this->log($designation, $designation->is_active ? 'Activated' : 'Deactivated');

        return redirect()
            ->route('admin.designations.index')
            ->with('status', "\"{$designation->name}\" is now ".($designation->is_active ? 'active' : 'inactive').'.');
    }

    public function destroy(EmployeeDesignation $designation): RedirectResponse
    {
        if ($designation->employees()->exists()) {
            return redirect()
                ->route('admin.designations.index')
                ->with('error', "\"{$designation->name}\" has employees assigned and can't be deleted.");
        }

        $designation->is_deleted = true;
        $designation->save();

        $this->log($designation, 'Deleted');

        return redirect()
            ->route('admin.designations.index')
            ->with('status', "\"{$designation->name}\" was deleted.");
    }

    private function log(EmployeeDesignation $designation, string $action, ?string $details = null): void
    {
        AuditLogger::log(
            action: $action,
            module: 'Designation',
            recordLabel: $designation->name,
            recordUrl: route('admin.designations.index'),
            details: $details,
            subject: $designation,
        );
    }
}
