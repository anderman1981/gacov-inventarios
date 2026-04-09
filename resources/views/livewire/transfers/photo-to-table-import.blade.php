<div
    x-data="{
        uploadProgress: 0,
        uploadMessage: '',
        toasts: [],
        pushToast(type, message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message });
            setTimeout(() => {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            }, 4200);
        }
    }"
    x-on:livewire-upload-start="uploadProgress = 8; uploadMessage = 'Subiendo imagen...';"
    x-on:livewire-upload-progress="uploadProgress = Math.max(uploadProgress, $event.detail.progress); uploadMessage = 'Subiendo imagen...';"
    x-on:livewire-upload-finish="uploadProgress = Math.max(uploadProgress, 30); uploadMessage = 'Procesando con IA...';"
    x-on:livewire-upload-error="uploadProgress = 0; uploadMessage = ''; pushToast('error', 'No fue posible subir las imágenes.');"
    x-on:transfer-photo-import-progress.window="uploadProgress = $event.detail.progress; uploadMessage = $event.detail.message;"
    x-on:transfer-photo-import-toast.window="pushToast($event.detail.type, $event.detail.message)"
    class="mb-6"
>
    <div class="relative overflow-hidden rounded-3xl border border-cyan-400/20 bg-slate-950/90 shadow-[0_0_35px_rgba(34,197,94,0.08)]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.15),_transparent_38%),radial-gradient(circle_at_bottom_right,_rgba(0,212,255,0.15),_transparent_32%)]"></div>

        <div class="relative p-6 md:p-7">
            <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="mb-2 inline-flex items-center rounded-full border border-emerald-400/25 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300">
                        Carga inteligente con foto
                    </p>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-50">📸 Cargar planilla por foto</h3>
                    <p class="mt-2 max-w-3xl text-sm text-slate-300">
                        Sube una o varias fotos de la planilla impresa para extraer solamente
                        <strong>COD</strong>, <strong>PRODUCTO</strong> y <strong>SB</strong>,
                        y luego aplica automáticamente las cantidades al formulario del traslado.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 text-xs text-slate-300">
                    <div class="font-semibold text-slate-100">Acceso restringido</div>
                    <div>Disponible para usuarios con permisos de bodega o super admin.</div>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
                <div class="rounded-2xl border border-dashed border-cyan-400/30 bg-slate-900/70 p-5">
                    <label for="transfer-photo-upload" class="block cursor-pointer">
                        <div class="flex min-h-[180px] flex-col items-center justify-center rounded-2xl border border-slate-800 bg-slate-950/80 px-6 py-8 text-center transition hover:border-cyan-400/40 hover:bg-slate-900">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-500/10 text-3xl">
                                📷
                            </div>
                            <div class="text-lg font-semibold text-slate-100">Arrastra aquí la foto o haz clic para seleccionarla</div>
                            <div class="mt-2 text-sm text-slate-400">Acepta JPG y PNG, hasta 4 imágenes de 8 MB cada una.</div>
                            <div class="mt-4 inline-flex rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/20">
                                Seleccionar imágenes
                            </div>
                        </div>
                    </label>

                    <input
                        id="transfer-photo-upload"
                        type="file"
                        wire:model="photos"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        multiple
                        class="hidden">

                    @error('photos')<div class="mt-3 text-sm text-rose-400">{{ $message }}</div>@enderror
                    @error('photos.*')<div class="mt-3 text-sm text-rose-400">{{ $message }}</div>@enderror

                    <div wire:loading.flex wire:target="photos" class="mt-3 items-center gap-2 text-sm text-cyan-200">
                        <span class="inline-block h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-300"></span>
                        Preparando imágenes...
                    </div>

                    @if($previewUrls !== [])
                    <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                        {{ count($previewUrls) }} imagen(es) lista(s) para procesar con IA.
                    </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="uploadAndProcessPhoto"
                            wire:loading.attr="disabled"
                            wire:target="uploadAndProcessPhoto,photos"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_0_24px_rgba(34,197,94,0.2)] transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50">
                            <span>Procesar con IA</span>
                        </button>

                        @if($parsedRows !== [])
                        <button
                            type="button"
                            wire:click="applyToTransfer"
                            class="inline-flex items-center gap-2 rounded-xl border border-cyan-400/30 bg-cyan-500/10 px-5 py-3 text-sm font-semibold text-cyan-100 transition hover:border-cyan-300 hover:bg-cyan-500/20">
                            <span>✅ Reaplicar SB al traslado</span>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/85 p-5">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-300">Estado del proceso</h4>

                    <div class="mb-4 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="h-3 rounded-full bg-gradient-to-r from-cyan-400 via-cyan-300 to-emerald-400 transition-all duration-500"
                            :style="`width:${uploadProgress}%`"></div>
                    </div>

                    <div class="text-sm font-medium text-slate-100" x-text="uploadMessage || 'Esperando imágenes para procesar'"></div>
                    <div class="mt-2 text-xs text-slate-400">
                        La IA suele tardar entre 4 y 8 segundos por foto dependiendo de la nitidez de la planilla.
                    </div>

                    @if($summaryMessage)
                    <div class="mt-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {{ $summaryMessage }}
                    </div>
                    @endif

                    @if($lastError)
                    <div class="mt-4 rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                        {{ $lastError }}
                    </div>
                    @endif
                </div>
            </div>

            @if($previewUrls !== [])
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($previewUrls as $preview)
                <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                    <img src="{{ $preview['url'] }}" alt="{{ $preview['name'] }}" class="h-44 w-full object-cover">
                    <div class="border-t border-slate-800 px-3 py-2 text-xs text-slate-300">{{ $preview['name'] }}</div>
                    <div
                        wire:loading.flex
                        wire:target="uploadAndProcessPhoto"
                        class="absolute inset-0 hidden items-center justify-center bg-slate-950/70 text-center text-sm font-semibold text-slate-100">
                        Procesando con IA...
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if($parsedRows !== [])
            <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/80 p-4">
                <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-slate-50">Tabla editable detectada</h4>
                        <p class="text-sm text-slate-400">Revisa los datos antes de aplicarlos al traslado.</p>
                    </div>
                    <div class="text-xs text-slate-400">
                        {{ count($parsedRows) }} fila(s) detectada(s)
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0 overflow-hidden rounded-2xl">
                        <thead>
                            <tr class="bg-slate-900 text-left text-xs uppercase tracking-[0.16em] text-slate-300">
                                <th class="sticky left-0 z-20 border-b border-slate-800 bg-slate-900 px-3 py-3">COD</th>
                                <th class="border-b border-slate-800 px-3 py-3">Producto</th>
                                <th class="border-b border-slate-800 px-3 py-3 text-center">SB</th>
                                <th class="border-b border-slate-800 px-3 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parsedRows as $index => $row)
                            <tr class="border-b border-slate-900/80 text-sm text-slate-100">
                                <td class="sticky left-0 z-10 border-b border-slate-900 bg-slate-950 px-3 py-3 align-top">
                                    <input
                                        type="text"
                                        wire:model.live="parsedRows.{{ $index }}.cod"
                                        @disabled($editingRowIndex !== $index)
                                        class="w-28 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-100 disabled:cursor-default disabled:opacity-70">
                                </td>
                                <td class="border-b border-slate-900 px-3 py-3 align-top">
                                    <input
                                        type="text"
                                        wire:model.live="parsedRows.{{ $index }}.producto"
                                        @disabled($editingRowIndex !== $index)
                                        class="w-64 min-w-[220px] rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-100 disabled:cursor-default disabled:opacity-70">
                                </td>
                                <td class="border-b border-slate-900 px-3 py-3 align-top text-center">
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        wire:model.live="parsedRows.{{ $index }}.sb"
                                        class="mx-auto w-24 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-center text-sm text-slate-100">
                                </td>
                                <td class="border-b border-slate-900 px-3 py-3 align-top">
                                    <div class="flex justify-end gap-2">
                                        @if($editingRowIndex === $index)
                                        <button
                                            type="button"
                                            wire:click="stopEditingRow"
                                            class="rounded-xl bg-emerald-500 px-3 py-2 text-xs font-semibold text-slate-950">
                                            Guardar
                                        </button>
                                        @else
                                        <button
                                            type="button"
                                            wire:click="startEditingRow({{ $index }})"
                                            class="rounded-xl border border-cyan-400/25 bg-cyan-500/10 px-3 py-2 text-xs font-semibold text-cyan-100">
                                            Editar
                                        </button>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="removeRow({{ $index }})"
                                            class="rounded-xl border border-rose-400/25 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-100">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-900/80 text-sm font-semibold text-slate-100">
                                <td class="sticky left-0 z-10 border-t border-slate-800 bg-slate-900 px-3 py-3">Totales</td>
                                <td class="border-t border-slate-800 px-3 py-3 text-slate-400">Suma de SB</td>
                                <td class="border-t border-slate-800 px-3 py-3 text-center">{{ number_format((float) ($footerTotals['sb'] ?? 0), 0, ',', '.') }}</td>
                                <td class="border-t border-slate-800 px-3 py-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="pointer-events-none fixed right-5 top-5 z-50 flex w-full max-w-sm flex-col gap-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="pointer-events-auto rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur"
                :class="toast.type === 'success'
                    ? 'border-emerald-400/25 bg-emerald-500/10 text-emerald-100'
                    : 'border-rose-400/25 bg-rose-500/10 text-rose-100'">
                <div class="text-sm font-semibold" x-text="toast.message"></div>
            </div>
        </template>
    </div>
</div>
