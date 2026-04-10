@php
    $productsForJs = collect($productCatalog)
        ->map(fn ($p, $k) => ['key' => $k, 'code' => $p['code'], 'name' => $p['name']])
        ->values()
        ->all();

    $machinesForJs = collect($routeMachines)
        ->map(fn ($m, $k) => ['key' => $k, 'code' => $m['code'], 'name' => $m['name']])
        ->values()
        ->all();
@endphp

{{-- ============================================================
     PHOTO MODE WRAPPER (Alpine toasts + upload events)
============================================================ --}}
<div
    x-data="{
        uploadProgress: 0,
        uploadMessage: '',
        isProcessing: false,
        toasts: [],
        addToast(type, message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 5000);
        }
    }"
    x-on:livewire-upload-start="isProcessing=true;uploadProgress=8;uploadMessage='Subiendo planilla...'"
    x-on:livewire-upload-progress="isProcessing=true;uploadProgress=Math.max(uploadProgress,$event.detail.progress);uploadMessage='Subiendo planilla...'"
    x-on:livewire-upload-finish="isProcessing=true;uploadProgress=Math.max(uploadProgress,30);uploadMessage='Procesando con IA...'"
    x-on:livewire-upload-error="isProcessing=false;uploadProgress=0;uploadMessage='';addToast('error','No fue posible subir las imágenes.')"
    x-on:driver-stocking-photo-progress.window="isProcessing=true;uploadProgress=Math.max(uploadProgress,$event.detail.progress);uploadMessage=$event.detail.message"
    x-on:driver-stocking-photo-toast.window="addToast($event.detail.type,$event.detail.message);if($event.detail.type==='success'){uploadProgress=100;uploadMessage='Planilla procesada.';isProcessing=false;}if($event.detail.type==='error'){uploadProgress=0;isProcessing=false;}"
    class="driver-photo-import"
>

{{-- ============================================================
     MANUAL ENTRY MODE
============================================================ --}}
@if($manualMode)
<div
    x-data="manualStockingForm({
        products: @js($productsForJs),
        machines: @js($machinesForJs),
        routeCode: @js($routeCode ?? 'route'),
        routeName: @js($routeName ?? ''),
    })"
    x-init="init()"
