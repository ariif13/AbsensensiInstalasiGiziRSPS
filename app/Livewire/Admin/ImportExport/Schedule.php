<?php

namespace App\Livewire\Admin\ImportExport;

use App\Exports\ScheduleTemplateExport;
use App\Imports\SchedulesImport;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Schedule extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public bool $previewing = false;
    public ?string $mode = null;
    public $file = null;
    public array $importErrors = [];
    public array $previewRows = [];
    public ?array $importResult = null;

    protected $rules = [
        'file' => 'required|mimes:csv,xls,xlsx,ods',
    ];

    public function mount()
    {
        if (\App\Helpers\Editions::reportingLocked()) {
            session()->flash('show-feature-lock', [
                'title' => 'Import/Export Locked',
                'message' => 'Schedule Import/Export is an Enterprise Feature 🔒. Please Upgrade.',
            ]);

            return redirect()->route('admin.dashboard');
        }
    }

    public function render()
    {
        if ($this->file) {
            $this->previewing = true;
            $this->mode = 'import';

            $previewImport = new SchedulesImport(save: false);
            Excel::import($previewImport, $this->file);

            $this->previewRows = $previewImport->previewRows;
            $this->importErrors = $previewImport->importErrors;
        } else {
            $this->previewing = false;
            $this->mode = null;
            $this->previewRows = [];
            $this->importErrors = [];
        }

        return view('livewire.admin.import-export.schedule', [
            'shifts' => Shift::query()->orderBy('start_time')->get(),
        ]);
    }

    public function import()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        if (\App\Helpers\Editions::reportingLocked()) {
            $this->dispatch('feature-lock', title: 'Import Locked', message: 'Importing Schedule is an Enterprise Feature 🔒. Please Upgrade.');
            return;
        }

        $this->validate();

        try {
            $importer = new SchedulesImport(save: true);
            Excel::import($importer, $this->file);

            $this->importErrors = $importer->importErrors;
            $this->importResult = [
                'imported' => $importer->importedCount,
                'skipped' => $importer->skippedCount,
            ];

            if ($importer->importedCount > 0) {
                $this->banner(__('Schedule import completed successfully.'));
            } else {
                $this->dangerBanner(__('No schedule rows were imported.'));
            }

            $this->file = null;
        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function downloadTemplate()
    {
        if (\App\Helpers\Editions::reportingLocked()) {
            $this->dispatch('feature-lock', title: 'Template Locked', message: 'Downloading Schedule Template is an Enterprise Feature 🔒. Please Upgrade.');
            return;
        }

        return Excel::download(new ScheduleTemplateExport, 'schedule_template.xlsx');
    }
}
