<div
    x-data="{
        uploadProgress: 0,
        uploadMessage: '',
        isProcessing: false,
        toasts: [],
        pushToast(type, message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message });
            setTimeout(() => {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            }, 4200);
        }
    }"
    x-on:livewire-upload-start="isProcessing = true; uploadProgress = 8; uploadMessage = 'Subiendo planilla...';"
    x-on:livewire-upload-progress="isProcessing = true; uploadProgress = Math.max(uploadProgress, $event.detail.progress); uploadMessage = 'Subiendo planilla...';"
    x-on:livewire-upload-finish="isProcessing = true; uploadProgress = Math.max(uploadProgress, 30); uploadMessage = 'Procesando planilla con IA...';"
    x-on:livewire-upload-error="isProcessing = false; uploadProgress = 0; uploadMessage = ''; pushToast('error', 'No fue posible subir las imágenes.');"
    x-on:driver-stocking-photo-progress.window="isProcessing = true; uploadProgress = Math.max(uploadProgress, $event.detail.progress); uploadMessage = $event.detail.message;"
    x-on:driver-stocking-photo-toast.window="pushToast($event.detail.type, $event.detail.message); if ($event.detail.type === 'success') { uploadProgress = 100; uploadMessage = 'Planilla procesada correctamente.'; isProcessing = false; } if ($event.detail.type === 'error') { uploadProgress = 0; isProcessing = false; }"
    class="driver-photo-import"
>
    <div class="panel" style="margin-bottom:var(--space-5);overflow:hidden">
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
            <div>
                <span class="panel-title">Carga por foto para surtido</span>
                <div style="margin-top:6px;font-size:12px;color:var(--gacov-text-muted)">
                    Usa la planilla de la ruta para cargar automáticamente la columna de la máquina seleccionada.
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
                <span class="badge badge-info">Ruta actual: {{ $routeName ?? 'Sin ruta' }}</span>
                @if($sheetRouteName)
                <span class="badge badge-success">Planilla: {{ $sheetRouteName }}</span>
                @endif
                @if($sheetOperatorName)
                <span class="badge badge-info">Rutero: {{ $sheetOperatorName }}</span>
                @endif
            </div>
        </div>
        <div class="panel-body">
            <div class="driver-photo-import__grid">
                <div>
                    <label for="driver-stocking-photo-upload" style="display:block;cursor:pointer">
                        <div class="driver-photo-import__dropzone">
                            <div style="font-size:30px;margin-bottom:var(--space-3)">📷</div>
                            <div style="font-weight:600;color:var(--gacov-text-primary)">Sube la planilla de surtido de esta ruta</div>
                            <div style="font-size:13px;color:var(--gacov-text-muted);margin-top:var(--space-2)">
                                Acepta JPG y PNG, hasta 4 imágenes.
                            </div>
                            <div style="margin-top:var(--space-4)">
                                <span class="driver-photo-import__action">Seleccionar imágenes</span>
                            </div>
                        </div>
                    </label>

                    <input
                        id="driver-stocking-photo-upload"
                        type="file"
                        wire:model="photos"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        multiple
                        style="display:none">

                    @error('photos')<div style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-error)">{{ $message }}</div>@enderror
                    @error('photos.*')<div style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-error)">{{ $message }}</div>@enderror

                    @if($photos !== [])
                    <div class="driver-photo-import__selected">
                        {{ count($photos) }} imagen(es) lista(s) para procesar.
                    </div>

                    <div class="driver-photo-import__files">
                        @foreach($photos as $photo)
                        <div class="driver-photo-import__file">
                            {{ $photo->getClientOriginalName() }}
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4);flex-wrap:wrap">
                        <button
                            type="button"
                            wire:click="uploadAndProcessPhoto"
                            wire:loading.attr="disabled"
                            wire:target="uploadAndProcessPhoto,photos"
                            class="btn btn-primary"
                            style="width:auto">
                            <span wire:loading.remove wire:target="uploadAndProcessPhoto">Procesar planilla</span>
                            <span wire:loading wire:target="uploadAndProcessPhoto">Procesando planilla...</span>
                        </button>
                    </div>
                </div>

                <div class="driver-photo-import__status">
                    <div style="margin-bottom:var(--space-3);font-size:12px;font-weight:600;color:var(--gacov-text-secondary);text-transform:uppercase;letter-spacing:.08em">
                        Estado del proceso
                    </div>
                    <div style="overflow:hidden;border-radius:999px;background:rgba(148,163,184,.15);height:10px">
                        <div
                            style="height:10px;border-radius:999px;background:linear-gradient(90deg,#00D4FF,#22c55e);transition:width .35s"
                            :style="`width:${uploadProgress}%`"></div>
                    </div>
                    <div class="driver-photo-import__status-row">
                        <span
                            class="driver-photo-import__status-badge"
                            :class="isProcessing ? 'is-processing' : 'is-idle'">
                            <span class="driver-photo-import__status-dot"></span>
                            <span x-text="isProcessing ? 'Procesando ahora' : 'En espera'"></span>
                        </span>
                        <span class="driver-photo-import__status-percent" x-text="`${Math.round(uploadProgress)}%`"></span>
                    </div>
                    <div style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-primary)" x-text="uploadMessage || 'Esperando planilla...'"></div>

                    <div
                        x-show="isProcessing"
                        x-cloak
                        class="driver-photo-import__processing-note">
                        La IA está trabajando sobre las imágenes cargadas. Espera a que aparezca el mensaje de éxito o error antes de volver a intentar.
                    </div>

                    @if($summaryMessage)
                    <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid rgba(16,185,129,.2);background:rgba(16,185,129,.08);border-radius:var(--radius-lg);font-size:13px;color:var(--gacov-text-primary)">
                        {{ $summaryMessage }}
                    </div>
                    @endif

                    @if($lastError)
                    <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid rgba(239,68,68,.2);background:rgba(239,68,68,.08);border-radius:var(--radius-lg);font-size:13px;color:#fecaca">
                        {{ $lastError }}
                    </div>
                    @endif

                    @if($machineColumns !== [])
                    <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid var(--gacov-border);background:rgba(255,255,255,.02);border-radius:var(--radius-lg)">
                        <div style="font-size:12px;font-weight:600;color:var(--gacov-text-secondary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:var(--space-2)">
                            Máquinas detectadas en la planilla
                        </div>
                        <div style="font-size:13px;color:var(--gacov-text-primary)">
                            {{ implode(', ', $machineColumns) }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="driver-photo-import__toasts" x-cloak>
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-cloak
                x-transition.opacity.duration.250ms
                class="driver-photo-import__toast">
                <div style="font-size:13px;font-weight:600;color:#f8fafc" x-text="toast.message"></div>
            </div>
        </template>
    </div>
</div>