>

    {{-- Network banner --}}
    <div x-show="isOffline" x-cloak style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3) var(--space-5);background:rgba(245,158,11,.12);border-bottom:1px solid rgba(245,158,11,.25);font-size:13px;font-weight:600;color:#fde68a">
        <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="flex-shrink:0"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Sin conexión — los datos se guardan localmente y se sincronizarán automáticamente
    </div>

    {{-- Pending offline sync banner --}}
    <div x-show="hasPendingSync" x-cloak style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);padding:var(--space-3) var(--space-5);background:rgba(59,130,246,.1);border-bottom:1px solid rgba(59,130,246,.2);font-size:13px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:var(--space-2);color:#93c5fd">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
            <span>Tienes una planilla guardada offline (<span x-text="pendingData ? formatTs(pendingData.ts) : ''"></span>)</span>
        </div>
        <div style="display:flex;gap:var(--space-2)">
            <button @click="loadPending()" type="button" style="padding:5px 12px;background:rgba(59,130,246,.2);color:#93c5fd;border:1px solid rgba(59,130,246,.3);border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer">
                Cargar datos
            </button>
            <button @click="!isOffline && syncPending()" :disabled="isOffline || isSubmitting" type="button" style="padding:5px 12px;background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.25);border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer" :style="isOffline ? 'opacity:.4;cursor:not-allowed' : ''">
                <span x-show="!isSubmitting">Sincronizar ahora</span>
                <span x-show="isSubmitting">Enviando...</span>
            </button>
            <button @click="clearQueue()" type="button" style="padding:5px 12px;background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer">
                Descartar
            </button>
        </div>
    </div>

    {{-- Panel --}}
    <div class="panel" style="margin-bottom:var(--space-5);overflow:hidden">

        {{-- Panel header --}}
        <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
            <div>
                <span class="panel-title">Entrada manual de surtido</span>
                <div style="margin-top:4px;font-size:12px;color:var(--gacov-text-muted)">
                    Selecciona la máquina, busca los productos y escribe las cantidades.
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:var(--space-2);flex-wrap:wrap">
                <span x-show="!isOffline" class="badge badge-success" style="font-size:11px">En línea</span>
                <span x-show="isOffline" class="badge badge-warning" style="font-size:11px">Sin conexión</span>
                <span x-show="lastSavedAt" x-cloak style="font-size:11px;color:var(--gacov-text-muted)" x-text="'Guardado ' + lastSavedAtText"></span>
                <button type="button" wire:click="disableManualMode" style="padding:6px 14px;background:rgba(148,163,184,.1);color:var(--gacov-text-secondary);border:1px solid var(--gacov-border);border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer">
                    ← Volver a foto
                </button>
            </div>
        </div>

        <div class="panel-body" style="padding:0">

            {{-- Step 1: Machine selector --}}
            <div style="padding:var(--space-5);border-bottom:1px solid var(--gacov-border)">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gacov-text-muted);margin-bottom:var(--space-3)">
                    Paso 1 — Selecciona la máquina a surtir
                </div>

                @if(count($machinesForJs) === 0)
                <div style="padding:var(--space-4);background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:var(--radius-md);font-size:13px;color:#fde68a">
                    Esta ruta no tiene máquinas activas registradas. Contacta al administrador.
                </div>
                @elseif(count($machinesForJs) <= 8)
                {{-- Card grid for small sets --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:var(--space-2)">
                    @foreach($machinesForJs as $machine)
                    <button
                        type="button"
                        @click="selectMachine('{{ $machine['key'] }}')"
                        :style="selectedMachineKey === '{{ $machine['key'] }}' ? 'background:rgba(0,212,255,.12);border-color:var(--gacov-primary);color:var(--gacov-primary)' : 'background:var(--gacov-bg-elevated);border-color:var(--gacov-border);color:var(--gacov-text-secondary)'"
                        style="padding:var(--space-3) var(--space-3);border:1.5px solid;border-radius:var(--radius-md);cursor:pointer;text-align:left;transition:all 150ms">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em">{{ $machine['code'] }}</div>
                        <div style="font-size:11px;margin-top:2px;opacity:.7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $machine['name'] }}</div>
                    </button>
                    @endforeach
                </div>
                @else
                {{-- Dropdown for larger sets --}}
                <select
                    @change="selectMachine($event.target.value)"
                    class="form-input"
                    style="max-width:360px">
                    <option value="">— Selecciona una máquina —</option>
                    @foreach($machinesForJs as $machine)
                    <option value="{{ $machine['key'] }}">{{ $machine['code'] }} — {{ $machine['name'] }}</option>
                    @endforeach
                </select>
                @endif
            </div>

            {{-- Step 2: Products (shown after machine selected) --}}
            <div x-show="selectedMachineKey !== ''" x-cloak>

                {{-- Search & filter bar (sticky) --}}
                <div style="position:sticky;top:0;z-index:10;padding:var(--space-3) var(--space-5);background:var(--gacov-bg-surface);border-bottom:1px solid var(--gacov-border);display:flex;gap:var(--space-3);align-items:center;flex-wrap:wrap">
                    <div style="position:relative;flex:1;min-width:200px">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--gacov-text-muted);pointer-events:none">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        <input
                            type="search"
                            x-model.debounce.200ms="searchQuery"
                            placeholder="Buscar por nombre o código..."
                            autocomplete="off"
                            style="width:100%;padding:9px 12px 9px 34px;background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);border-radius:var(--radius-md);color:var(--gacov-text-primary);font-size:13px;outline:none">
                    </div>
                    <div style="display:flex;gap:var(--space-2);align-items:center;flex-shrink:0">
                        <button
                            type="button"
                            @click="showOnlyFilled = !showOnlyFilled"
                            :style="showOnlyFilled ? 'background:rgba(0,212,255,.12);border-color:var(--gacov-primary);color:var(--gacov-primary)' : 'background:var(--gacov-bg-elevated);border-color:var(--gacov-border);color:var(--gacov-text-secondary)'"
                            style="padding:8px 14px;border:1.5px solid;border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 150ms">
                            <span x-text="showOnlyFilled ? 'Con cantidad ✓' : 'Todos'"></span>
                        </button>
                        <button
                            type="button"
                            x-show="filledCount > 0"
                            @click="if(confirm('¿Limpiar todas las cantidades?')) clearAll()"
                            style="padding:8px 12px;background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">
                            Limpiar todo
                        </button>
                    </div>
                </div>

                {{-- Products list --}}
                <div style="padding:var(--space-2) 0;min-height:200px">

                    {{-- Empty search result --}}
                    <div x-show="filteredProducts.length === 0" style="padding:var(--space-10);text-align:center;color:var(--gacov-text-muted);font-size:13px">
                        <div style="font-size:28px;margin-bottom:var(--space-3)">🔍</div>
                        <div>No se encontraron productos para "<span x-text="searchQuery"></span>"</div>
                    </div>

                    <template x-for="product in filteredProducts" :key="product.key">
                        <div
                            :style="(quantities[product.key] || 0) > 0 ? 'background:rgba(16,185,129,.05);border-left:3px solid rgba(16,185,129,.4)' : 'border-left:3px solid transparent'"
                            style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3) var(--space-5);border-bottom:1px solid rgba(255,255,255,.04);transition:background 150ms">

                            {{-- Code badge --}}
                            <div style="flex-shrink:0;min-width:60px">
                                <span
                                    style="font-size:11px;font-weight:700;font-family:var(--font-mono);color:var(--gacov-primary);background:rgba(0,212,255,.08);padding:2px 6px;border-radius:var(--radius-sm)"
                                    x-text="product.code"></span>
                            </div>

                            {{-- Product name --}}
                            <div style="flex:1;min-width:0">
                                <div style="font-size:13px;font-weight:500;color:var(--gacov-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="product.name"></div>
                            </div>

                            {{-- Quantity controls --}}
                            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                                <button
                                    type="button"
                                    @click="decrement(product.key)"
                                    :disabled="(quantities[product.key] || 0) <= 0"
                                    style="width:36px;height:36px;border-radius:var(--radius-md);border:1px solid var(--gacov-border);background:var(--gacov-bg-elevated);color:var(--gacov-text-secondary);font-size:18px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;touch-action:manipulation;transition:all 100ms"
                                    :style="(quantities[product.key]||0) <= 0 ? 'opacity:.3;cursor:not-allowed' : 'hover:border-color:var(--gacov-primary)'">
                                    −
                                </button>
                                <input
                                    type="number"
                                    :value="quantities[product.key] || 0"
                                    @change="setQty(product.key, $event.target.value)"
                                    @focus="$event.target.select()"
                                    min="0"
                                    max="9999"
                                    inputmode="numeric"
                                    style="width:56px;height:36px;text-align:center;background:var(--gacov-bg-elevated);border:1px solid var(--gacov-border);border-radius:var(--radius-md);color:var(--gacov-text-primary);font-size:15px;font-weight:700;-moz-appearance:textfield;outline:none;transition:border-color 150ms"
                                    :style="(quantities[product.key]||0)>0 ? 'border-color:rgba(16,185,129,.5);color:#6ee7b7' : ''">
                                <button
                                    type="button"
                                    @click="increment(product.key)"
                                    style="width:36px;height:36px;border-radius:var(--radius-md);border:1px solid rgba(0,212,255,.3);background:rgba(0,212,255,.08);color:var(--gacov-primary);font-size:18px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;touch-action:manipulation;transition:all 100ms">
                                    +
                                </button>
                            </div>

                            {{-- Filled indicator --}}
                            <div style="width:18px;flex-shrink:0">
                                <svg x-show="(quantities[product.key]||0)>0" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" style="color:#10b981"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Placeholder when no machine selected --}}
            <div x-show="selectedMachineKey === ''" style="padding:var(--space-10);text-align:center;color:var(--gacov-text-muted)">
                <div style="font-size:36px;margin-bottom:var(--space-3)">🏭</div>
                <div style="font-size:14px;font-weight:600;color:var(--gacov-text-secondary)">Selecciona la máquina para continuar</div>
            </div>

        </div>{{-- end panel-body --}}
    </div>{{-- end panel --}}

    {{-- Sticky footer --}}
    <div
        x-show="selectedMachineKey !== ''"
        x-cloak
        style="position:sticky;bottom:0;z-index:20;background:var(--gacov-bg-surface);border-top:1px solid var(--gacov-border);padding:var(--space-4) var(--space-5);display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap;box-shadow:0 -4px 16px rgba(0,0,0,.3)">

        {{-- Summary stats --}}
        <div style="display:flex;gap:var(--space-4);align-items:center;flex-wrap:wrap">
            <div style="font-size:12px;color:var(--gacov-text-muted)">
                Productos con cantidad:
                <strong style="color:var(--gacov-text-primary)" x-text="filledCount"></strong>
                <span style="opacity:.5"> / </span>
                <span x-text="totalProducts"></span>
            </div>
            <div style="font-size:12px;color:var(--gacov-text-muted)">
                Total unidades:
                <strong style="color:var(--gacov-primary);font-size:15px" x-text="totalUnits"></strong>
            </div>
            <div x-show="selectedMachine" style="font-size:12px;color:var(--gacov-text-muted)">
                Máquina: <strong style="color:var(--gacov-text-secondary)" x-text="selectedMachine?.code"></strong>
            </div>
        </div>

        {{-- Action buttons --}}
        <div style="display:flex;gap:var(--space-3);align-items:center">
            <div x-show="isOffline" style="font-size:11px;color:#fde68a;display:flex;align-items:center;gap:4px">
                <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Se guardará offline
            </div>
            <button
                type="button"
                @click="submit()"
                :disabled="!canSubmit"
                :style="canSubmit ? '' : 'opacity:.4;cursor:not-allowed'"
                class="btn btn-primary"
                style="width:auto;min-width:160px;font-size:14px;padding:11px 24px">
                <span x-show="!isSubmitting && !isOffline">Guardar planilla</span>
                <span x-show="!isSubmitting && isOffline">Guardar offline</span>
                <span x-show="isSubmitting">Enviando...</span>
            </button>
        </div>
    </div>

