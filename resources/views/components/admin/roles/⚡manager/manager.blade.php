<div>
    <x-common.component-card title="Kelola Peran" desc="Atur izin apa saja yang dimiliki role Admin dan Operator.">
        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        <form wire:submit="save">
            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Izin</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Administrator</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Admin</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Operator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->permissionRows() as $key => $label)
                            <tr wire:key="permission-{{ $key }}">
                                <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</td>
                                <td class="px-5 py-3 text-center">
                                    <x-ui.badge color="success" size="sm">akses penuh</x-ui.badge>
                                </td>
                                @foreach ($this->editableRoles() as $role)
                                    <td class="px-5 py-3 text-center">
                                        <input type="checkbox" wire:model="checked.{{ $role }}.{{ $key }}"
                                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5" />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Locked row: shown for transparency, never editable — see
                            manager.php's docblock on PERMISSION_LABELS for why. --}}
                        <tr class="bg-gray-50/50 dark:bg-white/[0.02]">
                            <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">
                                Kelola Pengguna
                                <span class="ml-1 text-xs text-gray-400">(khusus Administrator, tidak bisa didelegasikan)</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <x-ui.badge color="success" size="sm">akses penuh</x-ui.badge>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="checkbox" disabled class="h-4 w-4 rounded border-gray-200 text-gray-300 dark:border-gray-800" />
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="checkbox" disabled class="h-4 w-4 rounded border-gray-200 text-gray-300 dark:border-gray-800" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan Perubahan</x-ui.button>
            </div>
        </form>
    </x-common.component-card>
</div>
