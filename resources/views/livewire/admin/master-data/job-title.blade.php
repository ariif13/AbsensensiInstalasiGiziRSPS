<div>
  <div class="mb-4 flex-col items-center gap-5 sm:flex-row md:flex md:justify-between lg:mr-4">
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 md:mb-0">
      {{ __('Job Title Data') }}
    </h3>
    <x-button wire:click="showCreating" class="w-full sm:w-auto justify-center">
      <x-heroicon-o-plus class="mr-2 h-4 w-4" /> {{ __('Add Job Title') }}
    </x-button>
  </div>

  <!-- Mobile Card View -->
  <div class="grid grid-cols-1 gap-4 sm:hidden mb-4">
    @foreach ($jobTitles as $jobTitle)
      <div class="card p-4">
          <div class="flex justify-between items-start mb-3">
              <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $jobTitle->name }}</h4>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
              <x-secondary-button wire:click="edit({{ $jobTitle->id }})" class="justify-center px-2 py-1">
                  <x-heroicon-o-pencil class="w-4 h-4" />
              </x-secondary-button>
              <x-danger-button wire:click="confirmDeletion({{ $jobTitle->id }}, '{{ $jobTitle->name }}')" class="justify-center px-2 py-1">
                  <x-heroicon-o-trash class="w-4 h-4" />
              </x-danger-button>
          </div>
      </div>
    @endforeach
  </div>

  <div class="hidden sm:block overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ __('Name') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ __('Division') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            {{ __('Level / Rank') }}
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">{{ __('Actions') }}</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @foreach ($jobTitles as $jobTitle)
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ $jobTitle->name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $jobTitle->division->name ?? '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                @if($jobTitle->jobLevel)
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $jobTitle->jobLevel->rank == 1 ? 'bg-purple-100 text-purple-800' : ($jobTitle->jobLevel->rank == 2 ? 'bg-indigo-100 text-indigo-800' : ($jobTitle->jobLevel->rank == 3 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                        {{ $jobTitle->jobLevel->name }} ({{ $jobTitle->jobLevel->rank }})
                    </span>
                @else
                    -
                @endif
            </td>
            <td class="relative flex justify-end gap-2 px-6 py-4">
              <x-button wire:click="edit({{ $jobTitle->id }})" class="px-2 py-1">
                {{ __('Edit') }}
              </x-button>
              <x-danger-button wire:click="confirmDeletion({{ $jobTitle->id }}, '{{ $jobTitle->name }}')" class="px-2 py-1">
                {{ __('Delete') }}
              </x-danger-button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model="confirmingDeletion">
      <x-slot name="title">
        {{ __('Delete Job Title') }}
      </x-slot>

      <x-slot name="content">
        {{ __('Are you sure you want to delete the job title ') }} <span class="font-bold">{{ $deleteName }}</span>?
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
          {{ __('Delete') }}
        </x-danger-button>
      </x-slot>
    </x-confirmation-modal>

    <!-- Create/Edit Modal -->
    <x-dialog-modal wire:model="creating">
      <x-slot name="title">
        {{ __('Create Job Title') }}
      </x-slot>

      <x-slot name="content">
        <form wire:submit.prevent="create">
            <div class="col-span-6 sm:col-span-4">
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full" wire:model.defer="name" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-4">
                <x-label for="division_id" value="{{ __('Division') }}" />
                <select id="division_id" wire:model.defer="division_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('Select Division') }}</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>
                <x-input-error for="division_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-4">
                <x-label for="job_level_id" value="{{ __('Job Level') }}" />
                <select id="job_level_id" wire:model.defer="job_level_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('Select Level') }}</option>
                    @foreach($jobLevels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }} ({{ __('Rank') }} {{ $level->rank }})</option>
                    @endforeach
                </select>
                <x-input-error for="job_level_id" class="mt-2" />
                <p class="mt-1 text-xs text-gray-500">{{ __('Rank 1 is highest (Head), 4 is lowest (Staff). Approvals flow UP (4->3->2->1).') }}</p>
            </div>
        </form>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$set('creating', false)" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
          {{ __('Create') }}
        </x-button>
      </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="editing">
      <x-slot name="title">
        {{ __('Edit Job Title') }}
      </x-slot>

      <x-slot name="content">
        <form wire:submit.prevent="update">
            <div class="col-span-6 sm:col-span-4">
                <x-label for="edit_name" value="{{ __('Name') }}" />
                <x-input id="edit_name" type="text" class="mt-1 block w-full" wire:model.defer="name" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-4">
                <x-label for="edit_division_id" value="{{ __('Division') }}" />
                <select id="edit_division_id" wire:model.defer="division_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('Select Division') }}</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->name }}</option>
                    @endforeach
                </select>
                <x-input-error for="division_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-4">
                <x-label for="edit_job_level_id" value="{{ __('Job Level') }}" />
                <select id="edit_job_level_id" wire:model.defer="job_level_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('Select Level') }}</option>
                    @foreach($jobLevels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }} ({{ __('Rank') }} {{ $level->rank }})</option>
                    @endforeach
                </select>
                <x-input-error for="job_level_id" class="mt-2" />
            </div>
        </form>
      </x-slot>

      <x-slot name="footer">
        <x-secondary-button wire:click="$set('editing', false)" wire:loading.attr="disabled">
          {{ __('Cancel') }}
        </x-secondary-button>

        <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
          {{ __('Update') }}
        </x-button>
      </x-slot>
    </x-dialog-modal>
</div>