</div>{{-- end x-data manualStockingForm --}}

{{-- ============================================================
     PHOTO MODE
============================================================ --}}
@else
<div class="panel" style="margin-bottom:var(--space-5);overflow:hidden">
    <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
        <div>
            <span class="panel-title">Carga por foto para surtido</span>
            <div style="margin-top:6px;font-size:12px;color:var(--gacov-text-muted)">
                Usa la planilla de la ruta para cargar automáticamente la columna de la máquina seleccionada.
            </div>
        </div>
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
            <span class="badge badge-info">Ruta: {{ $routeName ?? 'Sin ruta' }}</span>
            @if($sheetRouteName)
            <span class="badge badge-success">Planilla: {{ $sheetRouteName }}</span>
            @endif
        </div>
    </div>
    <div class="panel-body">
        <div class="driver-photo-import__grid">
            <div>
                <label for="driver-stocking-photo-upload" style="display:block;cursor:pointer">
                    <div class="driver-photo-import__dropzone">
                        <div style="font-size:30px;margin-bottom:var(--space-3)">📷</div>
                        <div style="font-weight:600;color:var(--gacov-text-primary)">Sube la planilla de surtido</div>
                        <div style="font-size:13px;color:var(--gacov-text-muted);margin-top:var(--space-2)">JPG o PNG · máx. 4 imágenes · 8 MB c/u</div>
                        <div style="margin-top:var(--space-4)"><span class="driver-photo-import__action">Seleccionar imágenes</span></div>
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
                <div class="driver-photo-import__selected">{{ count($photos) }} imagen(es) lista(s) para procesar.</div>
                <div class="driver-photo-import__files">
                    @foreach($photos as $photo)
                    <div class="driver-photo-import__file">{{ $photo->getClientOriginalName() }}</div>
                    @endforeach
                </div>
                @endif

                <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4);flex-wrap:wrap;align-items:center">
                    <button
                        type="button"
                        wire:click="uploadAndProcessPhoto"
                        wire:loading.attr="disabled"
                        wire:target="uploadAndProcessPhoto,photos"
                        class="btn btn-primary"
                        style="width:auto">
                        <span wire:loading.remove wire:target="uploadAndProcessPhoto">Procesar planilla con IA</span>
                        <span wire:loading wire:target="uploadAndProcessPhoto">Procesando...</span>
                    </button>
                    <button
                        type="button"
                        wire:click="enableManualMode"
                        wire:loading.attr="disabled"
                        wire:target="uploadAndProcessPhoto,photos,enableManualMode"
                        style="padding:10px 16px;background:transparent;color:var(--gacov-text-secondary);border:1px solid var(--gacov-border);border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer">
                        Ingresar manualmente
                    </button>
                </div>
            </div>

            <div class="driver-photo-import__status">
                <div style="margin-bottom:var(--space-3);font-size:12px;font-weight:600;color:var(--gacov-text-secondary);text-transform:uppercase;letter-spacing:.08em">Estado del proceso</div>
                <div style="overflow:hidden;border-radius:999px;background:rgba(148,163,184,.15);height:10px">
                    <div style="height:10px;border-radius:999px;background:linear-gradient(90deg,#00D4FF,#22c55e);transition:width .35s" :style="`width:${uploadProgress}%`"></div>
                </div>
                <div class="driver-photo-import__status-row">
                    <span class="driver-photo-import__status-badge" :class="isProcessing ? 'is-processing' : 'is-idle'">
                        <span class="driver-photo-import__status-dot"></span>
                        <span x-text="isProcessing ? 'Procesando ahora' : 'En espera'"></span>
                    </span>
                    <span class="driver-photo-import__status-percent" x-text="`${Math.round(uploadProgress)}%`"></span>
                </div>
                <div style="margin-top:var(--space-3);font-size:13px;color:var(--gacov-text-primary)" x-text="uploadMessage || 'Esperando planilla...'"></div>

                <div x-show="isProcessing" x-cloak class="driver-photo-import__processing-note">
                    La IA está analizando las imágenes. Espera el mensaje de resultado antes de volver a intentar.
                </div>

                @if($summaryMessage)
                <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid rgba(16,185,129,.2);background:rgba(16,185,129,.08);border-radius:var(--radius-lg);font-size:13px;color:var(--gacov-text-primary)">
                    {{ $summaryMessage }}
                </div>
                @endif

                @if($lastError)
                <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid rgba(239,68,68,.2);background:rgba(239,68,68,.08);border-radius:var(--radius-lg);font-size:13px">
                    <div style="font-weight:700;color:#fca5a5;margin-bottom:var(--space-2)">Error al procesar planilla</div>
                    <div style="color:#fecaca">{{ $lastError }}</div>
                    <div style="margin-top:var(--space-3)">
                        <button type="button" wire:click="enableManualMode" style="padding:7px 14px;background:rgba(245,158,11,.15);color:#fde68a;border:1px solid rgba(245,158,11,.3);border-radius:var(--radius-md);font-size:12px;font-weight:700;cursor:pointer">
                            Ingresar datos manualmente →
                        </button>
                    </div>
                </div>
                @endif

                @if($machineColumns !== [])
                <div style="margin-top:var(--space-4);padding:var(--space-4);border:1px solid var(--gacov-border);background:rgba(255,255,255,.02);border-radius:var(--radius-lg)">
                    <div style="font-size:12px;font-weight:600;color:var(--gacov-text-secondary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:var(--space-2)">Máquinas detectadas</div>
                    <div style="font-size:13px;color:var(--gacov-text-primary)">{{ implode(', ', $machineColumns) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============================================================
     TOAST STACK
============================================================ --}}
<div class="driver-photo-import__toasts" x-cloak>
    <template x-for="toast in toasts" :key="toast.id">
        <div x-cloak x-transition.opacity.duration.250ms class="driver-photo-import__toast">
            <div style="font-size:13px;font-weight:600;color:#f8fafc" x-text="toast.message"></div>
        </div>
    </template>
</div>

</div>{{-- end outer wrapper --}}

@assets
<style>
/* Remove number input arrows */
.driver-photo-import input[type=number]::-webkit-outer-spin-button,
.driver-photo-import input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.driver-photo-import input[type=number] { -moz-appearance: textfield; }
.driver-photo-import input[type=search]::-webkit-search-cancel-button { display:none; }
/* Product row hover */
.driver-photo-import [x-cloak] { display:none !important; }
</style>
@endassets

@script
<script>
window.manualStockingForm = function ({ products, machines, routeCode, routeName }) {
    return {
        // ── State ──────────────────────────────────────────────────────
        quantities: Object.fromEntries(products.map(p => [p.key, 0])),
        searchQuery: '',
        showOnlyFilled: false,
        selectedMachineKey: '',
        isOffline: !navigator.onLine,
        hasPendingSync: false,
        pendingData: null,
        isSubmitting: false,
        lastSavedAt: null,
        saveDebounce: null,

        // ── Computed ────────────────────────────────────────────────────
        get selectedMachine() {
            return machines.find(m => m.key === this.selectedMachineKey) ?? null;
        },
        get filteredProducts() {
            const q = this.searchQuery.trim().toLowerCase();
            return products.filter(p => {
                if (this.showOnlyFilled && (this.quantities[p.key] || 0) <= 0) return false;
                if (!q) return true;
                return p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q);
            });
        },
        get totalProducts() {
            return products.length;
        },
        get filledCount() {
            return products.filter(p => (this.quantities[p.key] || 0) > 0).length;
        },
        get totalUnits() {
            return products.reduce((s, p) => s + (parseInt(this.quantities[p.key]) || 0), 0);
        },
        get canSubmit() {
            return this.selectedMachineKey !== '' && this.totalUnits > 0 && !this.isSubmitting;
        },
        get storageKey() {
            return 'gacov_manual_' + routeCode + '_' + this.selectedMachineKey;
        },
        get offlineQueueKey() {
            return 'gacov_offline_queue';
        },
        get lastSavedAtText() {
            if (!this.lastSavedAt) return '';
            return this.lastSavedAt.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        },

        // ── Init ────────────────────────────────────────────────────────
        init() {
            this.checkPendingSync();

            window.addEventListener('online',  () => { this.isOffline = false; this.checkPendingSync(); });
            window.addEventListener('offline', () => { this.isOffline = true; });

            // Auto-select single machine
            if (machines.length === 1) {
                this.$nextTick(() => this.selectMachine(machines[0].key));
            }
        },

        // ── Machine ─────────────────────────────────────────────────────
        selectMachine(key) {
            this.selectedMachineKey = key;
            this.$nextTick(() => this.restoreFromStorage());
        },

        // ── Quantities ──────────────────────────────────────────────────
        increment(key) {
            this.quantities[key] = (parseInt(this.quantities[key]) || 0) + 1;
            this.scheduleSave();
        },
        decrement(key) {
            const v = parseInt(this.quantities[key]) || 0;
            this.quantities[key] = Math.max(0, v - 1);
            this.scheduleSave();
        },
        setQty(key, val) {
            const n = parseInt(val);
            this.quantities[key] = isNaN(n) || n < 0 ? 0 : n;
            this.scheduleSave();
        },
        clearAll() {
            products.forEach(p => { this.quantities[p.key] = 0; });
            this.saveToStorage();
        },

        // ── Auto-save ───────────────────────────────────────────────────
        scheduleSave() {
            clearTimeout(this.saveDebounce);
            this.saveDebounce = setTimeout(() => this.saveToStorage(), 600);
        },
        saveToStorage() {
            if (!this.selectedMachineKey) return;
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    machineKey: this.selectedMachineKey,
                    quantities: { ...this.quantities },
                    ts: Date.now(),
                }));
                this.lastSavedAt = new Date();
            } catch(e) { /* storage full */ }
        },
        restoreFromStorage() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;
                const data = JSON.parse(raw);
                // Only restore data < 24 h old
                if (Date.now() - data.ts < 86400000) {
                    const hasAny = Object.values(data.quantities).some(v => parseInt(v) > 0);
                    if (hasAny) {
                        Object.entries(data.quantities).forEach(([k, v]) => {
                            if (k in this.quantities) this.quantities[k] = parseInt(v) || 0;
                        });
                        this.lastSavedAt = new Date(data.ts);
                    }
                }
            } catch(e) {}
        },
        clearStorage() {
            try { localStorage.removeItem(this.storageKey); } catch(e) {}
        },

        // ── Offline queue ────────────────────────────────────────────────
        checkPendingSync() {
            try {
                const queue = JSON.parse(localStorage.getItem(this.offlineQueueKey) || '[]');
                const mine  = queue.filter(i => i.routeCode === routeCode);
                this.hasPendingSync = mine.length > 0;
                this.pendingData    = mine.at(-1) ?? null;
            } catch(e) {}
        },
        queueOffline() {
            try {
                const queue = JSON.parse(localStorage.getItem(this.offlineQueueKey) || '[]');
                queue.push({
                    id: Date.now(),
                    routeCode,
                    routeName,
                    machineKey:   this.selectedMachineKey,
                    machineLabel: this.selectedMachine?.code ?? this.selectedMachineKey,
                    quantities:   { ...this.quantities },
                    ts: Date.now(),
                });
                localStorage.setItem(this.offlineQueueKey, JSON.stringify(queue));
                this.checkPendingSync();
            } catch(e) {}
        },
        clearQueue() {
            try {
                const queue    = JSON.parse(localStorage.getItem(this.offlineQueueKey) || '[]');
                const filtered = queue.filter(i => i.routeCode !== routeCode);
                localStorage.setItem(this.offlineQueueKey, JSON.stringify(filtered));
                this.hasPendingSync = false;
                this.pendingData    = null;
            } catch(e) {}
        },
        loadPending() {
            if (!this.pendingData) return;
            const key  = this.pendingData.machineKey;
            const mach = machines.find(m => m.key === key);
            if (mach) this.selectMachine(key);
            Object.entries(this.pendingData.quantities).forEach(([k, v]) => {
                if (k in this.quantities) this.quantities[k] = parseInt(v) || 0;
            });
        },

        // ── Submit ───────────────────────────────────────────────────────
        async submit() {
            if (!this.canSubmit) return;

            if (this.isOffline) {
                this.queueOffline();
                this.saveToStorage();
                this.$dispatch('driver-stocking-photo-toast', {
                    type: 'error',
                    message: 'Sin conexión. Planilla guardada localmente. Se enviará al recuperar señal.',
                });
                return;
            }

            this.isSubmitting = true;
            try {
                await $wire.submitManualData(this.selectedMachineKey, this.quantities);
                this.clearStorage();
            } catch(e) {
                this.$dispatch('driver-stocking-photo-toast', {
                    type: 'error',
                    message: 'Error al enviar. Intenta de nuevo.',
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        async syncPending() {
            if (!this.pendingData || this.isOffline) return;
            this.isSubmitting = true;
            try {
                await $wire.submitManualData(
                    this.pendingData.machineKey,
                    this.pendingData.quantities,
                );
                this.clearQueue();
            } catch(e) {
                this.$dispatch('driver-stocking-photo-toast', {
                    type: 'error',
                    message: 'Error al sincronizar. Intenta de nuevo.',
                });
            } finally {
                this.isSubmitting = false;
            }
        },

        // ── Utils ────────────────────────────────────────────────────────
        formatTs(ts) {
            if (!ts) return '';
            return new Date(ts).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
        },
    };
};
</script>
@endscript
